<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Broke_Tailor' ) ) {
  class Broke_Tailor {

    private $plugin_name;
  	private $version;

    public function __construct( $plugin_name, $version ) {

  		$this->plugin_name = $plugin_name;
  		$this->version = $version;

  	}

    public function load_tailor_element() {
      include 'class-broke-template-element.php';
    }

    public function register_tailor_element( $element_manager ) {
      $element_manager->add_element( 'broke_template', array(
    		'label'       =>  __( 'Lisa Template', 'broke-templates' ),
    		'description' =>  __( 'Renders a Lisa Template.', 'broke-templates' ),
    		'type'        =>  'content',
    		'badge'       =>  __( 'Template', 'broke-templates' ),
        'dynamic'     =>  true
    	) );
    }

  }
}
