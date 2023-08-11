<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'broke_fancy_implode') ) {

	function broke_fancy_implode( $array, $word = false ) {
		if ( ! is_string( $word ) ) $word = __( 'and', 'broke-templates' );

		$last  = array_slice( $array, -1 );
		$first = implode( ', ', array_slice( $array, 0, -1 ) );
		$both  = array_filter( array_merge( array( $first ), $last ), 'strlen' );

		return implode( sprintf( ' %s ', $word ), $both );
	}
}

if ( ! function_exists( 'broke_allowed_hooks' ) ) {

	function broke_allowed_hooks( $hook ) {
		$allowed_hooks = (array) apply_filters( 'broke_data_sources', array(
			'the_content'	=> __( 'the_content', 'broke-templates' ),
			'woocommerce_short_description'	=> __( 'woocommerce_short_description', 'Broke_Components' )
		) );

		if ( array_key_exists( $hook, $allowed_hooks ) ) {
			return $hook;
		} else {
			return 'the_content';
		}
	}

}

if ( ! function_exists( 'broke_allowed_methods' ) ) {

	function broke_allowed_methods( $method ) {
		$allowed_methods = (array) apply_filters( 'broke_data_sources', array(
			'shortcode'	=> __( 'Shortcode', 'broke-templates' ),
			'autoload'	=> __( 'Autoload', 'broke-templates'),
			'prerender'	=> __( 'Prerender', 'broke-templates' )
		) );

		if ( array_key_exists( $method, $allowed_methods ) ) {
			return $method;
		} else {
			return 'shortcode';
		}
	}

}

if ( ! function_exists( 'broke_allowed_data_sources' ) ) {

	function broke_allowed_data_sources( $data_source ) {
		$allowed_data_sources = (array) apply_filters( 'broke_data_sources', array(
			'single'	=> __( 'Single', 'broke' ),
			'query'		=> __( 'Query', 'broke' )
		) );

		if ( array_key_exists( $data_source, $allowed_data_sources ) ) {
			return $data_source;
		} else {
			return 'single';
		}
	}

}

if ( ! function_exists( 'broke_allowed_html' ) ) {

	function broke_allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );

		// iframe
		$allowed['iframe'] = array(
			'src'             => array(),
			'height'          => array(),
			'width'           => array(),
			'frameborder'     => array(),
			'allowfullscreen' => array(),
		);

		// form fields - input
		$allowed['input'] = array(
			'class' => array(),
			'id'    => array(),
			'name'  => array(),
			'value' => array(),
			'type'  => array(),
		);

		// select
		$allowed['select'] = array(
			'class'  => array(),
			'id'     => array(),
			'name'   => array(),
			'value'  => array(),
			'type'   => array(),
		);

		// select options
		$allowed['option'] = array(
			'selected' => array(),
		);

		// style
		$allowed['style'] = array(
			'types' => array(),
		);

		return $allowed;
	}

}

if ( ! function_exists( 'broke_kses' ) ) {
	function broke_kses( $content ) {
		$content = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $content );
		$content = wp_kses( $content, broke_allowed_html() );
		return trim( $content );
	}
}
