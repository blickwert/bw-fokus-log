#!/usr/bin/env python3
"""Builds the translation files from the sources.

Reads every __( 'text', 'bw-fokus-log' ) call in PHP and TypeScript, writes
free/languages/bw-fokus-log.pot, then — for each scripts/i18n/<locale>.json
(msgid -> translation) — the .po, the compiled .mo and the JSON file the
browser app loads. Strings without a translation are reported.
"""
import glob
import hashlib
import json
import os
import re
import struct
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DOMAIN = "bw-fokus-log"
LANG_DIR = os.path.join(ROOT, "free", "languages")
SCRIPT_HANDLE = "bw-fokus-log-app"

CALL = re.compile(r"""\b(?:__|_e|esc_html__|esc_attr__|esc_html_e)\(\s*(?P<q>['"])(?P<s>(?:\\.|(?!(?P=q)).)*)(?P=q)\s*,\s*['"]bw-fokus-log['"]""", re.S)


def unescape(s, q):
    return s.replace("\\" + q, q).replace("\\\\", "\\")


def collect():
    found = {}  # msgid -> {"refs": set(), "js": bool}
    files = glob.glob(os.path.join(ROOT, "free", "src", "**", "*.php"), recursive=True)
    files += glob.glob(os.path.join(ROOT, "free", "templates", "**", "*.php"), recursive=True)
    files += [os.path.join(ROOT, "free", "bw-fokus-log.php")]
    js = glob.glob(os.path.join(ROOT, "app", "**", "*.ts*"), recursive=True)
    for path in files + js:
        with open(path, encoding="utf-8") as fh:
            text = fh.read()
        for m in CALL.finditer(text):
            msgid = unescape(m.group("s"), m.group("q"))
            line = text.count("\n", 0, m.start()) + 1
            entry = found.setdefault(msgid, {"refs": [], "js": False})
            entry["refs"].append("%s:%d" % (os.path.relpath(path, ROOT), line))
            entry["js"] = entry["js"] or path in js
    return found


def esc(s):
    return s.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n")


def po_block(msgid, msgstr, refs):
    out = "".join("#: %s\n" % r for r in refs[:3])
    if "%" in msgid and "%%" not in msgid:
        out += "#, php-format\n"
    return out + 'msgid "%s"\nmsgstr "%s"\n\n' % (esc(msgid), esc(msgstr))


def header(locale):
    return (
        'msgid ""\nmsgstr ""\n"Project-Id-Version: BW Fokus-Log\\n"\n"Language: %s\\n"\n'
        '"MIME-Version: 1.0\\n"\n"Content-Type: text/plain; charset=UTF-8\\n"\n"Content-Transfer-Encoding: 8bit\\n"\n'
        '"Plural-Forms: nplurals=2; plural=(n != 1);\\n"\n"X-Domain: %s\\n"\n\n' % (locale, DOMAIN)
    )


def write_mo(path, messages):
    messages = dict(messages)
    messages[""] = "Content-Type: text/plain; charset=UTF-8\nLanguage: de_DE\nPlural-Forms: nplurals=2; plural=(n != 1);\n"
    keys = sorted(messages)
    ids = strs = b""
    offsets = []
    for k in keys:
        kb, vb = k.encode(), messages[k].encode()
        offsets.append((len(ids), len(kb), len(strs), len(vb)))
        ids += kb + b"\0"
        strs += vb + b"\0"
    n = len(keys)
    keystart = 7 * 4 + 16 * n
    valuestart = keystart + len(ids)
    table = b""
    for o1, l1, o2, l2 in offsets:
        table += struct.pack("<II", l1, o1 + keystart)
    for o1, l1, o2, l2 in offsets:
        table += struct.pack("<II", l2, o2 + valuestart)
    with open(path, "wb") as fh:
        fh.write(struct.pack("<Iiiiiii", 0x950412DE, 0, n, 7 * 4, 7 * 4 + 8 * n, 0, 0) + table + ids + strs)


def main():
    found = collect()
    os.makedirs(LANG_DIR, exist_ok=True)
    ids = sorted(found)

    with open(os.path.join(LANG_DIR, DOMAIN + ".pot"), "w", encoding="utf-8") as fh:
        fh.write(header("") + "".join(po_block(i, "", found[i]["refs"]) for i in ids))

    missing = 0
    for src in glob.glob(os.path.join(ROOT, "scripts", "i18n", "*.json")):
        locale = os.path.splitext(os.path.basename(src))[0]
        with open(src, encoding="utf-8") as fh:
            tr = json.load(fh)
        done = {i: tr[i] for i in ids if tr.get(i)}
        for i in ids:
            if i not in done:
                missing += 1
                print("missing %s: %s" % (locale, i))
        with open(os.path.join(LANG_DIR, "%s-%s.po" % (DOMAIN, locale)), "w", encoding="utf-8") as fh:
            fh.write(header(locale) + "".join(po_block(i, done.get(i, ""), found[i]["refs"]) for i in ids))
        write_mo(os.path.join(LANG_DIR, "%s-%s.mo" % (DOMAIN, locale)), done)

        jed = {"locale_data": {"messages": {"": {"domain": "messages", "lang": locale, "plural_forms": "nplurals=2; plural=(n != 1);"}}}}
        for i in ids:
            if found[i]["js"] and i in done:
                jed["locale_data"]["messages"][i] = [done[i]]
        jed["domain"] = "messages"
        jed["locale_data"]["messages"][""]["domain"] = "messages"
        name = "%s-%s-%s.json" % (DOMAIN, locale, SCRIPT_HANDLE)
        with open(os.path.join(LANG_DIR, name), "w", encoding="utf-8") as fh:
            json.dump({"translation-revision-date": "", "generator": "scripts/i18n.py", "domain": "messages", "locale_data": jed["locale_data"]}, fh, ensure_ascii=False)

    print("%d strings, %d missing translations" % (len(ids), missing))
    return 1 if missing and "--strict" in sys.argv else 0


if __name__ == "__main__":
    sys.exit(main())
