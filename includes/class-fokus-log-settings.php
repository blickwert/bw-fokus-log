<?php
/**
 * Einstellungen: Einstellungen › Fokus-Log
 * – Standard-Pause zwischen To-dos
 * – Coach-Sprüche (eine Zeile pro Spruch, Platzhalter {t} {d} {n} {total})
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fokus_Log_Settings {

	const OPTION = 'fokus_log_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function defaults() {
		return array(
			'pause'    => 5,
			'run'      => implode(
				"\n",
				array(
					'Du hast angefangen – das war der schwerste Teil. Der Rest ist Formsache.',
					'Handy umdrehen, Fokus an. Ich pass auf die Zeit auf.',
					'Kleiner Schritt, große Wirkung. Weiter so!',
					'Die Social-Media-Welt kommt ein paar Minuten ohne dich aus. Versprochen.',
					'Spontane Idee? Kurz merken, später feiern. Jetzt zählt nur das hier.',
					'Du bist gerade das Gegenteil von „mach ich gleich“: Du machst es.',
					'Nicht perfekt – nur fertig. Das reicht völlig.',
					'„{t}“ zittert schon. Es weiß, dass du kommst.',
					'Jede Minute hier ist eine Minute weniger Last im Kopf.',
					'Fokus-Modus: aktiviert. Ausreden-Modus: offline.',
					'Wie beim Radeln: gleichmäßig treten, nicht sprinten.',
					'Der Timer läuft – und du läufst mit. Stark!',
					'Erst machen, dann denken. Heute mal andersrum.',
					'Du sammelst gerade Beweise, dass du’s kannst.',
					'Einfach dranbleiben. Ich sag Bescheid, wenn’s Party gibt.',
					'Gerade baust du Schwung auf. Nicht bremsen!',
					'Wer „{t}“ startet, hat schon halb gewonnen.',
					'Kurz durchatmen, Schultern locker – du hast das im Griff.',
				)
			),
			'titles'   => "Geschafft!\nZack, erledigt!\nBoom!\nLäuft bei dir!\nAbgehakt!\nYes!\nStark!",
			'done'     => implode(
				"\n",
				array(
					'„{t}“ ist Geschichte. Ab auf die Erfolgsliste!',
					'Wieder eins weniger im Kopf. Fühlt sich gut an, oder?',
					'Das war kein To-do, das war ein Statement.',
					'Erledigt in {d}. Saubere Arbeit!',
					'Du hast Wort gehalten – dir selbst gegenüber. Das zählt am meisten.',
					'High five! Gönn dir die Pause, du hast sie verdient.',
					'Schwung mitnehmen – das nächste wird leichter.',
					'Ein Haken mehr. Dein Zukunfts-Ich bedankt sich.',
					'Siehst du? Gar nicht so schlimm, wie es sich angefühlt hat.',
					'Die To-do-Liste hat gerade Respekt vor dir.',
					'{n} von {total} geschafft. Läuft!',
					'Genau so geht das: einfach machen.',
				)
			),
			'all_done' => 'Der ganze Plan ist abgehakt. Heute bist du der Boss – feier dich!',
		);
	}

	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function lines( $text ) {
		$lines = array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) );
		return array_values( array_filter( $lines, 'strlen' ) );
	}

	/** Pause des aktuellen Benutzers (in der App änderbar), sonst Standard. */
	public static function user_pause( $user_id ) {
		$value = get_user_meta( $user_id, 'fokus_log_pause', true );
		if ( '' === $value ) {
			$s = self::get();
			return (int) $s['pause'];
		}
		return (int) $value;
	}

	public static function menu() {
		add_options_page( 'Fokus-Log', 'Fokus-Log', 'manage_options', 'fokus-log-settings', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			'fokus_log',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize( $input ) {
		$d   = self::defaults();
		$out = array();

		$out['pause'] = isset( $input['pause'] ) ? max( 0, min( 120, (int) $input['pause'] ) ) : $d['pause'];
		foreach ( array( 'run', 'titles', 'done' ) as $key ) {
			$value       = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : '';
			$out[ $key ] = '' !== trim( $value ) ? $value : $d[ $key ];
		}
		$all             = isset( $input['all_done'] ) ? sanitize_text_field( $input['all_done'] ) : '';
		$out['all_done'] = '' !== $all ? $all : $d['all_done'];
		return $out;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::get();
		$n = self::OPTION;
		?>
		<div class="wrap">
			<h1>Fokus-Log – Einstellungen</h1>
			<p>Die App erscheint im Menü <strong>Fokus-Log</strong> und auf jeder Seite mit dem Shortcode <code>[fokus_log]</code>.</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'fokus_log' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fl-pause">Standard-Pause zwischen To-dos</label></th>
						<td><input id="fl-pause" type="number" min="0" max="120" name="<?php echo esc_attr( $n ); ?>[pause]" value="<?php echo esc_attr( $s['pause'] ); ?>" class="small-text"> Minuten
						<p class="description">Jeder Benutzer kann den Wert in der App für sich ändern.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="fl-run">Coach-Sprüche während der Arbeit</label></th>
						<td><textarea id="fl-run" name="<?php echo esc_attr( $n ); ?>[run]" rows="10" class="large-text"><?php echo esc_textarea( $s['run'] ); ?></textarea>
						<p class="description">Ein Spruch pro Zeile. <code>{t}</code> = Name des To-dos.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="fl-titles">Überschriften beim Abhaken</label></th>
						<td><textarea id="fl-titles" name="<?php echo esc_attr( $n ); ?>[titles]" rows="5" class="large-text"><?php echo esc_textarea( $s['titles'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="fl-done">Sprüche beim Abhaken</label></th>
						<td><textarea id="fl-done" name="<?php echo esc_attr( $n ); ?>[done]" rows="10" class="large-text"><?php echo esc_textarea( $s['done'] ); ?></textarea>
						<p class="description">Platzhalter: <code>{t}</code> To-do, <code>{d}</code> Dauer, <code>{n}</code> erledigt, <code>{total}</code> gesamt.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="fl-all">Spruch, wenn alles erledigt ist</label></th>
						<td><input id="fl-all" type="text" name="<?php echo esc_attr( $n ); ?>[all_done]" value="<?php echo esc_attr( $s['all_done'] ); ?>" class="large-text"></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
