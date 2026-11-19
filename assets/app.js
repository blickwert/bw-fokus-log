/*
 * Fokus-Log – App (ohne Build-Schritt, reines JavaScript).
 * Ablauf: Tagesplan als Text → Elemente → antippen = Timer an/aus, Haken = erledigt
 * → Text im Format „- To-do {start|ende|dauer}“ wird automatisch gespeichert.
 */
(function () {
	'use strict';

	var cfg = window.FokusLogConfig;
	var root = document.getElementById('fokus-log-app');
	if (!cfg || !root) {
		return;
	}

	/* ---------- Hilfsfunktionen ---------- */

	var pad = function (n) { return String(n).padStart(2, '0'); };
	var dateKey = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
	var hm = function (ms) { var d = new Date(ms); return pad(d.getHours()) + ':' + pad(d.getMinutes()); };
	// Dauer im Text mit Sekunden (hh:mm:ss), damit beim Neuladen nichts verloren geht.
	var dur = function (s) { s = Math.max(0, Math.floor(s)); return pad(Math.floor(s / 3600)) + ':' + pad(Math.floor((s % 3600) / 60)) + ':' + pad(s % 60); };
	var clock = function (s) {
		s = Math.max(0, Math.floor(s));
		var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60;
		return h ? h + ':' + pad(m) + ':' + pad(x) : m + ':' + pad(x);
	};
	var mins = function (s) {
		var m = Math.round(s / 60);
		return m < 60 ? m + ' min' : Math.floor(m / 60) + ' h ' + (m % 60) + ' min';
	};
	var esc = function (s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	};
	// „hh:mm:ss“ oder ältere „hh:mm“, begrenzt auf 0 bis 24 Stunden wie am Server.
	var parseDur = function (t) {
		var p = String(t).trim().split(':').map(function (x) { return parseInt(x, 10) || 0; });
		var s = 0;
		if (p.length === 3) { s = p[0] * 3600 + p[1] * 60 + p[2]; }
		else if (p.length === 2) { s = p[0] * 3600 + p[1] * 60; }
		return Math.max(0, Math.min(86400, s));
	};

	function parse(text) {
		var out = [];
		String(text || '').split(/\r\n|\r|\n/).forEach(function (line) {
			var m = line.match(/^\s*[-•*]\s*(.+?)\s*$/);
			if (!m) { return; }
			var b = m[1].match(/^(.*?)\s*\{([^|}]*)\|([^|}]*)\|([^}]*)\}\s*$/);
			if (b) {
				out.push({ title: b[1].trim(), start: b[2].trim(), end: b[3].trim(), acc: parseDur(b[4]), done: b[3].trim() !== '', runStart: null, quote: '' });
			} else {
				out.push({ title: m[1].trim(), start: '', end: '', acc: 0, done: false, runStart: null, quote: '' });
			}
		});
		return out;
	}

	function live(it) { return it.acc + (it.runStart ? (Date.now() - it.runStart) / 1000 : 0); }

	function serialize() {
		return S.items.map(function (it) {
			if (!it.start) { return '- ' + it.title; }
			return '- ' + it.title + ' {' + it.start + '|' + (it.done ? it.end : '') + '|' + dur(live(it)) + '}';
		}).join('\n');
	}

	var lastPick = {};
	function pick(list, key) {
		if (!list || !list.length) { return ''; }
		var k;
		do { k = Math.floor(Math.random() * list.length); } while (list.length > 1 && k === lastPick[key]);
		lastPick[key] = k;
		return list[k];
	}

	/* ---------- Zustand ---------- */

	var today = new Date();
	var S = {
		date: dateKey(today),
		items: [],
		editing: false,
		planText: '',
		pause: parseInt(cfg.pause, 10) || 0,
		brk: null,
		tab: 'heute',
		history: null,
		loaded: false,
		loadError: false,
		rev: 0,        // Revision des Servers, auf der der Stand beruht
		dirty: false,  // Änderungen, die der Server noch nicht hat
		notice: ''     // Hinweis über dem Plan (z. B. Stand von einem anderen Gerät)
	};
	var localKey = function () { return 'fokuslog:' + cfg.userId + ':' + S.date; };
	var originalTitle = document.title;
	var lastSave = 0;
	var saveTimer = null;
	var retryTimer = null;
	var saving = false;
	var saveAgain = false;
	var partyTimer = null;

	/* ---------- Server ---------- */

	function url(path, params) {
		var u = cfg.restUrl + path;
		if (params) {
			u += (u.indexOf('?') > -1 ? '&' : '?') + new URLSearchParams(params).toString();
		}
		return u;
	}

	// Neuer REST-Schlüssel, wenn der alte abgelaufen ist (App lange offen).
	function renewNonce() {
		if (!cfg.ajaxUrl) { return Promise.reject(new Error('no ajax')); }
		return fetch(cfg.ajaxUrl + '?action=rest-nonce', { credentials: 'same-origin' })
			.then(function (r) { return r.ok ? r.text() : ''; })
			.then(function (n) {
				n = String(n).trim();
				if (!n || n === '0' || n === '-1') { throw new Error('logged out'); }
				cfg.nonce = n;
			});
	}

	// Fehler tragen status und die Antwort des Servers (data), z. B. beim Konflikt.
	function api(path, opts, renewed) {
		opts = opts || {};
		return fetch(url(path, opts.params), {
			method: opts.method || 'GET',
			credentials: 'same-origin',
			keepalive: !!opts.keepalive,
			headers: { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' },
			body: opts.body ? JSON.stringify(opts.body) : undefined
		}).then(function (r) {
			return r.json().catch(function () { return null; }).then(function (data) {
				if (r.ok) { return data; }
				if (r.status === 403 && !renewed && data && data.code === 'rest_cookie_invalid_nonce') {
					return renewNonce().then(function () { return api(path, opts, true); });
				}
				var err = new Error('HTTP ' + r.status);
				err.status = r.status;
				err.data = data;
				throw err;
			});
		});
	}

	function readLocal() {
		try { return JSON.parse(localStorage.getItem(localKey()) || 'null'); } catch (e) { return null; }
	}

	// Lokale Sicherung: der Text, ob der Server ihn schon hat, und auf welcher Revision er beruht.
	function persistLocal() {
		var idx = S.items.findIndex(function (i) { return i.runStart; });
		var data = {
			text: serialize(),
			saved: !S.dirty,
			rev: S.rev,
			run: idx > -1 ? { idx: idx, title: S.items[idx].title, runStart: S.items[idx].runStart, acc: S.items[idx].acc } : null,
			brk: S.brk ? { until: S.brk.until, next: S.brk.next } : null
		};
		try { localStorage.setItem(localKey(), JSON.stringify(data)); } catch (e) { /* privater Modus */ }
	}

	function setStatus(text, warn) {
		var el = root.querySelector('[data-status]');
		if (el) {
			el.textContent = text;
			el.classList.toggle('is-warn', !!warn);
		}
	}

	function scheduleSave() {
		S.dirty = true;
		persistLocal();
		clearTimeout(saveTimer);
		saveTimer = setTimeout(saveNow, 600);
	}

	/*
	 * Speichert den aktuellen Stand. Es läuft immer nur eine Speicherung; was währenddessen
	 * geändert wird, geht gleich danach hinterher – so überholt nie ein älterer Stand einen
	 * neueren. Mitgeschickt wird die Revision, auf der der Stand beruht.
	 */
	function saveNow(keepalive) {
		clearTimeout(saveTimer);
		S.dirty = true;
		if (saving && !keepalive) {
			saveAgain = true;
			return Promise.resolve();
		}
		var text = serialize();
		var date = S.date;
		saving = true;
		lastSave = Date.now();
		setStatus('Speichert …');
		return api('day', { method: 'POST', keepalive: keepalive, body: { date: date, content: text, base: S.rev } })
			.then(function (r) {
				if (date !== S.date) { return; }
				S.rev = r.rev;
				clearTimeout(retryTimer);
				retryTimer = null;
				if (serialize() === text) { S.dirty = false; }
				persistLocal();
				setStatus('Gespeichert um ' + hm(Date.now()));
			})
			.catch(function (err) {
				if (date !== S.date) { return; }
				if (err && err.status === 409 && err.data && err.data.data && err.data.data.current) {
					adopt(err.data.data.current, 'Auf einem anderen Gerät geändert – neuer Stand geladen. Deine letzte Änderung bitte noch einmal machen.');
					return;
				}
				persistLocal();
				setStatus('Nicht gespeichert – lokal gesichert, neuer Versuch in 30 Sekunden', true);
				if (!retryTimer) {
					retryTimer = setTimeout(function () { retryTimer = null; if (S.dirty) { saveNow(); } }, 30000);
				}
			})
			.then(function () {
				saving = false;
				if (saveAgain) {
					saveAgain = false;
					saveNow();
				}
			});
	}

	/*
	 * Übernimmt den Stand des Servers (anderes Gerät). Ein hier laufender Timer läuft
	 * beim gleichen, noch offenen To-do weiter.
	 */
	function adopt(day, notice) {
		var running = S.items.find(function (i) { return i.runStart; });
		S.items = parse(day.content);
		S.rev = day.rev || 0;
		S.dirty = false;
		S.brk = null;
		if (running) {
			var same = S.items.find(function (i) { return !i.done && i.title === running.title; });
			if (same) {
				same.acc = Math.max(same.acc, running.acc);
				same.runStart = running.runStart;
				same.start = same.start || running.start;
				same.quote = running.quote;
				S.dirty = true;
			}
		}
		S.notice = notice || '';
		persistLocal();
		if (!S.editing) { render(); }
		if (S.dirty) { scheduleSave(); }
	}

	/* ---------- Coach & Party ---------- */

	var Q = cfg.quotes || {};

	function runQuote(title) {
		return pick(Q.run, 'run').split('{t}').join(title);
	}

	function showParty(item) {
		var done = S.items.filter(function (i) { return i.done; }).length;
		var total = S.items.length;
		var title, text;
		if (done === total) {
			title = 'Alles erledigt!';
			text = Q.allDone || '';
		} else {
			title = pick(Q.titles, 'titles') || 'Geschafft!';
			text = pick(Q.done, 'done')
				.split('{t}').join(item.title)
				.split('{d}').join(mins(item.acc))
				.split('{n}').join(String(done))
				.split('{total}').join(String(total));
		}

		var colors = ['#F59E0B', '#DB2777', '#0F766E', '#6D28D9', '#1D4ED8', '#10B981'];
		var confetti = '';
		for (var k = 0; k < 28; k++) {
			confetti += '<span class="fl-confetti" style="left:' + Math.round(Math.random() * 100) + '%;width:' + (6 + Math.round(Math.random() * 6)) + 'px;height:' + (10 + Math.round(Math.random() * 8)) + 'px;background:' + colors[k % colors.length] + ';animation-duration:' + (1.6 + Math.random() * 1.2).toFixed(2) + 's;animation-delay:' + (Math.random() * 0.5).toFixed(2) + 's"></span>';
		}

		var old = document.querySelector('.fl-party');
		if (old) { old.remove(); }
		var el = document.createElement('div');
		el.className = 'fl-party fokus-log';
		el.setAttribute('role', 'status');
		el.setAttribute('aria-live', 'polite');
		el.innerHTML = confetti +
			'<div class="fl-party-card">' + ICON.party +
			'<div><div class="fl-party-title">' + esc(title) + '</div><div class="fl-party-text">' + esc(text) + '</div></div></div>';
		document.body.appendChild(el);
		clearTimeout(partyTimer);
		partyTimer = setTimeout(function () { el.remove(); }, 3600);
	}

	function beep() {
		try {
			var C = window.AudioContext || window.webkitAudioContext;
			if (!C) { return; }
			var ctx = new C(), o = ctx.createOscillator(), g = ctx.createGain();
			o.frequency.value = 660;
			o.connect(g);
			g.connect(ctx.destination);
			g.gain.setValueAtTime(0.0001, ctx.currentTime);
			g.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.02);
			g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.6);
			o.start();
			o.stop(ctx.currentTime + 0.65);
			o.onended = function () { ctx.close(); };
		} catch (e) { /* kein Ton möglich */ }
	}

	/* ---------- Aktionen ---------- */

	function stopAll() {
		S.items.forEach(function (it) {
			if (it.runStart) { it.acc = live(it); it.runStart = null; }
		});
	}

	function toggle(i) {
		var it = S.items[i];
		if (!it || it.done) { return; }
		if (it.runStart) {
			it.acc = live(it);
			it.runStart = null;
		} else {
			stopAll();
			var now = Date.now();
			it.runStart = now;
			it.start = it.start || hm(now);
			it.quote = runQuote(it.title);
			S.brk = null;
		}
		render();
		scheduleSave();
	}

	function check(i) {
		var it = S.items[i];
		if (!it || it.done) { return; }
		var now = Date.now();
		it.acc = live(it);
		it.runStart = null;
		it.done = true;
		it.start = it.start || hm(now);
		it.end = hm(now);
		it.quote = '';

		var next = -1;
		for (var k = i + 1; k < S.items.length; k++) { if (!S.items[k].done) { next = k; break; } }
		if (next < 0) { next = S.items.findIndex(function (x) { return !x.done; }); }
		// Läuft gerade ein anderes To-do, gibt es keine Pause.
		var other = S.items.some(function (x) { return x.runStart; });
		S.brk = (next > -1 && S.pause > 0 && !other) ? { until: now + S.pause * 60000, next: next, over: false } : null;

		render();
		showParty(it);
		saveNow();
		persistLocal();
	}

	// Das erste offene To-do ab Stelle i (sonst von vorne), -1 wenn alles erledigt ist.
	function nextOpen(i) {
		for (var k = i; k < S.items.length; k++) { if (!S.items[k].done) { return k; } }
		return S.items.findIndex(function (x) { return !x.done; });
	}

	function reopen(i) {
		var it = S.items[i];
		if (!it || !it.done) { return; }
		it.done = false;
		it.end = '';
		render();
		scheduleSave();
	}

	function applyPlan() {
		var running = S.items.find(function (i) { return i.runStart; });
		S.items = parse(S.planText);
		if (running) {
			var same = S.items.find(function (i) { return !i.done && i.title === running.title; });
			if (same) {
				same.acc = running.acc;
				same.runStart = running.runStart;
				same.start = running.start;
				same.quote = running.quote;
			}
		}
		S.editing = false;
		S.brk = null;
		render();
		saveNow();
	}

	function loadYesterday() {
		var d = new Date(S.date + 'T12:00:00');
		d.setDate(d.getDate() - 1);
		var hint = root.querySelector('[data-yesterday-hint]');
		api('day', { params: { date: dateKey(d) } }).then(function (r) {
			var open = parse(r.content).filter(function (i) { return !i.done; });
			if (!open.length) {
				if (hint) { hint.textContent = 'Gestern ist alles erledigt – nichts zu übernehmen.'; }
				return;
			}
			var area = root.querySelector('#fl-plan');
			var add = open.map(function (i) { return '- ' + i.title; }).join('\n');
			S.planText = (S.planText.trim() ? S.planText.replace(/\s+$/, '') + '\n' : '') + add;
			if (area) { area.value = S.planText; }
			if (hint) { hint.textContent = open.length + ' offene To-dos von gestern übernommen.'; }
		}).catch(function () {
			if (hint) { hint.textContent = 'Gestern konnte nicht geladen werden.'; }
		});
	}

	function loadHistory() {
		api('history', { params: { today: S.date, days: 14 } }).then(function (rows) {
			S.history = rows;
			if (S.tab === 'verlauf') { render(); }
		}).catch(function () {
			S.history = [];
			if (S.tab === 'verlauf') { render(); }
		});
	}

	function copyText(btn) {
		var text = serialize();
		var done = function () {
			btn.textContent = 'Kopiert ✓';
			setTimeout(function () { btn.textContent = 'Kopieren'; }, 1800);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done).catch(function () {});
		}
	}

	function downloadText() {
		var blob = new Blob([serialize() + '\n'], { type: 'text/plain;charset=utf-8' });
		var a = document.createElement('a');
		a.href = URL.createObjectURL(blob);
		a.download = 'tagesplan-' + S.date + '.txt';
		document.body.appendChild(a);
		a.click();
		setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
	}

	/* ---------- Icons ---------- */

	var ICON = {
		play: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round" aria-hidden="true"><path d="M7 4l13 8-13 8z"/></svg>',
		pause: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M8 5v14M16 5v14"/></svg>',
		check: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>',
		spark: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/></svg>',
		party: '<svg class="fl-party-icon" width="64" height="64" viewBox="0 0 64 64" fill="none" aria-hidden="true"><path d="M8 56L21 21l22 22z" fill="#F59E0B" stroke="#9A3412" stroke-width="2" stroke-linejoin="round"/><path d="M14 40l10 10M18 30l16 16" stroke="#9A3412" stroke-width="2" stroke-linecap="round"/><path d="M29 17c2-6 8-6 8-12" stroke="#0F766E" stroke-width="3" stroke-linecap="round"/><path d="M47 27c6-2 8 4 14 2" stroke="#6D28D9" stroke-width="3" stroke-linecap="round"/><path d="M38 22l10-10" stroke="#DB2777" stroke-width="3" stroke-linecap="round"/><circle cx="48" cy="6" r="3" fill="#DB2777"/><circle cx="58" cy="40" r="3" fill="#1D4ED8"/><circle cx="36" cy="30" r="2.5" fill="#10B981"/><path d="M56 10l1.5 3 3 1.5-3 1.5-1.5 3-1.5-3-3-1.5 3-1.5z" fill="#F59E0B"/></svg>'
	};

	/* ---------- Darstellung ---------- */

	function renderItem(it, i) {
		var running = !!it.runStart;
		var t = live(it);
		var paused = !running && !it.done && t > 0;
		var state = it.done ? 'is-done' : (running ? 'is-running' : (paused ? 'is-paused' : ''));
		var meta = 'Antippen zum Starten';
		if (it.done) { meta = it.start + ' – ' + it.end; }
		else if (running) { meta = 'läuft · gestartet ' + it.start; }
		else if (paused) { meta = 'pausiert · antippen zum Fortsetzen'; }
		var time = (running || paused) ? clock(t) : (it.done ? dur(t).slice(0, 5) : ''); // Liste kurz (hh:mm), der Text mit Sekunden
		var icon = it.done ? ICON.check : (running ? ICON.pause : ICON.play);

		var side = it.done
			? '<button type="button" class="fl-check is-on is-done" data-action="reopen" data-idx="' + i + '" aria-label="Wieder öffnen: ' + esc(it.title) + '" title="Wieder öffnen">' + ICON.check + '</button>'
			: '<button type="button" class="fl-check' + ((running || paused) ? ' is-on' : '') + '" data-action="check" data-idx="' + i + '" aria-label="Erledigt: ' + esc(it.title) + '" title="Erledigt">' + ICON.check + '</button>';

		var quote = (!it.done && it.quote && (running || paused))
			? '<div class="fl-quote">' + ICON.spark + '<span><span class="fl-quote-label">Dein Coach</span><span class="fl-quote-text">' + esc(it.quote) + '</span></span></div>'
			: '';

		return '<li class="fl-item ' + state + '"><div class="fl-row">' +
			'<button type="button" class="fl-toggle" data-action="toggle" data-idx="' + i + '"' + (it.done ? ' disabled' : '') +
			' aria-label="' + (running ? 'Pausieren: ' : 'Starten: ') + esc(it.title) + '">' +
			'<span class="fl-icon">' + icon + '</span>' +
			'<span class="fl-text"><span class="fl-name">' + esc(it.title) + '</span><span class="fl-meta">' + esc(meta) + '</span></span>' +
			'<span class="fl-time" data-live-time="' + i + '">' + time + '</span>' +
			'</button>' + side + '</div>' + quote + '</li>';
	}

	function renderToday() {
		var done = S.items.filter(function (i) { return i.done; }).length;
		var total = S.items.length;
		var focus = S.items.reduce(function (a, i) { return a + live(i); }, 0);
		var main;

		if (S.editing) {
			main = '<div class="fl-edit">' +
				'<label for="fl-plan">Eine Zeile pro To-do, beginnend mit „- “. Zeilen mit {start|ende|dauer} gelten als erledigt.</label>' +
				'<textarea id="fl-plan" rows="14" placeholder="- Spazieren gehen&#10;- Bett machen&#10;- Briefe öffnen">' + esc(S.planText) + '</textarea>' +
				'<div class="fl-actions">' +
				'<button type="button" class="fl-btn fl-btn-primary" data-action="apply">Plan übernehmen</button>' +
				'<button type="button" class="fl-btn" data-action="yesterday">Offene von gestern holen</button>' +
				(total ? '<button type="button" class="fl-btn" data-action="cancel-edit">Abbrechen</button>' : '') +
				'</div><p class="fl-hint" data-yesterday-hint></p></div>';
		} else {
			var brk = '';
			if (S.brk && S.items[S.brk.next]) {
				var left = (S.brk.until - Date.now()) / 1000;
				brk = '<div class="fl-break" role="status"><div><div class="fl-break-title" data-live-break-title>' +
					(left > 0 ? 'Pause – gut gemacht.' : 'Pause vorbei – weiter geht’s.') + '</div>' +
					'<div class="fl-break-next">Danach: ' + esc(S.items[S.brk.next].title) + '</div></div>' +
					'<div class="fl-break-right"><span class="fl-break-time" data-live-break>' + clock(left) + '</span>' +
					'<button type="button" class="fl-btn fl-btn-warm" data-action="start-next">Weiter starten</button></div></div>';
			}
			main = '<div class="fl-progress"><div class="fl-progress-row"><span>' + done + ' von ' + total + ' erledigt</span><span data-live-focus>' + mins(focus) + ' Fokus</span></div>' +
				'<div class="fl-bar"><div style="width:' + (total ? Math.round(done / total * 100) : 0) + '%"></div></div></div>' +
				brk +
				'<ul class="fl-list">' + S.items.map(renderItem).join('') + '</ul>' +
				'<p class="fl-hint">Antippen = starten bzw. pausieren. Der Haken rechts (erscheint beim Drüberfahren) hakt ein To-do ab – auch ohne Timer.</p>';
		}

		return '<div class="fl-grid">' +
			'<section class="fl-card fl-main" aria-labelledby="fl-plan-h">' +
			'<div class="fl-card-head"><h3 id="fl-plan-h">Tagesplan</h3>' +
			(!S.editing ? '<button type="button" class="fl-btn" data-action="edit">Plan bearbeiten</button>' : '') + '</div>' +
			main + '</section>' +
			'<div class="fl-side">' +
			'<section class="fl-card" aria-labelledby="fl-set-h"><h3 id="fl-set-h">Einstellungen</h3>' +
			'<div class="fl-setting"><label for="fl-pause">Pause zwischen To-dos</label><span><input id="fl-pause" type="number" min="0" max="120" value="' + S.pause + '"> min</span></div>' +
			'<p class="fl-hint">Die Pause hat ein festes Ende – so wird aus 5 Minuten Handy keine Stunde.</p></section>' +
			'<section class="fl-card fl-dark" aria-labelledby="fl-file-h"><div class="fl-card-head"><h3 id="fl-file-h">Gespeichert als Text</h3>' +
			'<span class="fl-actions"><button type="button" class="fl-btn fl-btn-ghost" data-action="copy">Kopieren</button>' +
			'<button type="button" class="fl-btn fl-btn-ghost" data-action="download">.txt</button></span></div>' +
			'<pre class="fl-export" data-live-export>' + esc(serialize()) + '</pre>' +
			'<p class="fl-status" data-status></p></section>' +
			'</div></div>';
	}

	function renderHistory() {
		if (!S.history) {
			return '<section class="fl-card"><p>Verlauf wird geladen …</p></section>';
		}
		var rows = S.history.slice();
		if (!rows.length) {
			return '<section class="fl-card"><p>Der Verlauf konnte nicht geladen werden.</p></section>';
		}
		var last = rows[rows.length - 1];
		if (last.date === S.date) {
			last.done = S.items.filter(function (i) { return i.done; }).length;
			last.seconds = S.items.reduce(function (a, i) { return a + live(i); }, 0);
		}
		var sum = function (a, k) { return a.reduce(function (x, r) { return x + r[k]; }, 0); };
		var cur = rows.slice(-7), prev = rows.slice(-14, -7);
		var avgT = sum(cur, 'done') / cur.length;
		var avgPrev = prev.length ? sum(prev, 'done') / prev.length : 0;
		var diff = avgT - avgPrev;
		var fmt1 = function (n) { return n.toFixed(1).replace('.', ','); };
		var max = Math.max(5, Math.max.apply(null, rows.map(function (r) { return r.done; })));
		var days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

		var bars = rows.map(function (r) {
			var d = new Date(r.date + 'T12:00:00');
			var isToday = r.date === S.date;
			return '<div class="fl-col' + (isToday ? ' is-today' : '') + '">' +
				'<span class="fl-col-val">' + r.done + '</span>' +
				'<span class="fl-col-track"><span class="fl-col-bar" style="height:' + Math.max(4, Math.round(r.done / max * 200)) + 'px"></span></span>' +
				'<span class="fl-col-day">' + (isToday ? 'Heute' : days[d.getDay()]) + '</span>' +
				'<span class="fl-col-date">' + d.getDate() + '.</span>' +
				'<span class="fl-col-min">' + Math.round(r.seconds / 60) + ' min</span></div>';
		}).join('');

		return '<div class="fl-stats">' +
			'<div class="fl-card fl-stat"><span>Heute</span><strong>' + last.done + ' To-dos</strong><span>' + mins(last.seconds) + ' Fokus</span></div>' +
			'<div class="fl-card fl-stat"><span>Ø To-dos pro Tag, letzte 7 Tage</span><strong>' + fmt1(avgT) + '</strong><span class="fl-accent">' + (diff >= 0 ? '+' : '') + fmt1(diff) + ' gegenüber Vorwoche</span></div>' +
			'<div class="fl-card fl-stat"><span>Ø Fokuszeit pro Tag, letzte 7 Tage</span><strong>' + mins(sum(cur, 'seconds') / cur.length) + '</strong><span>Vorwoche: ' + (prev.length ? mins(sum(prev, 'seconds') / prev.length) : '–') + '</span></div>' +
			'</div>' +
			'<section class="fl-card" aria-labelledby="fl-chart-h"><h3 id="fl-chart-h">Erledigte To-dos pro Tag · 14 Tage</h3>' +
			'<div class="fl-chart-wrap"><div class="fl-chart" role="img" aria-label="Erledigte To-dos der letzten 14 Tage">' + bars + '</div></div>' +
			'<p class="fl-hint">Leere Tage sind Pausen, kein Rückschritt. Was zählt, ist die Linie über Wochen.</p></section>';
	}

	function render(focusSel) {
		if (!S.loaded) { return; }
		var dateLabel = new Date(S.date + 'T12:00:00').toLocaleDateString('de-AT', { weekday: 'long', day: 'numeric', month: 'long' });
		root.innerHTML =
			'<header class="fl-head"><div>' +
			'<div class="fl-kicker">Fokus-Log · ' + esc(dateLabel) + '</div>' +
			'<h2 class="fl-title">Hallo ' + esc(cfg.name || '') + '</h2>' +
			'<p class="fl-sub">Offen heißt nicht versagt – es heißt: noch dran.</p></div>' +
			'<nav class="fl-tabs" aria-label="Ansicht">' +
			'<button type="button" data-action="tab" data-tab="heute" aria-pressed="' + (S.tab === 'heute') + '">Heute</button>' +
			'<button type="button" data-action="tab" data-tab="verlauf" aria-pressed="' + (S.tab === 'verlauf') + '">Verlauf</button>' +
			'</nav></header>' +
			(S.loadError ? '<p class="fl-error">Der Server war nicht erreichbar – du arbeitest mit der lokalen Kopie.</p>' : '') +
			(S.notice ? '<p class="fl-error">' + esc(S.notice) + '</p>' : '') +
			(S.tab === 'heute' ? renderToday() : renderHistory());

		if (focusSel) {
			var el = root.querySelector(focusSel);
			if (el) { el.focus({ preventScroll: true }); }
		}
	}

	/* ---------- Ereignisse ---------- */

	root.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-action]');
		if (!btn || !root.contains(btn)) { return; }
		var a = btn.getAttribute('data-action');
		var i = parseInt(btn.getAttribute('data-idx'), 10);
		S.notice = '';

		switch (a) {
			case 'toggle': toggle(i); refocus('toggle', i); break;
			case 'check': check(i); refocus('reopen', i); break;
			case 'reopen': reopen(i); refocus('check', i); break;
			case 'start-next': if (S.brk) { var n = nextOpen(S.brk.next); if (n > -1) { toggle(n); refocus('toggle', n); } else { S.brk = null; render(); } } break;
			case 'edit': S.planText = serialize(); S.editing = true; render('#fl-plan'); break;
			case 'cancel-edit': S.editing = false; render(); break;
			case 'apply': applyPlan(); break;
			case 'yesterday': loadYesterday(); break;
			case 'copy': copyText(btn); break;
			case 'download': downloadText(); break;
			case 'tab':
				S.tab = btn.getAttribute('data-tab');
				if (S.tab === 'verlauf') { S.history = null; loadHistory(); }
				render('[data-tab="' + S.tab + '"]');
				break;
		}
	});

	function refocus(action, i) {
		var el = root.querySelector('[data-action="' + action + '"][data-idx="' + i + '"]');
		if (el && !el.disabled) { el.focus({ preventScroll: true }); }
	}

	root.addEventListener('input', function (e) {
		if (e.target.id === 'fl-plan') { S.planText = e.target.value; }
	});

	root.addEventListener('change', function (e) {
		if (e.target.id === 'fl-pause') {
			S.pause = Math.max(0, Math.min(120, parseInt(e.target.value, 10) || 0));
			e.target.value = S.pause;
			api('settings', { method: 'POST', body: { pause: S.pause } }).catch(function () {});
		}
	});

	root.addEventListener('keydown', function (e) {
		if (e.target.id === 'fl-plan' && e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
			e.preventDefault();
			applyPlan();
		}
	});

	window.addEventListener('pagehide', function () {
		// Nur speichern, wenn der Server etwas noch nicht hat.
		if (S.loaded && S.dirty) {
			persistLocal();
			saveNow(true);
		}
	});

	/* ---------- Sekundentakt ---------- */

	setInterval(function () {
		if (!S.loaded) { return; }
		var runIdx = S.items.findIndex(function (i) { return i.runStart; });

		// Neuer Tag: wechseln, sobald nichts läuft und alles gespeichert ist. Ein Timer, der
		// über Mitternacht läuft, bleibt beim Tag, an dem er begonnen hat.
		var todayKey = dateKey(new Date());
		if (todayKey !== S.date && runIdx < 0 && !S.dirty && !saving && !S.editing) {
			switchDay(todayKey);
			return;
		}

		if (runIdx > -1) {
			var it = S.items[runIdx];
			var t = root.querySelector('[data-live-time="' + runIdx + '"]');
			if (t) { t.textContent = clock(live(it)); }
			var f = root.querySelector('[data-live-focus]');
			if (f) { f.textContent = mins(S.items.reduce(function (a, i) { return a + live(i); }, 0)) + ' Fokus'; }
			var ex = root.querySelector('[data-live-export]');
			if (ex) { ex.textContent = serialize(); }
			document.title = '▶ ' + clock(live(it)) + ' · ' + it.title;
			if (Date.now() - lastSave > 60000) { saveNow(); }
		} else if (S.brk) {
			var left = (S.brk.until - Date.now()) / 1000;
			var b = root.querySelector('[data-live-break]');
			if (b) { b.textContent = clock(left); }
			document.title = left > 0 ? 'Pause ' + clock(left) : 'Pause vorbei!';
			if (left <= 0 && !S.brk.over) {
				S.brk.over = true;
				beep();
				var bt = root.querySelector('[data-live-break-title]');
				if (bt) { bt.textContent = 'Pause vorbei – weiter geht’s.'; }
			}
		} else if (document.title !== originalTitle) {
			document.title = originalTitle;
		}
	}, 1000);

	/* ---------- Start ---------- */

	function restoreLocal(local) {
		if (!local) { return; }
		if (local.run) {
			var it = S.items[local.run.idx];
			if (it && !it.done && it.title === local.run.title) {
				it.acc = local.run.acc;
				it.runStart = local.run.runStart;
				it.quote = runQuote(it.title);
			}
		}
		if (local.brk && S.items[local.brk.next] && local.brk.until > Date.now() - 30 * 60000) {
			S.brk = { until: local.brk.until, next: local.brk.next, over: local.brk.until <= Date.now() };
		}
	}

	/*
	 * Lädt den Tag. Die lokale Kopie gewinnt nur, wenn sie Ungespeichertes enthält und der
	 * Server seither nicht von einem anderen Gerät geändert wurde (gleiche Revision), oder
	 * wenn der Server nicht erreichbar ist.
	 */
	function loadDay() {
		S.loaded = false;
		S.loadError = false;
		return api('day', { params: { date: S.date } })
			.catch(function () { S.loadError = true; return null; })
			.then(function (r) {
				var local = readLocal();
				var content = r ? (r.content || '') : '';
				var rev = r ? (r.rev || 0) : 0;
				var useLocal = !!(local && local.text) && (
					!r ||
					(local.saved === false && (local.rev === rev || !r.exists))
				);
				if (useLocal) {
					content = local.text;
					rev = typeof local.rev === 'number' ? local.rev : rev;
				}
				S.items = parse(content);
				S.rev = rev;
				S.dirty = useLocal && local.saved === false;
				restoreLocal(local);
				cleanLocal();
				S.planText = content;
				S.editing = S.items.length === 0;
				S.loaded = true;
				render();
				if (S.dirty && !S.loadError) { saveNow(); }
			});
	}

	// Lokale Kopien älter als 14 Tage entfernen.
	function cleanLocal() {
		try {
			var prefix = 'fokuslog:' + cfg.userId + ':';
			var limit = new Date(S.date + 'T12:00:00');
			limit.setDate(limit.getDate() - 14);
			var oldest = dateKey(limit);
			for (var k = localStorage.length - 1; k >= 0; k--) {
				var key = localStorage.key(k);
				if (key && key.indexOf(prefix) === 0 && key.slice(prefix.length) < oldest) { localStorage.removeItem(key); }
			}
		} catch (e) { /* privater Modus */ }
	}

	function switchDay(key) {
		S.date = key;
		S.items = [];
		S.brk = null;
		S.rev = 0;
		S.dirty = false;
		S.notice = '';
		S.history = null;
		loadDay();
	}

	// Zurück in der App (anderer Tab, Handy entsperrt): neuen Stand eines anderen Geräts holen.
	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState !== 'visible' || !S.loaded || S.dirty || saving || S.editing) { return; }
		var date = S.date;
		api('day', { params: { date: date } }).then(function (r) {
			if (date === S.date && !S.dirty && !S.editing && (r.rev || 0) !== S.rev) {
				adopt(r, '');
			}
		}).catch(function () {});
	});

	loadDay();
})();
