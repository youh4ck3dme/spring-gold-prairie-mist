<?php
/**
 * Admin interface and settings for Vorkei Statement Bridge.
 *
 * @package    Vorkei_Statement_Bridge
 * @subpackage Vorkei_Statement_Bridge/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Admin {

	/**
	 * Hook admin actions.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register menu and submenus.
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'Vorkei Bridge', 'vorkei-bridge' ),
			__( 'Vorkei Bridge', 'vorkei-bridge' ),
			'manage_options',
			'vorkei-bridge',
			[ $this, 'render_dashboard_page' ],
			'dashicons-money-alt',
			58
		);

		add_submenu_page(
			'vorkei-bridge',
			__( 'Prehľad a Výpisy', 'vorkei-bridge' ),
			__( 'Prehľad a Výpisy', 'vorkei-bridge' ),
			'manage_options',
			'vorkei-bridge',
			[ $this, 'render_dashboard_page' ]
		);

		add_submenu_page(
			'vorkei-bridge',
			__( 'Nastavenia', 'vorkei-bridge' ),
			__( 'Nastavenia', 'vorkei-bridge' ),
			'manage_options',
			'vorkei-bridge-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Enqueue admin CSS and JS.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'vorkei-bridge' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'vorkei-admin-style',
			plugins_url( 'css/vorkei-admin.css', __FILE__ ),
			[],
			'1.0.0'
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings(): void {
		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_enable_wc_matching', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_wc_target_status', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'processing',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_verify_amount', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_add_order_note', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_api_token', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_notify_discrepancy', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_delete_on_uninstall', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'no',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_update_url', [
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => 'https://api.vorkei.com/plugins/update-check',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_license_key', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );
	}

	/**
	 * Render Dashboard page (Statements and stats).
	 */
	public function render_dashboard_page(): void {
		global $wpdb;

		$statements_table = $wpdb->prefix . 'vorkei_statements';
		$movements_table  = $wpdb->prefix . 'vorkei_movements';

		$st_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $statements_table" );
		$mv_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $movements_table" );
		$matched_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $movements_table WHERE matched_order_id IS NOT NULL" );

		$recent_statements = $wpdb->get_results( "SELECT * FROM $statements_table ORDER BY created_at DESC LIMIT 10", ARRAY_A );
		$recent_movements  = $wpdb->get_results( "SELECT * FROM $movements_table ORDER BY created_at DESC LIMIT 15", ARRAY_A );

		$rest_url = rest_url( 'vorkei/v1/sync' );
		?>
		<div class="wrap vorkei-admin-wrap">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-money-alt" style="font-size:28px;width:30px;height:30px;line-height:1.2;"></span>
				<?php esc_html_e( 'Vorkei Statement Bridge — Prehľad', 'vorkei-bridge' ); ?>
			</h1>
			<p class="description">
				<?php esc_html_e( 'Synchronizačný mostík pre bankové výpisy a automatické párovanie platieb s WooCommerce.', 'vorkei-bridge' ); ?>
			</p>
			<hr class="wp-header-end">

			<!-- Stat Cards -->
			<div class="vorkei-stats-grid">
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Celkovo výpisov', 'vorkei-bridge' ); ?></h3>
					<div class="number"><?php echo esc_html( (string) $st_count ); ?></div>
				</div>
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Evidovaných pohybov', 'vorkei-bridge' ); ?></h3>
					<div class="number"><?php echo esc_html( (string) $mv_count ); ?></div>
				</div>
				<div class="vorkei-stat-card highlight">
					<h3><?php esc_html_e( 'Spárované objednávky', 'vorkei-bridge' ); ?></h3>
					<div class="number"><?php echo esc_html( (string) $matched_count ); ?></div>
				</div>
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'WooCommerce integrácia', 'vorkei-bridge' ); ?></h3>
					<div class="status-indicator">
						<?php if ( function_exists( 'wc_get_order' ) ) : ?>
							<span class="badge badge-success">✓ <?php esc_html_e( 'Aktívna', 'vorkei-bridge' ); ?></span>
						<?php else : ?>
							<span class="badge badge-warning">⚠ <?php esc_html_e( 'WC nenájdené', 'vorkei-bridge' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- API Endpoint Box -->
			<div class="vorkei-card" style="margin-top:20px;">
				<h2><?php esc_html_e( '🔗 REST API Endpoint pre aplikáciu Výpis (pdf-t3.vorkei)', 'vorkei-bridge' ); ?></h2>
				<p><?php esc_html_e( 'Zadajte túto adresu a vaše prihlasovacie údaje do synchronizačného dialógu:', 'vorkei-bridge' ); ?></p>
				<div class="vorkei-endpoint-box" style="margin-bottom:12px;">
					<code>POST <?php echo esc_url( $rest_url ); ?></code>
				</div>
				<div style="margin-top:8px;">
					<a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>" class="button button-secondary" target="_blank">
						<span class="dashicons dashicons-admin-network" style="vertical-align:middle;margin-top:-2px;"></span>
						<?php esc_html_e( 'Vytvoriť Aplikačné heslo vo Vašom profile ➔', 'vorkei-bridge' ); ?>
					</a>
					<?php if ( function_exists( 'wc_get_logger' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) ); ?>" class="button button-link" target="_blank" style="margin-left:12px;">
							<?php esc_html_e( 'Zobraziť WooCommerce audit log ➔', 'vorkei-bridge' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Recent Statements & Movements -->
			<div class="vorkei-columns" style="display:flex;gap:20px;margin-top:20px;flex-wrap:wrap;">
				<div style="flex:1;min-width:320px;" class="vorkei-card">
					<h2><?php esc_html_e( 'Posledné synchronizované výpisy', 'vorkei-bridge' ); ?></h2>
					<?php if ( empty( $recent_statements ) ) : ?>
						<p><?php esc_html_e( 'Zatiaľ neboli synchronizované žiadne výpisy.', 'vorkei-bridge' ); ?></p>
					<?php else : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Číslo výpisu', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'IBAN', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'Konečný zostatok', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'Vytvorené', 'vorkei-bridge' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_statements as $st ) : ?>
									<tr>
										<td><strong>#<?php echo esc_html( $st['statement_number'] ); ?></strong></td>
										<td><code><?php echo esc_html( $st['account_iban'] ?: '-' ); ?></code></td>
										<td><?php echo esc_html( number_format( $st['closing_balance'] / 100, 2, ',', ' ' ) . ' ' . $st['currency'] ); ?></td>
										<td><?php echo esc_html( $st['created_at'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<div style="flex:1;min-width:320px;" class="vorkei-card">
					<h2><?php esc_html_e( 'Posledné prijaté platby a pohyby', 'vorkei-bridge' ); ?></h2>
					<?php if ( empty( $recent_movements ) ) : ?>
						<p><?php esc_html_e( 'Zatiaľ neboli zaevidované žiadne pohyby.', 'vorkei-bridge' ); ?></p>
					<?php else : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Dátum', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'Suma', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'VS', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'Objednávka', 'vorkei-bridge' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_movements as $mv ) : ?>
									<tr>
										<td><?php echo esc_html( $mv['date'] ); ?></td>
										<td>
											<span style="font-weight:600;color:<?php echo 'in' === $mv['kind'] ? '#16a34a' : '#dc2626'; ?>;">
												<?php echo 'in' === $mv['kind'] ? '+' : '-'; ?>
												<?php echo esc_html( number_format( $mv['amount_cents'] / 100, 2, ',', ' ' ) ); ?> €
											</span>
										</td>
										<td><code><?php echo esc_html( $mv['variable_symbol'] ?: '-' ); ?></code></td>
										<td>
											<?php if ( ! empty( $mv['matched_order_id'] ) ) : ?>
												<a href="<?php echo esc_url( get_edit_post_link( $mv['matched_order_id'] ) ); ?>" target="_blank">
													#<?php echo esc_html( (string) $mv['matched_order_id'] ); ?> (Spárované)
												</a>
											<?php else : ?>
												<span style="color:#64748b;">—</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Settings page.
	 */
	public function render_settings_page(): void {
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : [
			'wc-processing' => 'Spracováva sa (Processing)',
			'wc-completed'  => 'Vybavená (Completed)',
		];
		?>
		<div class="wrap vorkei-admin-wrap">
			<h1><?php esc_html_e( 'Vorkei Bridge — Nastavenia', 'vorkei-bridge' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'vorkei_bridge_settings_group' );
				do_settings_sections( 'vorkei_bridge_settings_group' );
				?>

				<div class="vorkei-card" style="margin-top:20px;">
					<h2><?php esc_html_e( 'Pravidlá automatického párovania WooCommerce', 'vorkei-bridge' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_enable_wc_matching"><?php esc_html_e( 'Automatické párovanie', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<select name="vorkei_bridge_enable_wc_matching" id="vorkei_bridge_enable_wc_matching">
									<option value="yes" <?php selected( get_option( 'vorkei_bridge_enable_wc_matching', 'yes' ), 'yes' ); ?>>
										<?php esc_html_e( 'Povolené (Hľadať objednávku podľa variabilného symbolu)', 'vorkei-bridge' ); ?>
									</option>
									<option value="no" <?php selected( get_option( 'vorkei_bridge_enable_wc_matching', 'yes' ), 'no' ); ?>>
										<?php esc_html_e( 'Vypnuté (Iba ukladať pohyby do databázy)', 'vorkei-bridge' ); ?>
									</option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="vorkei_bridge_wc_target_status"><?php esc_html_e( 'Cieľový stav objednávky', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<select name="vorkei_bridge_wc_target_status" id="vorkei_bridge_wc_target_status">
									<option value="none" <?php selected( get_option( 'vorkei_bridge_wc_target_status' ), 'none' ); ?>>
										<?php esc_html_e( 'Nemeniť stav (iba pridať poznámku)', 'vorkei-bridge' ); ?>
									</option>
									<?php foreach ( $statuses as $slug => $label ) : 
										$clean_slug = str_replace( 'wc-', '', $slug );
									?>
										<option value="<?php echo esc_attr( $clean_slug ); ?>" <?php selected( get_option( 'vorkei_bridge_wc_target_status', 'processing' ), $clean_slug ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Stav, do ktorého sa objednávka prepne po úspešnom overení prijatej platby.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="vorkei_bridge_verify_amount"><?php esc_html_e( 'Striktná kontrola sumy', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<select name="vorkei_bridge_verify_amount" id="vorkei_bridge_verify_amount">
									<option value="yes" <?php selected( get_option( 'vorkei_bridge_verify_amount', 'yes' ), 'yes' ); ?>>
										<?php esc_html_e( 'Áno (Platba sa spáruje iba ak presne sedí suma v centoch)', 'vorkei-bridge' ); ?>
									</option>
									<option value="no" <?php selected( get_option( 'vorkei_bridge_verify_amount', 'yes' ), 'no' ); ?>>
										<?php esc_html_e( 'Nie (Spárovať aj pri čiastočnej / odlišnej sume)', 'vorkei-bridge' ); ?>
									</option>
								</select>
								<p class="description"><?php esc_html_e( 'Ak sa suma nezhoduje a je zapnutá kontrola, pridá sa varovná poznámka a stav sa automaticky nezmení.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="vorkei_bridge_notify_discrepancy"><?php esc_html_e( 'Emailové upozornenie pri nezrovnalosti', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<select name="vorkei_bridge_notify_discrepancy" id="vorkei_bridge_notify_discrepancy">
									<option value="yes" <?php selected( get_option( 'vorkei_bridge_notify_discrepancy', 'yes' ), 'yes' ); ?>>
										<?php esc_html_e( 'Odoslať email administrátorovi eshopu pri nedoplatku', 'vorkei-bridge' ); ?>
									</option>
									<option value="no" <?php selected( get_option( 'vorkei_bridge_notify_discrepancy', 'yes' ), 'no' ); ?>>
										<?php esc_html_e( 'Neodosielať email', 'vorkei-bridge' ); ?>
									</option>
								</select>
								<p class="description"><?php esc_html_e( 'Ak zákazník pošle nesprávnu sumu, administrátor dostane notifikáciu s detailmi a odkazom na objednávku.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="vorkei_bridge_add_order_note"><?php esc_html_e( 'Interná poznámka v objednávke', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<select name="vorkei_bridge_add_order_note" id="vorkei_bridge_add_order_note">
									<option value="yes" <?php selected( get_option( 'vorkei_bridge_add_order_note', 'yes' ), 'yes' ); ?>>
										<?php esc_html_e( 'Pridávať detailnú poznámku s IBANom a číslom výpisu', 'vorkei-bridge' ); ?>
									</option>
									<option value="no" <?php selected( get_option( 'vorkei_bridge_add_order_note', 'yes' ), 'no' ); ?>>
										<?php esc_html_e( 'Nepridávať poznámku', 'vorkei-bridge' ); ?>
									</option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<div class="vorkei-card" style="margin-top:20px;">
					<h2><?php esc_html_e( 'Zabezpečenie & Autentifikácia API', 'vorkei-bridge' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_api_token"><?php esc_html_e( 'Voliteľný tajný Bearer API kľúč', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="password" name="vorkei_bridge_api_token" id="vorkei_bridge_api_token" value="<?php echo esc_attr( get_option( 'vorkei_bridge_api_token', '' ) ); ?>" class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Ponechajte prázdne, ak používate štandardné WordPress Aplikačné heslá (Application Passwords v profile používateľa). Ak sem zadáte reťazec, aplikácia sa môže autentifikovať hlavičkou "Authorization: Bearer <kluc>".', 'vorkei-bridge' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="vorkei_bridge_delete_on_uninstall"><?php esc_html_e( 'Odstránenie dát pri odinštalovaní', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<select name="vorkei_bridge_delete_on_uninstall" id="vorkei_bridge_delete_on_uninstall">
									<option value="no" <?php selected( get_option( 'vorkei_bridge_delete_on_uninstall', 'no' ), 'no' ); ?>>
										<?php esc_html_e( 'Ponechať tabuľky a históriu (Bezpečné)', 'vorkei-bridge' ); ?>
									</option>
									<option value="yes" <?php selected( get_option( 'vorkei_bridge_delete_on_uninstall', 'no' ), 'yes' ); ?>>
										<?php esc_html_e( 'Zmazať tabuľky z databázy pri vymazaní pluginu', 'vorkei-bridge' ); ?>
									</option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<div class="vorkei-card" style="margin-top:20px;">
					<h2><?php esc_html_e( 'Automatické aktualizácie (Auto-Updates Server)', 'vorkei-bridge' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Plugin komunikuje priamo s oficiálnym aktualizačným serverom vorkei.com. Nové verzie sa automaticky zobrazujú v zozname pluginov s možnosťou aktualizácie na jeden klik.', 'vorkei-bridge' ); ?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Nainštalovaná verzia', 'vorkei-bridge' ); ?></th>
							<td>
								<strong>v<?php echo esc_html( defined( 'VORKEI_BRIDGE_VERSION' ) ? VORKEI_BRIDGE_VERSION : '1.0.0' ); ?></strong>
								&nbsp;
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'vorkei_force_update_check', '1' ), 'vorkei_check_update_nonce' ) ); ?>" class="button button-secondary">
									<span class="dashicons dashicons-update" style="line-height:1.4;"></span>
									<?php esc_html_e( 'Skontrolovať dostupnosť novej verzie teraz', 'vorkei-bridge' ); ?>
								</a>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_update_url"><?php esc_html_e( 'Aktualizačný server (URL)', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="url" name="vorkei_bridge_update_url" id="vorkei_bridge_update_url" value="<?php echo esc_attr( get_option( 'vorkei_bridge_update_url', 'https://api.vorkei.com/plugins/update-check' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Predvolená adresa je https://api.vorkei.com/plugins/update-check.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_license_key"><?php esc_html_e( 'Licenčný kľúč / Client ID', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="text" name="vorkei_bridge_license_key" id="vorkei_bridge_license_key" value="<?php echo esc_attr( get_option( 'vorkei_bridge_license_key', '' ) ); ?>" class="regular-text" placeholder="voliteľný licenčný token klienta">
								<p class="description"><?php esc_html_e( 'Používa sa na autentifikáciu klientskych inštalácií pri sťahovaní prémiových aktualizácií z vorkei.com.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button( __( 'Uložiť nastavenia', 'vorkei-bridge' ) ); ?>
			</form>
		</div>
		<?php
	}
}
