<?php
/**
 * WooCommerce Order Matching Engine.
 *
 * @package    Vorkei_Statement_Bridge
 * @subpackage Vorkei_Statement_Bridge/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Vorkei_Matcher {

	/**
	 * Attempt to match a banking movement with a WooCommerce order.
	 *
	 * @param array $movement  Sanitized movement data array.
	 * @param array $statement Sanitized parent statement data.
	 * @return int|null Matched order ID or null if not matched.
	 */
	public static function match_order( array $movement, array $statement ): ?int {
		// Only incoming payments ("in") can match paid orders.
		if ( isset( $movement['kind'] ) && 'in' !== $movement['kind'] ) {
			return null;
		}

		if ( ! function_exists( 'wc_get_order' ) ) {
			return null;
		}

		$raw_vs = trim( (string) ( $movement['variable_symbol'] ?? '' ) );
		if ( empty( $raw_vs ) ) {
			return null;
		}

		$vs = preg_replace( '/\D/', '', $raw_vs );
		if ( empty( $vs ) ) {
			return null;
		}

		$order = self::find_order_by_vs( $vs, $movement );

		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		$order_id = $order->get_id();

		// Avoid re-matching an order that has already been matched by this movement.
		$already_matched = $order->get_meta( '_vorkei_matched_movement_id' );
		if ( ! empty( $already_matched ) && $already_matched === $movement['id'] ) {
			return $order_id;
		}

		$enable_matching = get_option( 'vorkei_bridge_enable_wc_matching', 'yes' );
		if ( 'yes' !== $enable_matching ) {
			return $order_id; // Identified, but do not modify order status/notes
		}

		$verify_amount = get_option( 'vorkei_bridge_verify_amount', 'yes' );
		$target_status = get_option( 'vorkei_bridge_wc_target_status', 'processing' );
		$add_note      = get_option( 'vorkei_bridge_add_order_note', 'yes' );

		$order_total_cents = (int) round( ( (float) $order->get_total() ) * 100 );
		$movement_cents    = (int) ( $movement['amount_cents'] ?? 0 );
		$statement_curr    = strtoupper( $statement['currency'] ?? 'EUR' );
		$order_curr        = strtoupper( $order->get_currency() );

		$currency_matches = ( $statement_curr === $order_curr );
		$is_amount_valid  = ( 'yes' !== $verify_amount ) || ( ( $order_total_cents === $movement_cents ) && $currency_matches );

		// Format note details.
		$formatted_amount = number_format( $movement_cents / 100, 2, ',', ' ' ) . ' ' . ( $statement['currency'] ?? 'EUR' );
		$sender_info      = trim( ( $movement['party_name'] ?? '' ) . ' (' . ( $movement['party_iban'] ?? '' ) . ')' );

		if ( $is_amount_valid ) {
			$is_synthetic     = ! empty( $statement['is_synthetic'] ) || in_array( $statement['source'] ?? '', [ 'demo', 'manual_generator', 'synthetic', 'pwa_draft' ], true );
			$is_verified_bank = ! $is_synthetic && (
				( ! empty( $statement['is_verified'] ) && true === (bool) $statement['is_verified'] ) ||
				in_array( $statement['source'] ?? '', [ 'bank_verified', 'verified_bank_feed', 'ebanking_api', 'bank_import' ], true )
			);
			$statement_source = sanitize_text_field( $statement['source'] ?? ( $is_synthetic ? 'synthetic' : ( $is_verified_bank ? 'bank_import' : 'unverified' ) ) );

			// Update order meta according to P0-06 specifications
			$order->update_meta_data( '_vorkei_matched_movement_id', sanitize_text_field( $movement['id'] ) );
			$order->update_meta_data( '_vorkei_matched_statement_id', sanitize_text_field( $statement['id'] ?? '' ) );
			$order->update_meta_data( '_vorkei_matched_at', current_time( 'mysql' ) );
			$order->update_meta_data( '_vorkei_statement_source', $statement_source );
			$order->update_meta_data( '_vorkei_is_verified_bank', $is_verified_bank ? 'yes' : 'no' );

			// SECURITY P0 (Mission 07/08): FORBID automatic marking of real WooCommerce orders as paid based on unverified/synthetic statements!
			// Only authenticated verified bank documents can transition order status to 'processing'/'completed'.
			if ( ! $is_verified_bank || $is_synthetic ) {
				$order->update_meta_data( '_vorkei_match_state', 'proposal' );
				if ( 'yes' === $add_note ) {
					$label = $is_synthetic ? __( 'TESTOVACIE / SYNTETICKÉ DÁTA', 'vorkei-bridge' ) : __( 'Neoverený bankový import', 'vorkei-bridge' );
					$note = sprintf(
						/* translators: 1: Label, 2: Amount, 3: Statement number, 4: VS */
						__( '[Vorkei Bridge] ℹ️ NÁVRH PÁROVANIA (%1$s): Zistená zhoda s výpisom č. %3$s. Suma: %2$s, VS: %4$s. Reálna objednávka NEBOLA označená ako zaplatená (vyžaduje overenie personálu e-shopu alebo autorizovaný bankový podklad).', 'vorkei-bridge' ),
						$label,
						$formatted_amount,
						esc_html( $statement['statement_number'] ?? '-' ),
						esc_html( $vs )
					);
					$order->add_order_note( $note, false );
				}
				$order->save();
				return $order_id;
			}

			// Verified authentic bank source: approve payment confirmation
			$order->update_meta_data( '_vorkei_match_state', 'approved' );

			if ( 'yes' === $add_note ) {
				$note = sprintf(
					/* translators: 1: Amount, 2: Statement number, 3: Sender info, 4: Movement date */
					__( '[Vorkei Bridge] ✅ Platba potvrdená a spárovaná z overeného bankového výpisu č. %2$s. Prijatá suma: %1$s dňa %4$s od: %3$s.', 'vorkei-bridge' ),
					$formatted_amount,
					esc_html( $statement['statement_number'] ?? '-' ),
					esc_html( $sender_info ),
					esc_html( $movement['date'] ?? '' )
				);
				$order->add_order_note( $note, false );
			}

			// Update order status if target status is set and order is pending/on-hold.
			if ( 'none' !== $target_status && in_array( $order->get_status(), [ 'pending', 'on-hold', 'failed' ], true ) ) {
				$order->update_status(
					$target_status,
					sprintf( __( 'Vorkei Bridge: Platba potvrdená z overeného výpisu č. %s.', 'vorkei-bridge' ), esc_html( $statement['statement_number'] ?? '-' ) )
				);
			}

			$order->save();

			// Log to WooCommerce logger.
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->info(
					sprintf( 'Platba %s spárovaná s objednávkou #%d (Suma: %s, VS: %s)', $movement['id'], $order_id, $formatted_amount, $vs ),
					[ 'source' => 'vorkei-bridge' ]
				);
			}

			/**
			 * Triggered when a bank movement is successfully matched to an order.
			 *
			 * @param WC_Order $order
			 * @param array    $movement
			 * @param array    $statement
			 */
			do_action( 'vorkei_bridge_order_matched', $order, $movement, $statement );

		} else {
			// Amount discrepancy detected.
			$expected_amount = number_format( $order_total_cents / 100, 2, ',', ' ' ) . ' ' . $order->get_currency();
			$warn_note = sprintf(
				/* translators: 1: Received amount, 2: Expected amount, 3: VS */
				__( '[Vorkei Bridge] ⚠️ UPOZORNENIE: Suma platby sa nezhoduje! Prijaté: %1$s, očakávané: %2$s (VS: %3$s). Skontrolujte platbu manuálne.', 'vorkei-bridge' ),
				$formatted_amount,
				$expected_amount,
				esc_html( $vs )
			);

			if ( 'yes' === $add_note ) {
				$order->add_order_note( $warn_note, false );
				$order->save();
			}

			// Log discrepancy.
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning(
					sprintf( 'Nezrovnalosť sumy pre objednávku #%d: prijaté %s, očakávané %s (VS: %s)', $order_id, $formatted_amount, $expected_amount, $vs ),
					[ 'source' => 'vorkei-bridge' ]
				);
			}

			// Optional email notification to store administrator.
			if ( 'yes' === get_option( 'vorkei_bridge_notify_discrepancy', 'no' ) && function_exists( 'wp_mail' ) ) {
				$to      = get_option( 'admin_email' );
				$subject = sprintf( __( '[Vorkei Bridge] Upozornenie: Nezrovnalosť sumy pri platbe (Objednávka #%d)', 'vorkei-bridge' ), $order_id );
				$body    = sprintf(
					"Dobrý deň,\n\nPri automatickom spracovaní bankového výpisu nastala nezrovnalosť sumy:\n- Objednávka: #%d\n- Variabilný symbol: %s\n- Prijatá suma: %s\n- Očakávaná suma: %s\n- Odosielateľ: %s\n\nSkontrolujte objednávku v administrácii: %s",
					$order_id,
					$vs,
					$formatted_amount,
					$expected_amount,
					$sender_info,
					admin_url( 'post.php?post=' . $order_id . '&action=edit' )
				);
				@wp_mail( $to, $subject, $body );
			}
		}

		return $order_id;
	}

	/**
	 * Find order by variable symbol (supports direct ID and custom order number plugins).
	 *
	 * @param string $vs Clean numeric variable symbol.
	 * @param array  $movement Raw movement data for filter context.
	 * @return WC_Order|null
	 */
	public static function find_order_by_vs( string $vs, array $movement ): ?WC_Order {
		$order = null;

		// 1. Direct Order ID lookup.
		if ( is_numeric( $vs ) ) {
			$candidate = wc_get_order( (int) $vs );
			if ( $candidate instanceof WC_Order ) {
				$order = $candidate;
			}
		}

		// 2. Fallback: Search by order number, invoices, or custom variable symbol meta.
		if ( ! $order ) {
			$raw_vs = trim( (string) ( $movement['variable_symbol'] ?? '' ) );
			$unpadded_vs = ltrim( $vs, '0' );

			$search_values = array_values( array_unique( array_filter( [ $vs, $raw_vs, $unpadded_vs ] ) ) );

			$meta_queries = [ 'relation' => 'OR' ];
			foreach ( $search_values as $val ) {
				$meta_keys = [
					'_order_number',
					'_order_number_formatted',
					'_variable_symbol',
					'variable_symbol',
					'_wcpdf_invoice_number',
					'_superfaktura_invoice_id',
					'_superfaktura_invoice_number',
					'_invoice_number',
					'_invoice_number_formatted',
				];

				foreach ( $meta_keys as $mkey ) {
					$meta_queries[] = [
						'key'     => $mkey,
						'value'   => $val,
						'compare' => '=',
					];
				}
			}

			$found = wc_get_orders( [
				'limit'      => 1,
				'meta_query' => $meta_queries,
			] );

			if ( ! empty( $found ) && $found[0] instanceof WC_Order ) {
				$order = $found[0];
			}
		}

		/**
		 * Filter to allow custom resolution of order by variable symbol.
		 *
		 * @param WC_Order|null $order
		 * @param string        $vs
		 * @param array         $movement
		 */
		return apply_filters( 'vorkei_bridge_find_order_by_vs', $order, $vs, $movement );
	}
}
