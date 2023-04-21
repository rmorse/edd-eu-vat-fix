<?php

class EDD_EU_VAT_Fix {

	public function run() {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return;
		}
		add_action( 'edd_admin_filter_bar_orders', array( $this, 'add_location_filter' ), 1 );
		add_filter( 'edd_payments_table_parse_args', array( $this, 'filter_order_table_args' ), 1000, 2 );
	}
	public function add_location_filter() {

		// A a location filter to the orders page to show if the orders are EU or non-EU.
		$location = isset( $_GET['eu_location'] ) ? sanitize_text_field( $_GET['eu_location'] ) : '';
		$options = array(
			'eu' => __( 'EU or UK without VAT', 'edd-eu-vat-fix' ),
			'non-eu' => __( 'Non-EU or UK with VAT', 'edd-eu-vat-fix' ),
		);
		echo EDD()->html->select( array(
			'options'          => $options,
			'name'             => 'eu_location',
			'id'               => 'eu_location',
			'selected'         => $location,
			'show_option_all'  => __( 'All VAT locations', 'edd-eu-vat-fix' ),
			'show_option_none' => false
		) );
	}
	public function filter_order_table_args( $args, $paginate ) {
		// Maybe EU VAT location
		if ( ! empty( $_GET['eu_location'] ) ) {
			$meta_query = array();
			$location = sanitize_text_field( $_GET['eu_location'] );
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
		
		// $args['gateway'] = 'stripe';
		return $args;
	}
}
