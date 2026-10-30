(function($) {
	'use strict';

	$(document).ready(function() {
		// Quick IČO Lookup
		$('#vorkei-btn-quick-lookup').on('click', function(e) {
			e.preventDefault();
			const ico = $('#vorkei-quick-ico').val().trim();
			if (!ico) {
				alert('Zadajte IČO.');
				return;
			}

			const $spinner = $('#vorkei-search-spinner');
			const $result = $('#vorkei-quick-result');

			$spinner.addClass('is-active');
			$result.hide().empty();

			$.ajax({
				url: vorkeiIcoAtlas.root + 'vorkei-ico/v1/lookup?ico=' + encodeURIComponent(ico),
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', vorkeiIcoAtlas.nonce);
				},
				success: function(res) {
					$spinner.removeClass('is-active');
					if (!res.found) {
						$result.html('<div class="notice notice-warning inline"><p>' + (res.error || 'Subjekt nebol nájdený.') + '</p></div>').fadeIn();
						return;
					}

					let badgeHtml = '';
					if (res.is_risky) {
						badgeHtml = '<div class="notice notice-error inline"><p><strong>🛡️ RISK RADAR VAROVANIE:</strong> ' + res.risk_reason + '</p></div>';
					} else {
						badgeHtml = '<div class="notice notice-success inline"><p><strong>✓ SUBJEKT JE OVERENÝ A AKTÍVNY:</strong> ' + res.name + ' (' + res.status + ')</p></div>';
					}

					const detailsHtml = `
						<div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:12px; margin-top:8px;">
							<div><strong>Úradný názov:</strong> ${res.name}</div>
							<div><strong>Právna forma:</strong> ${res.legal_form || '—'}</div>
							<div><strong>Sídlo:</strong> ${res.address || '—'}</div>
							<div><strong>Vznik / Zánik:</strong> ${res.founded_on || '—'} ${res.dissolved_on ? '(Zánik: ' + res.dissolved_on + ')' : ''}</div>
						</div>
					`;

					$result.html(badgeHtml + detailsHtml).fadeIn();
				},
				error: function(err) {
					$spinner.removeClass('is-active');
					$result.html('<div class="notice notice-error inline"><p>Chyba spojenia: ' + (err.responseJSON ? err.responseJSON.message : err.statusText) + '</p></div>').fadeIn();
				}
			});
		});

		// Batch Audit
		$('#vorkei-run-batch-audit').on('click', function(e) {
			e.preventDefault();
			if (!confirm('Spustiť hromadný audit všetkých neoverených firiem v systéme?')) {
				return;
			}

			const $progress = $('#vorkei-batch-progress');
			const $fill = $('#vorkei-progress-fill');
			const $status = $('#vorkei-progress-status');
			const $btn = $(this);

			$btn.prop('disabled', true);
			$progress.slideDown();
			$fill.css('width', '15%');
			$status.text('Overujem prvú dávku subjektov voči registru SR...');

			$.ajax({
				url: vorkeiIcoAtlas.root + 'vorkei-ico/v1/batch-audit',
				method: 'POST',
				data: JSON.stringify({ limit: 50 }),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', vorkeiIcoAtlas.nonce);
				},
				success: function(res) {
					$fill.css('width', '100%');
					$status.text(`Hotovo! Spracovaných: ${res.processed}, overených: ${res.verified}, rizikových: ${res.risky}. Obnovujem stránku...`);
					setTimeout(function() {
						location.reload();
					}, 1200);
				},
				error: function(err) {
					$btn.prop('disabled', false);
					$status.text('Chyba počas auditu: ' + (err.responseJSON ? err.responseJSON.message : err.statusText));
				}
			});
		});
	});
})(jQuery);
