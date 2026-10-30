<?php
/**
 * Admin Dashboard View for VORKEI IČO ATLAS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap vorkei-ico-wrap">
	<div class="vorkei-header">
		<div class="vorkei-brand">
			<span class="vorkei-icon dashicons dashicons-building"></span>
			<div>
				<h1>VORKEI IČO ATLAS <span class="vorkei-badge">v<?php echo esc_html( VORKEI_ICO_ATLAS_VERSION ); ?></span></h1>
				<p class="vorkei-subtitle">Register SR · JetEngine CCT Auto-Enrichment · Risk Radar Detektor</p>
			</div>
		</div>
		<div class="vorkei-actions">
			<button type="button" class="button button-primary button-hero" id="vorkei-run-batch-audit">
				<span class="dashicons dashicons-update"></span> Hromadný audit registrov
			</button>
			<a href="<?php echo esc_url( rest_url( 'vorkei-ico/v1/export' ) ); ?>" class="button button-secondary button-hero" target="_blank">
				<span class="dashicons dashicons-download"></span> Export JSON / CSV
			</a>
		</div>
	</div>

	<!-- KPI Stats Cards -->
	<div class="vorkei-kpis">
		<div class="vorkei-card kpi-total">
			<div class="kpi-num" id="kpi-total"><?php echo esc_html( $stats['total'] ); ?></div>
			<div class="kpi-label">Celkovo firiem v systéme</div>
			<div class="kpi-sub">Customers · Contractors · Clients</div>
		</div>
		<div class="vorkei-card kpi-active">
			<div class="kpi-num text-success" id="kpi-active"><?php echo esc_html( $stats['active'] ); ?></div>
			<div class="kpi-label">Overené aktívne subjekty</div>
			<div class="kpi-sub">Platný právny stav v registri SR</div>
		</div>
		<div class="vorkei-card kpi-risky">
			<div class="kpi-num text-danger" id="kpi-risky"><?php echo esc_html( $stats['risky'] ); ?></div>
			<div class="kpi-label">🛡️ Risk Radar varovania</div>
			<div class="kpi-sub">Likvidácie, konkurzy, zánik</div>
		</div>
		<div class="vorkei-card kpi-unverified">
			<div class="kpi-num text-muted" id="kpi-unverified"><?php echo esc_html( $stats['unverified'] ); ?></div>
			<div class="kpi-label">Neoverené / Chýba IČO</div>
			<div class="kpi-sub">Pripravené na obohatenie</div>
		</div>
	</div>

	<!-- Quick Live IČO Lookup Bar -->
	<div class="vorkei-card vorkei-search-box">
		<h2>Bleskové overenie IČO v registri SR</h2>
		<p class="description">Zadajte 8-miestne IČO a okamžite zistite úradný názov, sídlo, právnu formu a bezpečnostný status.</p>
		<div class="search-form-row">
			<input type="text" id="vorkei-quick-ico" placeholder="napr. 31333532 (ESET) alebo 31707921 (Konkurz)" class="regular-text" />
			<button type="button" class="button button-primary" id="vorkei-btn-quick-lookup">Overiť subjekt</button>
			<span class="spinner" id="vorkei-search-spinner"></span>
		</div>
		<div id="vorkei-quick-result" style="display:none; margin-top:15px;"></div>
	</div>

	<!-- Batch Audit Progress Bar -->
	<div id="vorkei-batch-progress" class="vorkei-card" style="display:none;">
		<h3>Prebieha automatický audit...</h3>
		<div class="progress-bar-wrap">
			<div class="progress-bar-fill" id="vorkei-progress-fill" style="width: 0%;"></div>
		</div>
		<p id="vorkei-progress-status" class="description">Odosielam požiadavky do Registra účtovných závierok...</p>
	</div>

	<!-- Filter Tabs -->
	<div class="vorkei-tabs">
		<a href="?page=vorkei-ico-atlas&filter=all" class="nav-tab <?php echo 'all' === $filter ? 'nav-tab-active' : ''; ?>">Všetky subjekty (<?php echo esc_html( $stats['total'] ); ?>)</a>
		<a href="?page=vorkei-ico-atlas&filter=risky" class="nav-tab nav-tab-danger <?php echo 'risky' === $filter ? 'nav-tab-active' : ''; ?>">🛡️ Rizikové (<?php echo esc_html( $stats['risky'] ); ?>)</a>
		<a href="?page=vorkei-ico-atlas&filter=active" class="nav-tab nav-tab-success <?php echo 'active' === $filter ? 'nav-tab-active' : ''; ?>">✓ Overené aktívne (<?php echo esc_html( $stats['active'] ); ?>)</a>
		<a href="?page=vorkei-ico-atlas&filter=unverified" class="nav-tab <?php echo 'unverified' === $filter ? 'nav-tab-active' : ''; ?>">Neoverené (<?php echo esc_html( $stats['unverified'] ); ?>)</a>
	</div>

	<!-- Entity Table -->
	<table class="wp-list-table widefat fixed striped vorkei-table">
		<thead>
			<tr>
				<th style="width: 25%;">Subjekt / Názov</th>
				<th style="width: 10%;">Typ CCT</th>
				<th style="width: 12%;">IČO</th>
				<th style="width: 15%;">Právna forma</th>
				<th style="width: 20%;">Bezpečnostný status (Risk Radar)</th>
				<th style="width: 18%;">Overené</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $entities ) ) : ?>
				<tr>
					<td colspan="6" style="text-align: center; padding: 20px;">
						<em>Zatiaľ neboli nájdené žiadne záznamy v JetEngine CCT tabuľkách.</em>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $entities as $item ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $item['name'] ?: 'Bez názvu' ); ?></strong>
							<?php if ( ! empty( $item['iban'] ) ) : ?>
								<div class="entity-iban"><code><?php echo esc_html( $item['iban'] ); ?></code></div>
							<?php endif; ?>
							<?php if ( ! empty( $item['address'] ) ) : ?>
								<div class="entity-address"><?php echo esc_html( $item['address'] ); ?></div>
							<?php endif; ?>
						</td>
						<td>
							<span class="badge badge-type badge-<?php echo esc_attr( $item['entity_type'] ); ?>">
								<?php
								switch ( $item['entity_type'] ) {
									case 'customers':
										echo 'Odberateľ';
										break;
									case 'contractors':
										echo 'Dodávateľ';
										break;
									case 'clients':
										echo 'Klient účtu';
										break;
									default:
										echo esc_html( $item['entity_type'] );
								}
								?>
							</span>
						</td>
						<td>
							<?php if ( ! empty( $item['ico'] ) ) : ?>
								<code><?php echo esc_html( $item['ico'] ); ?></code>
							<?php else : ?>
								<span class="text-muted">—</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $item['legal_form'] ?: '—' ); ?></td>
						<td>
							<?php if ( ! empty( $item['is_risky'] ) ) : ?>
								<span class="badge badge-risk" title="<?php echo esc_attr( $item['risk_reason'] ?? '' ); ?>">
									🛡️ RIZIKOVÝ SUBJEKT
								</span>
								<div class="risk-desc"><?php echo esc_html( $item['company_status'] ?: 'Likvidácia / Konkurz' ); ?></div>
							<?php elseif ( ! empty( $item['verified_at'] ) && ! empty( $item['is_active'] ) ) : ?>
								<span class="badge badge-active">✓ Overený aktívny</span>
								<div class="active-desc"><?php echo esc_html( $item['company_status'] ?: 'Aktívny subjekt' ); ?></div>
							<?php elseif ( ! empty( $item['ico'] ) ) : ?>
								<span class="badge badge-unverified">Neoverené IČO</span>
							<?php else : ?>
								<span class="text-muted">Chýba IČO</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $item['verified_at'] ) ) : ?>
								<span class="verified-date"><?php echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $item['verified_at'] ) ) ); ?></span>
							<?php else : ?>
								<button type="button" class="button button-small vorkei-row-audit" data-type="<?php echo esc_attr( $item['entity_type'] ); ?>" data-id="<?php echo esc_attr( $item['_ID'] ); ?>" data-ico="<?php echo esc_attr( $item['ico'] ?? '' ); ?>">
									Overiť
								</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Settings Card -->
	<div class="vorkei-card vorkei-settings-box" style="margin-top: 30px;">
		<h2>Konfigurácia integrácie</h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'vorkei_ico_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="vorkei_ico_api_url">URL Registračného API</label></th>
					<td>
						<input name="vorkei_ico_api_url" type="url" id="vorkei_ico_api_url" value="<?php echo esc_attr( get_option( 'vorkei_ico_api_url', 'http://ico-api.vorkei.com/api/company/proxy-search?ico=' ) ); ?>" class="regular-text" />
						<p class="description">Verejná HTTPS alebo IPv4 adresa Hetzner VPS mikroslužby (napr. <code>http://ico-api.vorkei.com/api/company/proxy-search?ico=</code>).</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Automatické obohacovanie</th>
					<td>
						<label>
							<input name="vorkei_ico_auto_enrich" type="checkbox" value="yes" <?php checked( 'yes', get_option( 'vorkei_ico_auto_enrich', 'yes' ) ); ?> />
							Spustiť audit a obohatenie partnerov automaticky po dokončení synchronizácie výpisov z Vorkei Bridge
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Uložiť nastavenia' ); ?>
		</form>
	</div>
</div>
