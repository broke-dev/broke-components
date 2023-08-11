<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Broke_Shortcake' ) ) {
  class Broke_Shortcake {

    private $plugin_name;
  	private $version;

    public function __construct( $plugin_name, $version ) {

  		$this->plugin_name = $plugin_name;
  		$this->version = $version;

  	}

    public function register_shortcake_element( $element_manager ) {
      if ( ! function_exists( 'shortcode_ui_register_for_shortcode' ) )
        return;

      $upper_limit = intval( apply_filters( 'broke_upper_limit', 200 ) );

      $args = array(
        'post_type'				=> array( 'broke_template' ),
        'post_status'			=> array( 'publish' ),
        'order'						=> 'ASC',
        'orderby'					=> 'menu_order',
        'posts_per_page' 	=> $upper_limit,
      );

      $query = get_posts( $args );

      $posts = array();

      foreach( $query as $post ) {
        $posts[$post->ID] = $post->post_title;
      }

      shortcode_ui_register_for_shortcode(
        'broke_template',
        array(
          // Display label. String. Required.
          'label' => __( 'Lisa Template', 'broke-templates' ),
          // Icon/image for shortcode. Optional. src or dashicons-$icon. Defaults to carrot.
          'listItemImage' => 'dashicons-editor-code',
          // Available shortcode attributes and default values. Required. Array.
          // Attribute model expects 'attr', 'type' and 'label'
          // Supported field types: text, checkbox, textarea, radio, select, email, url, number, and date.
          'attrs' => array(
            array(
              'label' => __( 'Template', 'broke-templates' ),
              'description' => __( 'Please select your template.', 'broke-templates' ),
              'attr' => 'id',
              'type' => 'select',
              'options' => $posts,
            ),
          ),
        )
      );
    }
  }
}
