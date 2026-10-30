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
		add_action( 'admin_init', [ $this, 'handle_billing_actions' ] );
		add_action( 'admin_init', [ $this, 'handle_wallet_actions' ] );
		add_action( 'admin_init', [ $this, 'handle_stripe_actions' ] );
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
			__( 'Fakturačný podklad (Audit & Provízie)', 'vorkei-bridge' ),
			__( 'Fakturačný podklad', 'vorkei-bridge' ),
			'manage_options',
			'vorkei-bridge-billing',
			[ $this, 'render_billing_page' ]
		);

		add_submenu_page(
			'vorkei-bridge',
			__( 'Partneri & Kredity (Paywall)', 'vorkei-bridge' ),
			__( 'Partneri & Kredity', 'vorkei-bridge' ),
			'manage_options',
			'vorkei-bridge-wallets',
			[ $this, 'render_wallets_page' ]
		);

		add_submenu_page(
			'vorkei-bridge',
			__( 'Stripe & Kredity', 'vorkei-bridge' ),
			__( 'Stripe Transakcie', 'vorkei-bridge' ),
			'manage_options',
			'vorkei-bridge-transactions',
			[ $this, 'render_transactions_page' ]
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
	 * Handle admin actions for lock toggle and payment status.
	 */
	public function handle_billing_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['vorkei_action'], $_GET['log_id'], $_GET['_wpnonce'] ) ) {
			$action = sanitize_text_field( $_GET['vorkei_action'] );
			$log_id = absint( $_GET['log_id'] );
			$nonce  = sanitize_text_field( $_GET['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'vorkei_billing_action_' . $log_id ) ) {
				wp_die( esc_html__( 'Bezpečnostné overenie (nonce) zlyhalo.', 'vorkei-bridge' ) );
			}

			global $wpdb;
			$table = $wpdb->prefix . 'vorkei_audit_log';

			if ( 'toggle_lock' === $action ) {
				$curr = (int) $wpdb->get_var( $wpdb->prepare( "SELECT export_unlocked FROM $table WHERE id = %d", $log_id ) );
				$new_val = $curr === 1 ? 0 : 1;
				$wpdb->update( $table, [ 'export_unlocked' => $new_val ], [ 'id' => $log_id ] );
				wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-billing', 'notice' => 'lock_updated' ], admin_url( 'admin.php' ) ) );
				exit;
			} elseif ( 'toggle_paid' === $action ) {
				$curr_status = $wpdb->get_var( $wpdb->prepare( "SELECT fee_status FROM $table WHERE id = %d", $log_id ) );
				$new_status  = 'paid' === $curr_status ? 'unpaid' : 'paid';
				$wpdb->update( $table, [ 'fee_status' => $new_status ], [ 'id' => $log_id ] );
				wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-billing', 'notice' => 'status_updated' ], admin_url( 'admin.php' ) ) );
				exit;
			}
		}
	}

	/**
	 * Handle admin actions for client partner wallets (topup, toggle, delete, paywall switch).
	 */
	public function handle_wallet_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'vorkei_client_tokens';

		// Form POST: Create new wallet
		if ( isset( $_POST['vorkei_create_wallet_submit'], $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'vorkei_create_wallet_nonce' ) ) {
				wp_die( esc_html__( 'Bezpečnostné overenie (nonce) zlyhalo.', 'vorkei-bridge' ) );
			}

			$client_name = sanitize_text_field( $_POST['client_name'] ?? '' );
			if ( empty( $client_name ) ) {
				wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'error' => 'missing_name' ], admin_url( 'admin.php' ) ) );
				exit;
			}

			$client_email       = sanitize_email( $_POST['client_email'] ?? '' );
			$credits_balance    = max( 0, (int) ( $_POST['credits_balance'] ?? 0 ) );
			$cost_per_statement = max( 1, (int) ( $_POST['cost_per_statement'] ?? 1 ) );
			$notes              = sanitize_textarea_field( $_POST['notes'] ?? '' );

			$token_key = ! empty( $_POST['token_key'] )
				? preg_replace( '/[^A-Za-z0-9_\-]/', '', sanitize_text_field( $_POST['token_key'] ) )
				: ( 'VRK-' . strtoupper( substr( md5( uniqid( (string) ( function_exists( 'wp_rand' ) ? wp_rand() : rand() ), true ) ), 0, 16 ) ) );

			$wpdb->insert( $table, [
				'token_key'             => $token_key,
				'client_name'           => $client_name,
				'client_email'          => $client_email,
				'credits_balance'       => $credits_balance,
				'cost_per_statement'    => $cost_per_statement,
				'total_statements_used' => 0,
				'total_spent_cents'     => 0,
				'is_active'             => 1,
				'notes'                 => $notes,
				'created_at'            => current_time( 'mysql' ),
			] );

			wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'notice' => 'wallet_created' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		// GET actions: Top up, Toggle active, Delete, Toggle Paywall
		if ( isset( $_GET['vorkei_wallet_action'], $_GET['_wpnonce'] ) ) {
			$action = sanitize_text_field( $_GET['vorkei_wallet_action'] );
			$nonce  = sanitize_text_field( $_GET['_wpnonce'] );

			if ( 'toggle_paywall' === $action ) {
				if ( ! wp_verify_nonce( $nonce, 'vorkei_toggle_paywall_nonce' ) ) {
					wp_die( esc_html__( 'Bezpečnostné overenie (nonce) zlyhalo.', 'vorkei-bridge' ) );
				}
				$curr = get_option( 'vorkei_bridge_enforce_credits', 'yes' );
				$new_val = ( 'yes' === $curr ) ? 'no' : 'yes';
				update_option( 'vorkei_bridge_enforce_credits', $new_val );
				wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'notice' => 'paywall_updated' ], admin_url( 'admin.php' ) ) );
				exit;
			}

			if ( isset( $_GET['wallet_id'] ) ) {
				$wallet_id = absint( $_GET['wallet_id'] );
				if ( ! wp_verify_nonce( $nonce, 'vorkei_wallet_action_' . $wallet_id ) ) {
					wp_die( esc_html__( 'Bezpečnostné overenie (nonce) zlyhalo.', 'vorkei-bridge' ) );
				}

				if ( 'topup' === $action ) {
					$credits = max( 1, (int) ( $_GET['credits'] ?? 5 ) );
					$wpdb->query( $wpdb->prepare( "UPDATE $table SET credits_balance = credits_balance + %d, updated_at = %s WHERE id = %d", $credits, current_time( 'mysql' ), $wallet_id ) );
					wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'notice' => 'topup_success', 'added' => $credits ], admin_url( 'admin.php' ) ) );
					exit;
				} elseif ( 'toggle_active' === $action ) {
					$curr = (int) $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM $table WHERE id = %d", $wallet_id ) );
					$new_val = 1 === $curr ? 0 : 1;
					$wpdb->update( $table, [ 'is_active' => $new_val ], [ 'id' => $wallet_id ] );
					wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'notice' => 'status_updated' ], admin_url( 'admin.php' ) ) );
					exit;
				} elseif ( 'delete' === $action ) {
					$wpdb->delete( $table, [ 'id' => $wallet_id ] );
					wp_safe_redirect( add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'notice' => 'wallet_deleted' ], admin_url( 'admin.php' ) ) );
					exit;
				}
			}
		}
	}

	/**
	 * Handle admin actions for Stripe settings and reconciliation.
	 */
	public function handle_stripe_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['vorkei_stripe_action'] ) && check_admin_referer( 'vorkei_stripe_nonce' ) ) {
			if ( 'reconcile_session' === $_POST['vorkei_stripe_action'] && ! empty( $_POST['session_id'] ) ) {
				$session_id = sanitize_text_field( $_POST['session_id'] );
				$controller = new Vorkei_REST_Controller();
				$result     = $controller->reconcile_stripe_session( $session_id );

				if ( ! empty( $result['success'] ) ) {
					add_settings_error( 'vorkei_stripe', 'vorkei_reconcile_success', $result['message'], 'updated' );
				} else {
					add_settings_error( 'vorkei_stripe', 'vorkei_reconcile_error', $result['message'], 'error' );
				}
			} elseif ( 'save_stripe_keys' === $_POST['vorkei_stripe_action'] ) {
				if ( isset( $_POST['stripe_webhook_secret'] ) ) {
					update_option( 'vorkei_bridge_stripe_webhook_secret', sanitize_text_field( $_POST['stripe_webhook_secret'] ) );
					update_option( 'vorkei_stripe_webhook_secret', sanitize_text_field( $_POST['stripe_webhook_secret'] ) );
				}
				if ( isset( $_POST['stripe_secret_key'] ) ) {
					update_option( 'vorkei_bridge_stripe_secret_key', sanitize_text_field( $_POST['stripe_secret_key'] ) );
					update_option( 'vorkei_stripe_secret_key', sanitize_text_field( $_POST['stripe_secret_key'] ) );
				}
				add_settings_error( 'vorkei_stripe', 'vorkei_keys_saved', __( 'Stripe kľúče boli úspešne uložené.', 'vorkei-bridge' ), 'updated' );
			}
		}
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

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_fee_base_amount', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '5.00',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_fee_movement_rate', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '0.00',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_fee_volume_percent', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '0.00',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_auto_lock_exports', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_enforce_credits', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_stripe_webhook_secret', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		register_setting( 'vorkei_bridge_settings_group', 'vorkei_bridge_stripe_secret_key', [
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

			<?php
			$audit_table = $wpdb->prefix . 'vorkei_audit_log';
			$unpaid_fees = 0;
			if ( method_exists( $wpdb, 'get_var' ) ) {
				$unpaid_fees = (int) $wpdb->get_var( "SELECT SUM(suggested_fee_cents) FROM $audit_table WHERE fee_status = 'unpaid'" );
			}
			if ( $unpaid_fees > 0 ) :
			?>
				<div class="notice notice-warning" style="margin-top:20px;padding:12px 16px;border-left:4px solid #eab308;background:#fefce8;border-radius:4px;">
					<p style="margin:0;font-size:14px;color:#854d0e;">
						<strong>🕵️‍♂️ Forenzný audit zaznamenal aktivitu partnera:</strong> V databáze evidujeme asistované spracovanie výpisov s odhadovanou províziou <strong><?php echo esc_html( number_format( $unpaid_fees / 100, 2, ',', ' ' ) ); ?> €</strong>.
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=vorkei-bridge-billing' ) ); ?>" style="font-weight:700;color:#854d0e;margin-left:8px;text-decoration:underline;">
							Zobraziť podklad k prefakturácii a správu zámkov ➔
						</a>
					</p>
				</div>
			<?php endif; ?>

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
									<th><?php esc_html_e( 'Počiatočný stav', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'Konečný zostatok', 'vorkei-bridge' ); ?></th>
									<th><?php esc_html_e( 'Vytvorené', 'vorkei-bridge' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_statements as $st ) : ?>
									<tr>
										<td><strong>#<?php echo esc_html( $st['statement_number'] ); ?></strong></td>
										<td><code><?php echo esc_html( $st['account_iban'] ?: '-' ); ?></code></td>
										<td><?php echo esc_html( number_format( $st['opening_balance'] / 100, 2, ',', ' ' ) . ' ' . $st['currency'] ); ?></td>
										<td><strong><?php echo esc_html( number_format( $st['closing_balance'] / 100, 2, ',', ' ' ) . ' ' . $st['currency'] ); ?></strong></td>
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
									<th><?php esc_html_e( 'Partner / Subjekt', 'vorkei-bridge' ); ?></th>
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
										<td>
											<div style="font-weight:600;color:#1e293b;">
												<?php echo esc_html( $mv['party_name'] ?: ( $mv['note'] ?: '-' ) ); ?>
											</div>
											<?php if ( ! empty( $mv['party_iban'] ) ) : ?>
												<small style="color:#64748b;font-family:monospace;"><?php echo esc_html( $mv['party_iban'] ); ?></small>
											<?php endif; ?>
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
					<h2><?php esc_html_e( '💰 Fakturačné sadzby pre partnerov (Forensic Metering)', 'vorkei-bridge' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Nastavte parametre pre výpočet doporučenej provízie, ak váš systém používajú kolegovia alebo partneri na spracovanie výpisov pre externých klientov.', 'vorkei-bridge' ); ?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_fee_base_amount"><?php esc_html_e( 'Základná sadzba za výpis (€)', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="number" step="0.01" min="0" name="vorkei_bridge_fee_base_amount" id="vorkei_bridge_fee_base_amount" value="<?php echo esc_attr( get_option( 'vorkei_bridge_fee_base_amount', '5.00' ) ); ?>" class="small-text"> €
								<p class="description"><?php esc_html_e( 'Jednotný fixný poplatok za spracovanie jedného bankového výpisu (napr. 5.00 €).', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_fee_movement_rate"><?php esc_html_e( 'Sadzba za 1 pohyb (€)', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="number" step="0.01" min="0" name="vorkei_bridge_fee_movement_rate" id="vorkei_bridge_fee_movement_rate" value="<?php echo esc_attr( get_option( 'vorkei_bridge_fee_movement_rate', '0.00' ) ); ?>" class="small-text"> €
								<p class="description"><?php esc_html_e( 'Poplatok za každú zaevidovanú transakciu (nastavte 0.00 € pre čistú fixnú sadzbu).', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_fee_volume_percent"><?php esc_html_e( 'Percento z obratu výpisu (%)', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="number" step="0.01" min="0" name="vorkei_bridge_fee_volume_percent" id="vorkei_bridge_fee_volume_percent" value="<?php echo esc_attr( get_option( 'vorkei_bridge_fee_volume_percent', '0.00' ) ); ?>" class="small-text"> %
								<p class="description"><?php esc_html_e( 'Podiel na hodnote spracovaných peňazí (nastavte 0.00 % pre čistú fixnú sadzbu).', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="vorkei_bridge_auto_lock_exports"><?php esc_html_e( 'Automaticky zamykať komerčné exporty', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<select name="vorkei_bridge_auto_lock_exports" id="vorkei_bridge_auto_lock_exports">
									<option value="yes" <?php selected( get_option( 'vorkei_bridge_auto_lock_exports', 'yes' ), 'yes' ); ?>>
										<?php esc_html_e( 'Áno — nové exporty sú zamknuté a vodotlačované až do Vášho schválenia', 'vorkei-bridge' ); ?>
									</option>
									<option value="no" <?php selected( get_option( 'vorkei_bridge_auto_lock_exports', 'yes' ), 'no' ); ?>>
										<?php esc_html_e( 'Nie — exporty sú okamžite odomknuté', 'vorkei-bridge' ); ?>
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

	/**
	 * Render Forensic Billing & Audit Page.
	 */
	public function render_billing_page(): void {
		if ( isset( $_GET['action'] ) && 'print_report' === $_GET['action'] ) {
			$this->render_printable_report();
			return;
		}

		global $wpdb;
		$audit_table      = $wpdb->prefix . 'vorkei_audit_log';
		$statements_table = $wpdb->prefix . 'vorkei_statements';

		$logs = method_exists( $wpdb, 'get_results' )
			? (array) $wpdb->get_results( "SELECT * FROM $audit_table ORDER BY created_at DESC", ARRAY_A )
			: [];

		$total_volume_cents = 0;
		$total_minutes      = 0;
		$unpaid_cents       = 0;
		$locked_count       = 0;

		foreach ( $logs as $log ) {
			$total_volume_cents += (int) ( $log['total_volume_cents'] ?? 0 );
			$total_minutes      += (int) ( $log['estimated_minutes'] ?? 0 );
			if ( ( $log['fee_status'] ?? '' ) !== 'paid' ) {
				$unpaid_cents += (int) ( $log['suggested_fee_cents'] ?? 0 );
			}
			if ( (int) ( $log['export_unlocked'] ?? 0 ) === 0 ) {
				$locked_count++;
			}
		}

		$hours_saved = round( $total_minutes / 60, 1 );
		?>
		<div class="wrap vorkei-admin-wrap">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-shield" style="font-size:28px;width:30px;height:30px;line-height:1.2;color:#2563eb;"></span>
				<?php esc_html_e( 'Fakturačný podklad pre partnerov (Forensic Blackbox)', 'vorkei-bridge' ); ?>
			</h1>
			<p class="description">
				<?php esc_html_e( 'Kompletný audit a evidencia spracovaných bankových výpisov, úspory času a výpočet partnerskej provízie za použitie technológie.', 'vorkei-bridge' ); ?>
			</p>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['notice'] ) && 'lock_updated' === $_GET['notice'] ) : ?>
				<div class="notice notice-success is-dismissible" style="margin-top:15px;">
					<p><?php esc_html_e( 'Stav licenčného zámku exportu bol úspešne zmenený.', 'vorkei-bridge' ); ?></p>
				</div>
			<?php elseif ( isset( $_GET['notice'] ) && 'status_updated' === $_GET['notice'] ) : ?>
				<div class="notice notice-success is-dismissible" style="margin-top:15px;">
					<p><?php esc_html_e( 'Stav úhrady provízie bol úspešne aktualizovaný.', 'vorkei-bridge' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- KPI Cards -->
			<div class="vorkei-stats-grid">
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Spracovaný objem peňazí', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="color:#0f172a;">
						<?php echo esc_html( number_format( $total_volume_cents / 100, 2, ',', ' ' ) ); ?> €
					</div>
					<small style="color:#64748b;"><?php esc_html_e( 'Súčet všetkých transakcií vo výpisoch', 'vorkei-bridge' ); ?></small>
				</div>
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Ušetrený čas partnera', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="color:#2563eb;">
						<?php echo esc_html( (string) $hours_saved ); ?> <span style="font-size:1.1rem;font-weight:600;">hodín</span>
					</div>
					<small style="color:#64748b;"><?php echo esc_html( (string) $total_minutes ); ?> <?php esc_html_e( 'minút manuálneho nahadzovania', 'vorkei-bridge' ); ?></small>
				</div>
				<div class="vorkei-stat-card highlight">
					<h3><?php esc_html_e( 'Nevyplatená provízia pre Vás', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="color:#16a34a;">
						<?php echo esc_html( number_format( $unpaid_cents / 100, 2, ',', ' ' ) ); ?> €
					</div>
					<small style="color:#15803d;font-weight:600;"><?php esc_html_e( 'Doporučená suma k prefakturácii', 'vorkei-bridge' ); ?></small>
				</div>
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Stav licenčného zámku', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="color:<?php echo $locked_count > 0 ? '#d97706' : '#16a34a'; ?>;">
						<?php echo esc_html( (string) $locked_count ); ?> <span style="font-size:1.1rem;font-weight:600;"><?php esc_html_e( 'zamknuté', 'vorkei-bridge' ); ?></span>
					</div>
					<small style="color:#64748b;"><?php esc_html_e( 'Z celkovo ', 'vorkei-bridge' ); ?><?php echo count( $logs ); ?> <?php esc_html_e( 'výpisov', 'vorkei-bridge' ); ?></small>
				</div>
			</div>

			<!-- Forensic Table -->
			<div class="vorkei-card" style="margin-top:24px;">
				<h2><?php esc_html_e( '📋 Evidencia asistovaných výpisov a výpočet provízie', 'vorkei-bridge' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Každý záznam zachytáva IP adresu, zariadenie a spracované bankové dáta. Zamknutý export chráni Vaše know-how vodotlačou, kým partner neuhradí servisný poplatok.', 'vorkei-bridge' ); ?>
				</p>

				<?php if ( empty( $logs ) ) : ?>
					<p style="padding:20px 0;color:#64748b;"><?php esc_html_e( 'Zatiaľ neboli zaevidované žiadne asistované výpisy.', 'vorkei-bridge' ); ?></p>
				<?php else : ?>
					<table class="widefat striped" style="margin-top:15px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Dátum a čas', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Spracovateľ / Zariadenie', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Klient & Účet', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Finančný obrat', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Úspora', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Provízia pre Vás', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Licenčný zámok', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Stav úhrady', 'vorkei-bridge' ); ?></th>
								<th style="text-align:right;"><?php esc_html_e( 'Akcie', 'vorkei-bridge' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $logs as $l ) : ?>
								<?php
								$is_unlocked = (int) ( $l['export_unlocked'] ?? 0 ) === 1;
								$is_paid     = ( $l['fee_status'] ?? '' ) === 'paid';
								$log_id      = (int) $l['id'];
								$toggle_nonce = wp_create_nonce( 'vorkei_billing_action_' . $log_id );
								$toggle_lock_url = add_query_arg( [
									'vorkei_action' => 'toggle_lock',
									'log_id'        => $log_id,
									'_wpnonce'      => $toggle_nonce,
								], admin_url( 'admin.php?page=vorkei-bridge-billing' ) );
								$toggle_paid_url = add_query_arg( [
									'vorkei_action' => 'toggle_paid',
									'log_id'        => $log_id,
									'_wpnonce'      => $toggle_nonce,
								], admin_url( 'admin.php?page=vorkei-bridge-billing' ) );
								$print_url = add_query_arg( [
									'page'   => 'vorkei-bridge-billing',
									'action' => 'print_report',
									'log_id' => $log_id,
								], admin_url( 'admin.php' ) );
								$export_csv_url = rest_url( 'vorkei/v1/export/' . rawurlencode( $l['statement_id'] ) . '?format=csv' );
								?>
								<tr>
									<td>
										<strong><?php echo esc_html( $l['created_at'] ); ?></strong>
										<br><small style="color:#64748b;">ID: #<?php echo esc_html( (string) $l['id'] ); ?></small>
									</td>
									<td>
										<strong><?php echo esc_html( $l['user_login'] ?: 'admin' ); ?></strong>
										<br><small style="color:#475569;font-family:monospace;"><?php echo esc_html( $l['ip_address'] ); ?></small>
										<br><small style="color:#64748b;"><?php echo esc_html( $l['client_os'] . ' • ' . $l['client_browser'] ); ?></small>
									</td>
									<td>
										<strong style="color:#0f172a;"><?php echo esc_html( $l['client_name'] ?: 'Neznámy subjekt' ); ?></strong>
										<br><small style="color:#475569;">Výpis #<?php echo esc_html( $l['statement_number'] ); ?></small>
										<br><code style="font-size:11px;"><?php echo esc_html( $l['account_iban'] ); ?></code>
									</td>
									<td>
										<strong style="font-size:14px;"><?php echo esc_html( number_format( (int) $l['total_volume_cents'] / 100, 2, ',', ' ' ) ); ?> €</strong>
										<br><small style="color:#64748b;"><?php echo esc_html( (string) $l['movements_count'] ); ?> <?php esc_html_e( 'položiek', 'vorkei-bridge' ); ?></small>
									</td>
									<td>
										<strong style="color:#2563eb;"><?php echo esc_html( (string) $l['estimated_minutes'] ); ?> min</strong>
										<br><small style="color:#64748b;"><?php esc_html_e( 'čistej práce', 'vorkei-bridge' ); ?></small>
									</td>
									<td>
										<span style="font-size:15px;font-weight:700;color:<?php echo $is_paid ? '#16a34a' : '#b45309'; ?>;">
											<?php echo esc_html( number_format( (int) $l['suggested_fee_cents'] / 100, 2, ',', ' ' ) ); ?> €
										</span>
									</td>
									<td>
										<?php if ( $is_unlocked ) : ?>
											<span class="badge badge-success">🔓 <?php esc_html_e( 'Odomknutý', 'vorkei-bridge' ); ?></span>
										<?php else : ?>
											<span class="badge badge-warning" style="background:#fef3c7;color:#92400e;">🔒 <?php esc_html_e( 'Zamknutý (Vodotlač)', 'vorkei-bridge' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $is_paid ) : ?>
											<span class="badge badge-success">✓ <?php esc_html_e( 'Vyplatené', 'vorkei-bridge' ); ?></span>
										<?php else : ?>
											<span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:600;">⏳ <?php esc_html_e( 'K úhrade', 'vorkei-bridge' ); ?></span>
										<?php endif; ?>
									</td>
									<td style="text-align:right;">
										<div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
											<a href="<?php echo esc_url( $toggle_lock_url ); ?>" class="button button-small <?php echo $is_unlocked ? 'button-secondary' : 'button-primary'; ?>" title="<?php echo $is_unlocked ? 'Zamknúť export' : 'Odomknúť plný export pre partnera'; ?>">
												<?php echo $is_unlocked ? '🔒 Zamknúť' : '🔓 Odomknúť'; ?>
											</a>
											<a href="<?php echo esc_url( $toggle_paid_url ); ?>" class="button button-small button-secondary" title="Zmeniť stav úhrady">
												<?php echo $is_paid ? '↩ Označiť k úhrade' : '💰 Vyplatené'; ?>
											</a>
											<a href="<?php echo esc_url( $print_url ); ?>" class="button button-small" target="_blank" title="Vytlačiť fakturačný výkaz pre partnera">
												📄 <?php esc_html_e( 'Výkaz', 'vorkei-bridge' ); ?>
											</a>
											<a href="<?php echo esc_url( $export_csv_url ); ?>" class="button button-small button-link" target="_blank" title="Stiahnuť CSV súbor">
												📥 CSV
											</a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Strategy & Negotiation Box -->
			<div class="vorkei-card" style="margin-top:24px;background:#f8fafc;border-color:#cbd5e1;">
				<h2><?php esc_html_e( '💡 Ako postupovať pri vyrovnaní s kolegom / partnerom', 'vorkei-bridge' ); ?></h2>
				<div style="font-size:14px;color:#334155;line-height:1.6;">
					<p><strong>1. Máte v rukách nepriestrelné čísla:</strong> Vidíte presný dátum nahratia výpisu (napr. <em>30.10. o 01:18</em>), IP adresu, meno klienta (<em>Flotilový partner s.r.o.</em>) a finančný obrat (<em>vyše 36 000 €</em>).</p>
					<p><strong>2. Úspora času má komerčnú hodnotu:</strong> Namiesto ručného prepisovania 9 platieb a kontroly variabilných symbolov to systém zprocesoval za zlomok sekundy. Tvoj softvér mu ušetril hodiny práce, za ktorú si on od svojho klienta účtuje peniaze.</p>
					<p><strong>3. Transparentná dohoda (Win-Win):</strong> Otvorte výkaz a dohodnite sa na férovom modeli: buď <strong>30–50 % podiel z jeho fakturácie</strong> za účtovné spracovanie, alebo fixná servisná čiastka (napr. <strong>25–35 € za výpis</strong>).</p>
					<p><strong>4. Ochrana licenčným zámkom:</strong> Kým sa nedohodnete, export zostáva v stave <strong>🔒 Zamknutý</strong> — výstupné CSV súbory budú mať maskované dáta s vodotlačou, takže ich nebude môcť priamo importovať do svojho účtovníctva.</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Client Partner Wallets & Paywall Page.
	 */
	public function render_wallets_page(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'vorkei_client_tokens';

		$wallets = ( $wpdb && method_exists( $wpdb, 'get_results' ) )
			? (array) $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A )
			: [];

		$total_partners = count( $wallets );
		$active_count   = 0;
		$total_credits  = 0;
		$total_used     = 0;

		foreach ( $wallets as $w ) {
			if ( (int) ( $w['is_active'] ?? 1 ) === 1 ) {
				$active_count++;
			}
			$total_credits += (int) ( $w['credits_balance'] ?? 0 );
			$total_used    += (int) ( $w['total_statements_used'] ?? 0 );
		}

		$is_paywall_active = ( get_option( 'vorkei_bridge_enforce_credits', 'yes' ) === 'yes' );
		$toggle_paywall_url = wp_nonce_url(
			add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'vorkei_wallet_action' => 'toggle_paywall' ], admin_url( 'admin.php' ) ),
			'vorkei_toggle_paywall_nonce'
		);
		?>
		<div class="wrap vorkei-admin-wrap">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-lock" style="font-size:28px;width:30px;height:30px;line-height:1.2;color:#dc2626;"></span>
				<?php esc_html_e( 'Partneri & Predplatené Kredity (Hard Paywall)', 'vorkei-bridge' ); ?>
			</h1>
			<p class="description">
				<?php esc_html_e( 'Správa partnerských API Tokenov, kreditných peňaženiek a paywall zámku pre aplikáciu Výpis (pdf-t3.vorkei). Bez platných kreditov je synchronizácia odmietnutá kódom HTTP 402 Payment Required.', 'vorkei-bridge' ); ?>
			</p>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['notice'] ) ) : ?>
				<?php if ( 'wallet_created' === $_GET['notice'] ) : ?>
					<div class="notice notice-success is-dismissible" style="margin-top:15px;">
						<p><?php esc_html_e( 'Nový partnerský API Token a peňaženka boli úspešne vytvorené.', 'vorkei-bridge' ); ?></p>
					</div>
				<?php elseif ( 'topup_success' === $_GET['notice'] ) : ?>
					<div class="notice notice-success is-dismissible" style="margin-top:15px;">
						<p><?php printf( esc_html__( 'Úspešne pridaných %d kreditov pre partnera.', 'vorkei-bridge' ), absint( $_GET['added'] ?? 5 ) ); ?></p>
					</div>
				<?php elseif ( 'status_updated' === $_GET['notice'] ) : ?>
					<div class="notice notice-success is-dismissible" style="margin-top:15px;">
						<p><?php esc_html_e( 'Stav partnera / tokenu bol úspešne aktualizovaný.', 'vorkei-bridge' ); ?></p>
					</div>
				<?php elseif ( 'wallet_deleted' === $_GET['notice'] ) : ?>
					<div class="notice notice-success is-dismissible" style="margin-top:15px;">
						<p><?php esc_html_e( 'Partnerský token bol bezpečne odstránený.', 'vorkei-bridge' ); ?></p>
					</div>
				<?php elseif ( 'paywall_updated' === $_GET['notice'] ) : ?>
					<div class="notice notice-success is-dismissible" style="margin-top:15px;">
						<p><?php esc_html_e( 'Globálny stav Hard Paywall zámku bol aktualizovaný.', 'vorkei-bridge' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( isset( $_GET['error'] ) && 'missing_name' === $_GET['error'] ) : ?>
				<div class="notice notice-error is-dismissible" style="margin-top:15px;">
					<p><?php esc_html_e( 'Chyba: Názov partnera je povinný údaj.', 'vorkei-bridge' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- KPI Cards -->
			<div class="vorkei-stats-grid">
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Aktívni partneri', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="color:#0f172a;">
						<?php echo esc_html( (string) $active_count ); ?> <span style="font-size:1.1rem;color:#64748b;font-weight:400;">/ <?php echo esc_html( (string) $total_partners ); ?></span>
					</div>
					<small style="color:#64748b;"><?php esc_html_e( 'Klientske tokeny s povoleným prístupom', 'vorkei-bridge' ); ?></small>
				</div>
				<div class="vorkei-stat-card highlight">
					<h3><?php esc_html_e( 'Kredity v obehu', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="color:#2563eb;">
						<?php echo esc_html( (string) $total_credits ); ?> <span style="font-size:1.1rem;font-weight:600;">kreditov</span>
					</div>
					<small style="color:#1e40af;font-weight:600;"><?php esc_html_e( 'Dostupný kredit na spracovanie výpisov', 'vorkei-bridge' ); ?></small>
				</div>
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Spracovaných výpisov', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="color:#16a34a;">
						<?php echo esc_html( (string) $total_used ); ?> <span style="font-size:1.1rem;font-weight:600;">výpisov</span>
					</div>
					<small style="color:#64748b;"><?php esc_html_e( 'Celkovo spotrebovaných kreditov', 'vorkei-bridge' ); ?></small>
				</div>
				<div class="vorkei-stat-card">
					<h3><?php esc_html_e( 'Stav Hard Paywallu', 'vorkei-bridge' ); ?></h3>
					<div class="number" style="font-size:1.5rem;color:<?php echo $is_paywall_active ? '#16a34a' : '#dc2626'; ?>;">
						<?php echo $is_paywall_active ? '🛡️ ZAPNUTÝ' : '⚠️ VYPNUTÝ'; ?>
					</div>
					<div style="margin-top:8px;">
						<a href="<?php echo esc_url( $toggle_paywall_url ); ?>" class="button button-small <?php echo $is_paywall_active ? 'button-secondary' : 'button-primary'; ?>">
							<?php echo $is_paywall_active ? esc_html__( 'Vypnúť paywall', 'vorkei-bridge' ) : esc_html__( 'Aktivovať paywall', 'vorkei-bridge' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Quick Info Box -->
			<div class="vorkei-card" style="margin-top:24px;background:#f0fdf4;border-color:#86efac;">
				<h3 style="margin-top:0;color:#166534;display:flex;align-items:center;gap:8px;">
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Ako funguje ochrana a kontrola kolegu / partnera', 'vorkei-bridge' ); ?>
				</h3>
				<div style="font-size:13px;color:#1e3a1e;line-height:1.6;">
					<p style="margin:0 0 6px 0;">
						1. Partnerovi odovzdáte jeho unikátny kód (napr. <code>VRK-PETER-FLOTILA-2026</code>). V aplikácii Výpis (pdf-t3.vorkei) zadá tento token do poľa <strong>API Token</strong>.
					</p>
					<p style="margin:0 0 6px 0;">
						2. Pokiaľ má partner <strong>0 kreditov</strong>, systém každý pokus o synchronizáciu výpisu <strong>okamžite zablokuje s kódom HTTP 402 Payment Required</strong>.
					</p>
					<p style="margin:0 0 6px 0;">
						3. <strong>Jednotný cenník: 5,00 € za výpis</strong> (1 kredit = 5 € bez ohľadu na zakúpený počet). Keď vám partner uhradí poplatok, jedným klikom na tlačidlo <strong>+1 (5 €), +5 (25 €), +10 (50 €) alebo +20 (100 €)</strong> mu okamžite dobijete kredit na spracovanie ďalších výpisov.
					</p>
				</div>
			</div>

			<!-- Partners Table -->
			<div class="vorkei-card" style="margin-top:24px;">
				<h2><?php esc_html_e( '👥 Evidencia partnerov a zostatky peňaženiek', 'vorkei-bridge' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Prehľad všetkých vydaných tokenov. Každý token má prísne evidovaný počet zostávajúcich kreditov.', 'vorkei-bridge' ); ?>
				</p>

				<?php if ( empty( $wallets ) ) : ?>
					<p style="padding:20px 0;color:#64748b;"><?php esc_html_e( 'Zatiaľ neboli vytvorené žiadne klientske peňaženky.', 'vorkei-bridge' ); ?></p>
				<?php else : ?>
					<table class="widefat striped" style="margin-top:15px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Partner / Klient', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Klientsky API Token', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Kreditný zostatok', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Sadzba', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Spracované výpisy', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Stav prístupu', 'vorkei-bridge' ); ?></th>
								<th><?php esc_html_e( 'Rýchle dobitie kreditu (+)', 'vorkei-bridge' ); ?></th>
								<th style="text-align:right;"><?php esc_html_e( 'Správa', 'vorkei-bridge' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $wallets as $w ) :
								$w_id        = (int) $w['id'];
								$balance     = (int) ( $w['credits_balance'] ?? 0 );
								$is_active   = ( (int) ( $w['is_active'] ?? 1 ) === 1 );
								$token_key   = $w['token_key'] ?? '';
								$nonce       = wp_create_nonce( 'vorkei_wallet_action_' . $w_id );

								$topup_1_url  = add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'vorkei_wallet_action' => 'topup', 'wallet_id' => $w_id, 'credits' => 1, '_wpnonce' => $nonce ], admin_url( 'admin.php' ) );
								$topup_5_url  = add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'vorkei_wallet_action' => 'topup', 'wallet_id' => $w_id, 'credits' => 5, '_wpnonce' => $nonce ], admin_url( 'admin.php' ) );
								$topup_10_url = add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'vorkei_wallet_action' => 'topup', 'wallet_id' => $w_id, 'credits' => 10, '_wpnonce' => $nonce ], admin_url( 'admin.php' ) );
								$topup_20_url = add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'vorkei_wallet_action' => 'topup', 'wallet_id' => $w_id, 'credits' => 20, '_wpnonce' => $nonce ], admin_url( 'admin.php' ) );

								$toggle_url = add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'vorkei_wallet_action' => 'toggle_active', 'wallet_id' => $w_id, '_wpnonce' => $nonce ], admin_url( 'admin.php' ) );
								$delete_url = add_query_arg( [ 'page' => 'vorkei-bridge-wallets', 'vorkei_wallet_action' => 'delete', 'wallet_id' => $w_id, '_wpnonce' => $nonce ], admin_url( 'admin.php' ) );
							?>
								<tr>
									<td>
										<strong><?php echo esc_html( $w['client_name'] ); ?></strong>
										<?php if ( ! empty( $w['client_email'] ) ) : ?>
											<br><small style="color:#64748b;"><?php echo esc_html( $w['client_email'] ); ?></small>
										<?php endif; ?>
										<?php if ( ! empty( $w['notes'] ) ) : ?>
											<br><small style="color:#94a3b8;font-style:italic;"><?php echo esc_html( $w['notes'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<code style="background:#f1f5f9;color:#0f172a;font-weight:600;padding:3px 6px;border-radius:4px;user-select:all;">
											<?php echo esc_html( $token_key ); ?>
										</code>
										<button type="button" class="button button-small" style="margin-left:4px;" onclick="navigator.clipboard.writeText('<?php echo esc_js( $token_key ); ?>');alert('API Token skopírovaný do schránky!');" title="Kopírovať token">
											📋
										</button>
									</td>
									<td>
										<?php if ( $balance > 0 ) : ?>
											<span style="display:inline-block;padding:4px 10px;border-radius:12px;background:#dcfce7;color:#15803d;font-weight:700;font-size:13px;">
												🟢 <?php echo esc_html( (string) $balance ); ?> <?php esc_html_e( 'kreditov', 'vorkei-bridge' ); ?>
											</span>
										<?php else : ?>
											<span style="display:inline-block;padding:4px 10px;border-radius:12px;background:#fee2e2;color:#991b1b;font-weight:700;font-size:12px;">
												🛑 0 KREDITOV — BLOKOVANÉ (402)
											</span>
										<?php endif; ?>
									</td>
									<td>
										<span style="color:#475569;font-weight:600;">
											<?php echo esc_html( (string) ( $w['cost_per_statement'] ?? 1 ) ); ?> <?php esc_html_e( 'kr. (5,00 € / výpis)', 'vorkei-bridge' ); ?>
										</span>
									</td>
									<td>
										<strong><?php echo esc_html( (string) ( $w['total_statements_used'] ?? 0 ) ); ?></strong>
										<br><small style="color:#64748b;"><?php esc_html_e( 'výpisov spracovaných', 'vorkei-bridge' ); ?></small>
									</td>
									<td>
										<?php if ( $is_active ) : ?>
											<span class="badge badge-success"><?php esc_html_e( 'Aktívny', 'vorkei-bridge' ); ?></span>
										<?php else : ?>
											<span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:600;"><?php esc_html_e( 'Pozastavený', 'vorkei-bridge' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<div style="display:flex;gap:4px;flex-wrap:wrap;">
											<a href="<?php echo esc_url( $topup_1_url ); ?>" class="button button-small" style="color:#15803d;font-weight:600;" title="Dobiť 1 kredit (5 €)">
												+1 (5 €)
											</a>
											<a href="<?php echo esc_url( $topup_5_url ); ?>" class="button button-small" style="color:#15803d;font-weight:700;background:#dcfce7;" title="Dobiť 5 kreditov (25 €)">
												+5 (25 €)
											</a>
											<a href="<?php echo esc_url( $topup_10_url ); ?>" class="button button-small" style="color:#15803d;font-weight:700;" title="Dobiť 10 kreditov (50 €)">
												+10 (50 €)
											</a>
											<a href="<?php echo esc_url( $topup_20_url ); ?>" class="button button-small" style="color:#1e40af;font-weight:700;background:#dbeafe;" title="Dobiť 20 kreditov (100 €)">
												+20 (100 €)
											</a>
										</div>
									</td>
									<td style="text-align:right;">
										<div style="display:flex;gap:6px;justify-content:flex-end;">
											<a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small <?php echo $is_active ? 'button-secondary' : 'button-primary'; ?>">
												<?php echo $is_active ? esc_html__( 'Pozastaviť', 'vorkei-bridge' ) : esc_html__( 'Aktivovať', 'vorkei-bridge' ); ?>
											</a>
											<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small" style="color:#dc2626;" onclick="return confirm('Naozaj chcete zmazať tohto partnera a zrušiť jeho prístup?');">
												🗑️
											</a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Add New Partner Form -->
			<div class="vorkei-card" style="margin-top:24px;">
				<h2><?php esc_html_e( '➕ Vytvoriť nový klientsky token / peňaženku partnera', 'vorkei-bridge' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Vygenerujte token pre nového kolegu, účtovníka alebo externú firmu. Zvolíte počiatočný kredit a cenu za spracovanie jedného výpisu.', 'vorkei-bridge' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=vorkei-bridge-wallets' ) ); ?>" style="margin-top:16px;">
					<?php wp_nonce_field( 'vorkei_create_wallet_nonce' ); ?>
					<input type="hidden" name="vorkei_create_wallet_submit" value="1">

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="client_name"><?php esc_html_e( 'Meno partnera / firmy *', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="text" name="client_name" id="client_name" class="regular-text" placeholder="napr. Kolega Peter (Flotila Bolt/Wolt)" required>
								<p class="description"><?php esc_html_e( 'Názov partnera, ktorý sa bude zobrazovať vo Forensic auditnom logu.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="client_email"><?php esc_html_e( 'Email partnera', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="email" name="client_email" id="client_email" class="regular-text" placeholder="peter@partner.sk">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="token_key"><?php esc_html_e( 'Vlastný API Token (voliteľné)', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="text" name="token_key" id="token_key" class="regular-text" placeholder="napr. VRK-PETER-FLOTILA-2026">
								<p class="description"><?php esc_html_e( 'Ponechajte prázdne pre automatické bezpečné vygenerovanie náhodného kľúča.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="credits_balance"><?php esc_html_e( 'Počiatočný počet kreditov', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="number" min="0" step="1" name="credits_balance" id="credits_balance" value="0" class="small-text">
								<p class="description"><?php esc_html_e( 'Nastavte 0, ak chcete, aby bol partner okamžite zablokovaný paywallom, kým neuhradí zálohu.', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cost_per_statement"><?php esc_html_e( 'Spotreba za 1 výpis (kredity)', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<input type="number" min="1" step="1" name="cost_per_statement" id="cost_per_statement" value="1" class="small-text">
								<p class="description"><?php esc_html_e( 'Koľko kreditov sa odpočíta za každý spracovaný bankový výpis (predvolene 1).', 'vorkei-bridge' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="notes"><?php esc_html_e( 'Interná poznámka', 'vorkei-bridge' ); ?></label>
							</th>
							<td>
								<textarea name="notes" id="notes" rows="2" class="large-text" placeholder="napr. Dohodnutá provízia 35 € za výpis alebo 50% z fakturácie."></textarea>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-saved" style="line-height:1.4;"></span>
							<?php esc_html_e( 'Vytvoriť partnerskú peňaženku & API Token', 'vorkei-bridge' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Printable Forensic Billing Report (A4 Printable Voucher).
	 */
	public function render_printable_report(): void {
		global $wpdb;

		$log_id = absint( $_GET['log_id'] ?? 0 );
		$table  = $wpdb->prefix . 'vorkei_audit_log';

		$log = method_exists( $wpdb, 'get_row' )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d LIMIT 1", $log_id ), ARRAY_A )
			: null;

		if ( ! $log ) {
			wp_die( esc_html__( 'Výkaz nebol nájdený.', 'vorkei-bridge' ) );
		}

		$fee_total  = number_format( (int) $log['suggested_fee_cents'] / 100, 2, ',', ' ' );
		$vol_total  = number_format( (int) $log['total_volume_cents'] / 100, 2, ',', ' ' );
		$base_fee   = number_format( (float) get_option( 'vorkei_bridge_fee_base_amount', 5.00 ), 2, ',', ' ' );
		$mv_rate    = number_format( (float) get_option( 'vorkei_bridge_fee_movement_rate', 0.00 ), 2, ',', ' ' );
		$vol_rate   = number_format( (float) get_option( 'vorkei_bridge_fee_volume_percent', 0.00 ), 2, ',', ' ' );

		?>
		<!DOCTYPE html>
		<html lang="sk">
		<head>
			<meta charset="UTF-8">
			<title>Fakturačný protokol #<?php echo esc_html( (string) $log['id'] ); ?> — Vorkei Statement Bridge</title>
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #1e293b; background: #fff; margin: 40px; }
				.report-box { max-width: 800px; margin: 0 auto; border: 1px solid #cbd5e1; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
				.header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
				.logo { font-size: 22px; font-weight: 800; color: #2563eb; letter-spacing: -0.5px; }
				.doc-type { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 600; text-align: right; }
				.meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 30px; font-size: 14px; }
				.meta-block { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 6px; }
				.meta-block h4 { margin: 0 0 8px 0; color: #475569; font-size: 12px; text-transform: uppercase; }
				table { width: 100%; border-collapse: collapse; margin-top: 30px; font-size: 14px; }
				th { background: #f1f5f9; padding: 12px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #cbd5e1; }
				td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
				.total-row td { font-size: 18px; font-weight: 800; color: #0f172a; border-top: 2px solid #0f172a; border-bottom: none; }
				.signatures { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
				.sig-line { border-top: 1px dashed #94a3b8; padding-top: 8px; text-align: center; font-size: 13px; color: #64748b; }
				.btn-print { display: inline-block; background: #2563eb; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 24px; cursor: pointer; border: none; }
				@media print {
					body { margin: 0; }
					.report-box { border: none; box-shadow: none; padding: 0; }
					.no-print { display: none !important; }
				}
			</style>
		</head>
		<body>
			<div class="report-box">
				<div class="no-print" style="display:flex;justify-content:space-between;align-items:center;">
					<button onclick="window.print()" class="btn-print">🖨️ Tlačiť protokol / Uložiť ako PDF</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=vorkei-bridge-billing' ) ); ?>" style="color:#64748b;font-size:14px;text-decoration:none;">← Späť do administrácie</a>
				</div>

				<div class="header">
					<div>
						<div class="logo">VORKEI STATEMENT BRIDGE</div>
						<small style="color:#64748b;">Fintech & Bank Data Automation Platform</small>
					</div>
					<div class="doc-type">
						PROTOKOL O ASISTOVANOM SPRACOVANÍ<br>
						<span style="font-size:18px;color:#0f172a;font-weight:700;">#<?php echo esc_html( (string) $log['id'] ); ?></span>
					</div>
				</div>

				<div class="meta-grid">
					<div class="meta-block">
						<h4>Poskytovateľ technológie</h4>
						<strong>Vorkei.com Platform Services</strong><br>
						Systém: Vorkei Statement Bridge API<br>
						Host: pdf-t3.vorkei.com
					</div>
					<div class="meta-block">
						<h4>Spracovateľ / Partner</h4>
						<strong><?php echo esc_html( $log['user_login'] ); ?></strong><br>
						Zaznamenaná IP: <code><?php echo esc_html( $log['ip_address'] ); ?></code><br>
						Zariadenie: <?php echo esc_html( $log['client_os'] . ' (' . $log['client_browser'] . ')' ); ?><br>
						Dátum spracovania: <?php echo esc_html( $log['created_at'] ); ?>
					</div>
				</div>

				<div class="meta-block" style="margin-top:20px;">
					<h4>Identifikácia bankového výpisu a klienta</h4>
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
						<div>
							<strong>Subjekt / Klient:</strong> <?php echo esc_html( $log['client_name'] ); ?><br>
							<strong>Číslo výpisu:</strong> #<?php echo esc_html( $log['statement_number'] ); ?>
						</div>
						<div>
							<strong>Bankový účet (IBAN):</strong> <code><?php echo esc_html( $log['account_iban'] ); ?></code><br>
							<strong>Počet bankových pohybov:</strong> <?php echo esc_html( (string) $log['movements_count'] ); ?> položiek
						</div>
					</div>
				</div>

				<table>
					<thead>
						<tr>
							<th>Popis položky vyúčtovania</th>
							<th>Sadzba</th>
							<th>Základ</th>
							<th style="text-align:right;">Suma k úhrade</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong>Základný paušál za inicializáciu a parsing výpisu</strong><br><small style="color:#64748b;">Lokálna extrakcia dát, deduplikácia SHA-256 a validácia zostatku</small></td>
							<td>Fix</td>
							<td>1 výpis</td>
							<td style="text-align:right;font-weight:600;"><?php echo esc_html( $base_fee ); ?> €</td>
						</tr>
						<tr>
							<td><strong>Spracovanie a normalizácia položiek výpisu</strong><br><small style="color:#64748b;">Automatické vyčistenie VS, IBANu a párovací mechanizmus</small></td>
							<td><?php echo esc_html( $mv_rate ); ?> € / pol.</td>
							<td><?php echo esc_html( (string) $log['movements_count'] ); ?> položiek</td>
							<td style="text-align:right;font-weight:600;"><?php echo esc_html( number_format( (int) $log['movements_count'] * (float) $mv_rate, 2, ',', ' ' ) ); ?> €</td>
						</tr>
						<tr>
							<td><strong>Servisný poplatok z objemu finančných tokov</strong><br><small style="color:#64748b;">Zodpovednosť za finančnú integritu a centovú matematiku</small></td>
							<td><?php echo esc_html( $vol_rate ); ?> %</td>
							<td><?php echo esc_html( $vol_total ); ?> €</td>
							<td style="text-align:right;font-weight:600;"><?php echo esc_html( number_format( ( (int) $log['total_volume_cents'] / 100 ) * ( (float) $vol_rate / 100 ), 2, ',', ' ' ) ); ?> €</td>
						</tr>
						<tr class="total-row">
							<td colspan="3">CELKOVÁ SERVISNÁ PROVÍZIA K ÚHRADE:</td>
							<td style="text-align:right;color:#2563eb;"><?php echo esc_html( $fee_total ); ?> €</td>
						</tr>
					</tbody>
				</table>

				<div style="margin-top:24px;padding:16px;background:#f0fdf4;border-left:4px solid #16a34a;border-radius:4px;font-size:13px;color:#166534;">
					<strong>Odhadovaná úspora času v účtovníctve:</strong> Spracovaním tohto výpisu cez technológiu Vorkei Statement Bridge partner ušetril približne <strong><?php echo esc_html( (string) $log['estimated_minutes'] ); ?> minút</strong> manuálnej administratívnej práce.
				</div>

				<div class="signatures">
					<div>
						<br><br><br>
						<div class="sig-line">Za Vorkei Platform (Podpis)</div>
					</div>
					<div>
						<br><br><br>
						<div class="sig-line">Za Spracovateľa / Partnera (Podpis)</div>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Render Stripe Transactions and Credit Management Page.
	 */
	public function render_transactions_page(): void {
		global $wpdb;

		$tx_table = $wpdb->prefix . 'vorkei_credit_transactions';

		$transactions = ( $wpdb && method_exists( $wpdb, 'get_results' ) )
			? (array) $wpdb->get_results( "SELECT * FROM $tx_table ORDER BY created_at DESC LIMIT 100", ARRAY_A )
			: [];

		$total_cents = 0;
		$total_credits = 0;
		$completed_count = 0;
		$pending_count = 0;

		foreach ( $transactions as $tx ) {
			if ( 'completed' === ( $tx['payment_status'] ?? '' ) ) {
				$total_cents += (int) ( $tx['amount_cents'] ?? 0 );
				$total_credits += (int) ( $tx['credits_amount'] ?? 0 );
				$completed_count++;
			} elseif ( 'pending' === ( $tx['payment_status'] ?? '' ) ) {
				$pending_count++;
			}
		}

		$webhook_secret = function_exists( 'get_option' ) ? ( get_option( 'vorkei_stripe_webhook_secret', '' ) ?: get_option( 'vorkei_bridge_stripe_webhook_secret', '' ) ) : '';
		$secret_key     = function_exists( 'get_option' ) ? ( get_option( 'vorkei_stripe_secret_key', '' ) ?: get_option( 'vorkei_bridge_stripe_secret_key', '' ) ) : '';
		$webhook_url    = rest_url( 'vorkei/v1/stripe-webhook' );
		?>
		<div class="wrap vorkei-admin-wrap">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-cart" style="font-size:28px;width:28px;height:28px;margin-right:8px;vertical-align:middle;color:#635bff;"></span>
				<?php esc_html_e( 'Stripe Platby & Kreditné Transakcie', 'vorkei-bridge' ); ?>
			</h1>
			<p class="description">
				<?php esc_html_e( 'Kompletný auditný zoznam prijatých Stripe platieb, automatické priradenie k zákazníckym peňaženkám a nástroj na vyrovnanie.', 'vorkei-bridge' ); ?>
			</p>
			<hr class="wp-header-end">

			<?php settings_errors( 'vorkei_stripe' ); ?>

			<!-- STATS CARDS -->
			<div class="vorkei-stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin:20px 0;">
				<div class="vorkei-card" style="background:#ffffff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">
					<div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Celkový Objem Platieb</div>
					<div style="font-size:28px;font-weight:800;color:#0f172a;"><?php echo esc_html( number_format( $total_cents / 100, 2, ',', ' ' ) ); ?> €</div>
					<div style="font-size:12px;color:#16a34a;margin-top:4px;">Stripe Payments Europe</div>
				</div>
				<div class="vorkei-card" style="background:#ffffff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">
					<div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Pripísané Kredity</div>
					<div style="font-size:28px;font-weight:800;color:#16a34a;"><?php echo esc_html( (string) $total_credits ); ?> výpisov</div>
					<div style="font-size:12px;color:#64748b;margin-top:4px;">Z celkovo <?php echo esc_html( (string) $completed_count ); ?> úspešných platieb</div>
				</div>
				<div class="vorkei-card" style="background:#ffffff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">
					<div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Stav Transakcií</div>
					<div style="font-size:28px;font-weight:800;color:#2563eb;"><?php echo esc_html( (string) count( $transactions ) ); ?></div>
					<div style="font-size:12px;color:#eab308;margin-top:4px;"><?php echo esc_html( (string) $pending_count ); ?> čakajúcich na potvrdenie</div>
				</div>
			</div>

			<!-- STRIPE CONFIGURATION & RECONCILIATION TOOLS -->
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
				<!-- WEBHOOK CONFIG -->
				<div style="background:#ffffff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">
					<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
						<span class="dashicons dashicons-admin-network" style="color:#635bff;"></span>
						<?php esc_html_e( 'Stripe Webhook Konfigurácia', 'vorkei-bridge' ); ?>
					</h3>
					<p style="font-size:13px;color:#64748b;margin-bottom:12px;">
						<?php esc_html_e( 'Tento URL endpoint nastavte vo vašom Stripe Dashboarde (Developers ➔ Webhooks) pre udalosť checkout.session.completed:', 'vorkei-bridge' ); ?>
					</p>
					<div style="background:#f1f5f9;border:1px solid #cbd5e1;padding:8px 12px;border-radius:6px;font-family:monospace;font-size:12px;margin-bottom:16px;word-break:break-all;">
						<?php echo esc_html( $webhook_url ); ?>
					</div>

					<form method="post" action="">
						<?php wp_nonce_field( 'vorkei_stripe_nonce' ); ?>
						<input type="hidden" name="vorkei_stripe_action" value="save_stripe_keys">

						<div style="margin-bottom:12px;">
							<label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;" for="stripe_webhook_secret">
								Signing Secret (whsec_...):
							</label>
							<input type="password" id="stripe_webhook_secret" name="stripe_webhook_secret" value="<?php echo esc_attr( $webhook_secret ); ?>" class="regular-text" style="width:100%;font-family:monospace;">
						</div>

						<div style="margin-bottom:12px;">
							<label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;" for="stripe_secret_key">
								Stripe Secret Key (sk_live_... / sk_test_...):
							</label>
							<input type="password" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text" style="width:100%;font-family:monospace;">
							<small style="color:#64748b;">Používa sa pre automatické vyrovnanie (Reconciliation) pri výpadku webhooku.</small>
						</div>

						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Uložiť Stripe Kľúče', 'vorkei-bridge' ); ?>
						</button>
					</form>
				</div>

				<!-- RECONCILIATION TOOL -->
				<div style="background:#ffffff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">
					<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
						<span class="dashicons dashicons-update" style="color:#16a34a;"></span>
						<?php esc_html_e( 'Mechanizmus Vyrovnania (Reconciliation)', 'vorkei-bridge' ); ?>
					</h3>
					<p style="font-size:13px;color:#64748b;margin-bottom:12px;">
						<?php esc_html_e( 'Ak zákazník zaplatil, ale zlyhal webhook (DNS výpadok, firewall), vložte Stripe Session ID a systém platbu overí priamo na Stripe serveroch a pripíše kredity:', 'vorkei-bridge' ); ?>
					</p>

					<form method="post" action="">
						<?php wp_nonce_field( 'vorkei_stripe_nonce' ); ?>
						<input type="hidden" name="vorkei_stripe_action" value="reconcile_session">

						<div style="margin-bottom:16px;">
							<label style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;" for="reconcile_session_id">
								Stripe Checkout Session ID (cs_...):
							</label>
							<input type="text" id="reconcile_session_id" name="session_id" placeholder="cs_live_... alebo cs_test_..." class="regular-text" style="width:100%;font-family:monospace;" required>
						</div>

						<button type="submit" class="button button-secondary" style="font-weight:600;">
							<?php esc_html_e( 'Skontrolovať a Vyrovnať Platbu', 'vorkei-bridge' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- TRANSACTIONS LIST TABLE -->
			<div class="vorkei-table-card" style="background:#ffffff;border:1px solid #c3c4c7;border-radius:8px;padding:20px;">
				<h3 style="margin-top:0;">
					<?php esc_html_e( 'Zoznam Prijatých Platieb & Priradených Kreditov', 'vorkei-bridge' ); ?>
				</h3>

				<?php if ( empty( $transactions ) ) : ?>
					<p style="color:#64748b;font-style:italic;"><?php esc_html_e( 'Zatiaľ neboli zaznamenané žiadne Stripe transakcie.', 'vorkei-bridge' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>ID</th>
								<th>Dátum &amp; Čas</th>
								<th>Zákazník</th>
								<th>Stripe Session ID</th>
								<th>Suma</th>
								<th>Pripísané Kredity</th>
								<th>Stav</th>
								<th>Priradený Token</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $transactions as $tx ) : ?>
								<?php
								$status = $tx['payment_status'] ?? 'pending';
								$badge_color = '#eab308';
								$badge_bg = '#fef9c3';
								$badge_label = 'Čaká na úhradu';

								if ( 'completed' === $status ) {
									$badge_color = '#16a34a';
									$badge_bg = '#dcfce7';
									$badge_label = 'Zaplatené (Kredity Pripísané)';
								} elseif ( 'failed' === $status ) {
									$badge_color = '#dc2626';
									$badge_bg = '#fee2e2';
									$badge_label = 'Zlyhané';
								}
								?>
								<tr>
									<td>#<?php echo esc_html( (string) $tx['id'] ); ?></td>
									<td><?php echo esc_html( $tx['created_at'] ?? '' ); ?></td>
									<td>
										<strong><?php echo esc_html( $tx['customer_name'] ?: 'Zákazník Stripe' ); ?></strong><br>
										<span style="font-size:12px;color:#64748b;"><?php echo esc_html( $tx['customer_email'] ?? '' ); ?></span>
									</td>
									<td>
										<code style="font-size:11px;"><?php echo esc_html( substr( $tx['stripe_session_id'] ?? '', 0, 24 ) . '...' ); ?></code>
									</td>
									<td>
										<strong><?php echo esc_html( number_format( (int) ( $tx['amount_cents'] ?? 0 ) / 100, 2, ',', ' ' ) ); ?> €</strong>
									</td>
									<td>
										<span style="font-weight:700;color:#16a34a;">+ <?php echo esc_html( (string) ( $tx['credits_amount'] ?? 0 ) ); ?> výpisov</span>
									</td>
									<td>
										<span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;background:<?php echo esc_attr( $badge_bg ); ?>;color:<?php echo esc_attr( $badge_color ); ?>;">
											● <?php echo esc_html( $badge_label ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $tx['token_key'] ) ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=vorkei-bridge-wallets' ) ); ?>" style="font-family:monospace;font-weight:600;font-size:11px;">
												<?php echo esc_html( $tx['token_key'] ); ?>
											</a>
										<?php else : ?>
											<span style="color:#94a3b8;font-size:11px;">—</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
