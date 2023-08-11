<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Broke_Template_Element' ) ) {
  class Broke_Template_Element extends Tailor_Element {
      /**
       * Registers element settings, sections and controls.
       *
       * @access protected
       */
      protected function register_controls() {
          $this->add_section( 'template', array(
              'title'                 =>  __( 'Template', 'broke-templates' ),
              'priority'              =>  10,
          ) );

          $this->add_setting( 'id', array(
              'sanitize_callback'     =>  'tailor_sanitize_number',
          ) );

          $upper_limit = intval( apply_filters( 'broke_upper_limit', 200 ) );

          $args = array(
      			'post_type'				=> array( 'broke_template' ),
      			'post_status'			=> array( 'publish' ),
      			'order'						=> 'ASC',
      			'orderby'					=> 'menu_order',
      			'posts_per_page' 	=> $upper_limit,
      		);

          $query = get_posts( $args );

          $posts = array(
            '0' => __( 'Please select a template', 'broke-templates' )
          );

          foreach( $query as $post ) {
            $posts[$post->ID] = $post->post_title;
          }

          $this->add_control( 'id', array(
            'label'     => __( 'Template', 'broke-templates' ),
            'type'      => 'select',
            'choices'   => $posts,
            'priority'  =>  10,
            'section'   =>  'template',
          ) );

      }

  }
}
