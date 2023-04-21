<?php

class EDD_EU_VAT_Fix {

	public function run() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return;
		}

		// Filter the orders page.
		add_action( 'edd_admin_filter_bar_orders', array( $this, 'add_location_filter' ), 1 );
		add_filter( 'edd_payments_table_parse_args', array( $this, 'filter_order_table_args' ), 1000, 2 );

		// Add metabox to order page.
		add_action( 'edd_view_order_details_sidebar_before', array( $this, 'render_meta_box' ) );

		// Add admin ajax enpoints for carrying out the updates to order, lazy / quick way.
		add_action( 'wp_ajax_edd_eu_vat_fix_add_vat', array( $this, 'add_vat' ) );
		add_action( 'wp_ajax_edd_eu_vat_fix_remove_vat', array( $this, 'remove_vat' ) );
	}

	public function remove_vat() {
		// Get action and verify nonce.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		if ( ! wp_verify_nonce( $_GET['nonce'], $action ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'edd-eu-vat-fix' ) ) );
			return;
		}

		// Get order ID.
		$order_id = isset( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;
		$referrer = isset( $_GET['referrer'] ) ? esc_url_raw( $_GET['referrer'] ) : '';
		$payment = new EDD_Payment( $order_id );
		$tax = $payment->tax;
		$total = $payment->total;

		// Remove the tax from order.
		$payment->total = (float)$total - (float)$payment->tax;
		$payment->tax = 0;
		$payment->subtotal = $payment->total;
		$payment->update_meta('_edd_eu_vat_fix_status', 'removed');
		$payment->save();
		
		// Redirect back to referrer.
		wp_redirect( $referrer );
		exit;
	}
	public function add_vat() {
		// Get action and verify nonce.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		if ( ! wp_verify_nonce( $_GET['nonce'], $action ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'edd-eu-vat-fix' ) ) );
			return;
		}
		

	}
	public function add_location_filter() {

		// A a location filter to the orders page to show if the orders are EU or non-EU.
		$location = isset( $_GET['eu_vat_fix_location'] ) ? sanitize_text_field( $_GET['eu_vat_fix_location'] ) : '';
		$options = array(
			'eu' => __( 'EU or UK without VAT', 'edd-eu-vat-fix' ),
			'non-eu' => __( 'Non-EU or UK with VAT', 'edd-eu-vat-fix' ),
		);
		echo EDD()->html->select( array(
			'options'          => $options,
			'name'             => 'eu_vat_fix_location',
			'id'               => 'eu_vat_fix_location',
			'selected'         => $location,
			'show_option_all'  => __( 'All VAT types and locations', 'edd-eu-vat-fix' ),
			'show_option_none' => false
		) );
	}
	public function filter_order_table_args( $args, $paginate ) {
		// Maybe filter by EU VAT location
		if ( ! empty( $_GET['eu_vat_fix_location'] ) ) {
			$meta_query = array();
			$location = sanitize_text_field( $_GET['eu_vat_fix_location'] );
			if ( $location !== 'all' ) {
				$eu_value = '';
				if ( $location === 'eu' ) {
					// Find all orders that are EU.
					$eu_value = '1';
					// That don't have VAT applied.
					$args['compare_query'] = array(
						array(
							'key'     => 'tax',
							'value'   => '0',
							'compare' => '=',
						),
					);
					// Don't show orders that are reverse charged.
					$meta_query[] = array(
						'key'     => '_edd_payment_vat_reverse_charged',
						'value'   => '',
						'compare' => '=',
					);
				} else if ( $location === 'non-eu' ) {
					$eu_value = '';
					$args['compare_query'] = array(
						array(
							'key'     => 'tax',
							'value'   => '0',
							'compare' => '>',
						),
					);
				}
				$meta_query[] = array(
					'key'     => '_edd_payment_vat_is_eu',
					'value'   => $eu_value,
					'compare' => '=',
				);
				$args['meta_query'] = array(
					'relation' => 'AND',
					$meta_query,
				);
			}
		}
		
		return $args;
	}

	public function render_meta_box( $order_id ) {

		$payment = new EDD_Payment( $order_id );

		$is_eu = $payment->get_meta('_edd_payment_vat_is_eu') === '1' ? true : false;
		$is_reverse_charged = $payment->get_meta('_edd_payment_vat_reverse_charged') === '1' ? true : false;
		$fix_status = $payment->get_meta('_edd_eu_vat_fix_status');
		$tax = (float)$payment->tax;
		$currency = $payment->currency;

		// Store the VAT amount in a meta field for re-use later.
		$eu_vat_fix_amount = $payment->get_meta('_edd_eu_vat_fix_amount');
		$has_vat_fix_amount = $eu_vat_fix_amount === '' ? false : true;

		$eu_vat_amount = $currency . ' ' . number_format_i18n( (float)$eu_vat_fix_amount, 2 );
		if ( ! $has_vat_fix_amount ) {
			$eu_vat_amount = $currency . ' ' . number_format_i18n( $tax, 2 );
			$payment->update_meta('_edd_eu_vat_fix_amount', $payment->tax);
		}
		
		// TODO: We don't take into consideration fees or discounts.

		if ( $is_eu && ! $is_reverse_charged && $tax === (float)0 ) {
			// We have an EU payment missing VAT
			ob_start();
			?>
				<?php
					esc_html_e( 'This EU/UK order is missing VAT.', 'edd-eu-vat-fix' );
				?>
				<button class="button button-primary" id="edd_eu_vat_fix_add_vat"><?php esc_html_e( 'Adjust and add VAT', 'edd-eu-vat-fix' ); ?></button>
			<?php
			$message = ob_get_clean();
			$this->render_metabox_message( $message );

		} else if ( ( ! $is_eu && $tax > 0 ) || $fix_status === 'removed' ) {
			// We have a non-EU payment with VAT
			$remove_url_args = array(
				'action' => 'edd_eu_vat_fix_remove_vat',
				'payment_id' => absint( $order_id ),
				'nonce' => wp_create_nonce( 'edd_eu_vat_fix_remove_vat' ),
				// Add current page to redirect back to.
				'referrer' => urlencode_deep( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $order_id ) ),
			);
			$remove_vat_url = add_query_arg( $remove_url_args, admin_url( 'admin-ajax.php' ) );
			ob_start();
			?>
				<?php
					esc_html_e( 'This non EU/UK order incorrectly has VAT.', 'edd-eu-vat-fix' );
				?>
			<p>
				<?php
					esc_html_e( 'Steps:', 'edd-eu-vat-fix' );
				?>
			</p>
			<ol>
				<li><?php esc_html_e( 'Click the button to remove VAT from the order (the total will be updated)', 'edd-eu-vat-fix' ) ?><br />
				<?php
					if ( $fix_status === '' ) {
				?>
					<p><a href="<?php echo esc_url( $remove_vat_url ); ?>" class="button button-primary" id="edd_eu_vat_fix_remove_vat"><?php esc_html_e( 'Adjust and remove VAT', 'edd-eu-vat-fix' ); ?></a></p>
				<?php
					}
				?>
				</li>
				<li><?php echo wp_kses_post( sprintf( __( 'Initialiase a partial refund of<br />%s<br />via your payment provider.', 'edd-eu-vat-fix' ), '<strong>' . $eu_vat_amount . '</strong>') ); ?></li>
				<li><?php esc_html_e( 'Contact customer to let them know their order has been updated', 'edd-eu-vat-fix' ) ?></li>
			</ol>
			<?php
			$message = ob_get_clean();
			$this->render_metabox_message( $message );

		}
	}

	public function render_metabox_message( $message ) {
		?>
		<div id="edd-order-extras" class="postbox edd-order-data">
			<h2 class="hndle">
				<span><?php esc_html_e( 'EU VAT Fix', 'edd-eu-vat-fix' ); ?></span>
			</h2>

			<div class="inside">
				<div class="edd-admin-box">
					<div class="edd-admin-box-inside">
						<?php 
							echo wp_kses_post( $message );
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
