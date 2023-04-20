<?php

class EDD_EU_VAT_Fix {

	public function run() {
		add_action( 'edd_admin_filter_bar_orders', array( $this, 'add_location_filter' ), 1 );
	}
	public function add_location_filter() {

		// A a location filter to the orders page to show if the orders are EU or non-EU.
		$location = isset( $_GET['eu_location'] ) ? sanitize_text_field( $_GET['eu_location'] ) : '';
		$options = array(
			'eu' => __( 'EU', 'edd-eu-vat-fix' ),
			'non-eu' => __( 'Non-EU', 'edd-eu-vat-fix' ),
		);
		echo EDD()->html->select( array(
			'options'          => $options,
			'name'             => 'eu_location',
			'id'               => 'eu_location',
			'selected'         => $location,
			'show_option_all'  => __( 'All VAT locations', 'edd-eu-vat-fix' ),
			'show_option_none' => false
		) );

		// Add a filter to show if the order had VAT applied or not.
		$vat = isset( $_GET['vat_applied'] ) ? sanitize_text_field( $_GET['vat_applied'] ) : '';
		$options = array(
			'yes' => __( 'Has VAT', 'edd-eu-vat-fix' ),
			'no' => __( 'Doesn\'t have VAT', 'edd-eu-vat-fix' ),
		);
		echo EDD()->html->select( array(
			'options'          => $options,
			'name'             => 'vat_applied',
			'id'               => 'vat_applied',
			'selected'         => $vat,
			'show_option_all'  => __( 'Any VAT status', 'edd-eu-vat-fix' ),
			'show_option_none' => false
		) );
		

	}
}
