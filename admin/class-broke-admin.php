<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://broke.dev
 * @since      1.0.0
 *
 * @package    Broke_Components
 * @subpackage Broke_Components/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Broke_Components
 * @subpackage Broke_Components/admin
 * @author     Daniel Snell <daniel@broke.dev>
 */
class Broke_Components_Admin {

	private $plugin_name;
	private $version;

	public $conditions_reference = array();
	public $conditions_headings = array();

	public $post_types = array();

	public $upper_limit;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->upper_limit = intval( apply_filters( 'broke_upper_limit', 200 ) );

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		global $post_type;
    if( 'broke_template' == $post_type )
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/broke-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		global $post_type;
    if( 'broke_template' == $post_type ) {
			wp_enqueue_script( 'vue', plugin_dir_url( __FILE__ ) . 'js/vue.min.js', array(), $this->version, false );

			wp_enqueue_script( 'ace', plugin_dir_url( __FILE__ ) . 'js/ace/ace.js', array(), $this->version, false );

	    wp_enqueue_script( 'ace-mode-twig', plugin_dir_url( __FILE__ ) . 'js/ace/mode-twig.js', array( 'ace' ), $this->version, false );

			wp_enqueue_script( 'ace-mode-json', plugin_dir_url( __FILE__ ) . 'js/ace/mode-json.js', array( 'ace' ), $this->version, false );

			wp_enqueue_script( 'ace-ext-langtools', plugin_dir_url( __FILE__ ) . 'js/ace/ext-language_tools.js', array( 'ace' ), $this->version, false );

			wp_enqueue_script( 'ace-theme-monokai', plugin_dir_url( __FILE__ ) . 'js/ace/theme-monokai.js', array( 'ace' ), $this->version, false );

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/broke-admin.js', array( 'vue', 'ace', 'ace-mode-twig', 'ace-theme-monokai', 'ace-mode-json' ), $this->version, false );
		}

	}

	public function get_post_types() {
		if ( empty( $this->post_types ) ) {
			$this->post_types = (array) apply_filters( 'broke_prerender_post_types', array() );
		}
		return $this->post_types;
	}

	public function prerender_post_types( $post_types ) {
		$public_post_types = get_post_types( array(
			'public'	=> true
		), 'objects' );

		foreach( $public_post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'broke_prerender' ) || $post_type->name == 'post' || $post_type->name == 'page' ) {
				$post_types[$post_type->name] = $post_type->labels->name;
			};
		}

		$post_types = array_unique( $post_types );

		return $post_types;
	}

	private function setup_default_conditions_reference() {
		$conditions_headings = array();
		$conditions = array();

		// Get an array of the different post status labels, in case we need it later.
		$post_statuses = get_post_statuses();

		$args = array(
					'show_ui' => true,
					'public' => true,
					'publicly_queryable' => true,
					'_builtin' => false
					);

		$post_types = get_post_types( $args, 'object' );

		// Set certain post types that aren't allowed to have custom sidebars.
		$disallowed_types = array( 'slide' );

		// Make the array filterable.
		$disallowed_types = apply_filters( 'broke_disallowed_post_types', $disallowed_types );

		if ( count( $post_types ) ) {
			foreach ( $post_types as $k => $v ) {
				if ( in_array( $k, $disallowed_types ) ) {
					unset( $post_types[$k] );
				}
			}
		}

		// Add per-post support for any post type that supports it.
		$args = array(
				'show_ui' => true,
				'public' => true,
				'publicly_queryable' => true,
				'_builtin' => true
				);

		$built_in_post_types = get_post_types( $args, 'object' );

		foreach ( $built_in_post_types as $k => $v ) {
			if ( $k == 'post' ) {
				$post_types[$k] = $v;
				break;
			}
		}

		foreach ( $post_types as $k => $v ) {
			if ( ! post_type_supports( $k, 'broke-template' ) ) { continue; }

			$conditions_headings[$k] = $v->labels->name;

			$query_args = array( 'numberposts' => intval( $this->upper_limit ), 'post_type' => $k, 'meta_key' => '_enable_sidebar', 'meta_value' => 'yes', 'meta_compare' => '=', 'post_status' => 'any', 'suppress_filters' => 'false' );

			$posts = get_posts( $query_args );

			if ( count( $posts ) > 0 ) {
				foreach ( $posts as $i => $j ) {
					$label = $j->post_title;
					if ( 'publish' != $j->post_status ) {
						$label .= ' <strong>(' . $post_statuses[$j->post_status] . ')</strong>';
					}
					$conditions[$k]['post' . '-' . $j->ID] = array(
										'label' => $label,
										'description' => sprintf( __( 'A custom sidebar for "%s"', 'broke-templates' ), esc_attr( $j->post_title ) )
										);
				}
			}
		}

		// Page Templates
		$conditions['templates'] = array();

		$page_templates = get_page_templates();

		if ( count( $page_templates ) > 0 ) {

			$conditions_headings['templates'] = __( 'Page Templates', 'broke-templates' );

			foreach ( $page_templates as $k => $v ) {
				$token = str_replace( '.php', '', 'page-template-' . $v );
				$conditions['templates'][$token] = array(
									'label' => $k,
									'description' => sprintf( __( 'The "%s" page template', 'broke-templates' ), $k )
									);
			}
		}

		// Post Type Archives
		$conditions['post_types'] = array();

		if ( count( $post_types ) > 0 ) {

			$conditions_headings['post_types'] = __( 'Post Types', 'broke-templates' );

			foreach ( $post_types as $k => $v ) {
				$token = 'post-type-archive-' . $k;

				if ( $v->has_archive ) {
					$conditions['post_types'][$token] = array(
										'label' => sprintf( __( '"%s" Post Type Archive', 'broke-templates' ), $v->labels->name ),
										'description' => sprintf( __( 'The "%s" post type archive', 'broke-templates' ), $v->labels->name )
										);
				}
			}

			foreach ( $post_types as $k => $v ) {
				$token = 'post-type-' . $k;
				$conditions['post_types'][$token] = array(
									'label' => sprintf( __( 'Each Individual %s', 'broke-templates' ), $v->labels->singular_name ),
									'description' => sprintf( __( 'Entries in the "%s" post type', 'broke-templates' ), $v->labels->name )
									);
			}

		}

		// Taxonomies and Taxonomy Terms
		$conditions['taxonomies'] = array();

		$args = array(
					'public' => true
					);

		$taxonomies = get_taxonomies( $args, 'objects' );

		if ( count( $taxonomies ) > 0 ) {

			$conditions_headings['taxonomies'] = __( 'Taxonomy Archives', 'broke-templates' );

			foreach ( $taxonomies as $k => $v ) {
				$taxonomy = $v;

				if ( $taxonomy->public == true ) {
					$conditions['taxonomies']['archive-' . $k] = array(
										'label' => esc_html( $taxonomy->labels->name ) . ' (' . esc_html( $k ) . ')',
										'description' => sprintf( __( 'The default "%s" archives', 'broke-templates' ), strtolower( $taxonomy->labels->name ) )
										);

					// Setup each individual taxonomy's terms as well.
					$conditions_headings['taxonomy-' . $k] = $taxonomy->labels->name;
					$terms = get_terms( $k );
					if ( count( $terms ) > 0 ) {
						$conditions['taxonomy-' . $k] = array();
						foreach ( $terms as $i => $j ) {
							$conditions['taxonomy-' . $k]['term-' . $j->term_id] = array( 'label' => esc_html( $j->name ), 'description' => sprintf( __( 'The %s %s archive', 'broke-templates' ), esc_html( $j->name ), strtolower( $taxonomy->labels->name ) ) );
							if ( $k == 'category' ) {
								$conditions['taxonomy-' . $k]['in-term-' . $j->term_id] = array( 'label' => sprintf( __( 'All posts in "%s"', 'broke-templates' ), esc_html( $j->name ) ), 'description' => sprintf( __( 'All posts in the %s %s archive', 'broke-templates' ), esc_html( $j->name ), strtolower( $taxonomy->labels->name ) ) );
							}
							if ( $k == 'post_tag' ) {
								$conditions['taxonomy-' . $k]['has-term-' . $j->term_id] = array( 'label' => sprintf( __( 'All posts tagged "%s"', 'broke-templates' ), esc_html( $j->name ) ), 'description' => sprintf( __( 'All posts tagged %s', 'broke-templates' ), esc_html( $j->name ) ) );
							}
							if ( $k == 'post_format' ) {
								$conditions['taxonomy-' . $k]['is-term-' . $j->term_id] = array( 'label' => sprintf( __( 'All posts with "%s" post format', 'broke-templates' ), esc_html( $j->name ) ), 'description' => sprintf( __( 'All posts with %s post format', 'broke-templates' ), esc_html( $j->name ) ) );
							}
						}
					}

				}
			}
		}

		$conditions_headings['hierarchy'] = __( 'Template Hierarchy', 'broke-templates' );

		$conditions['hierarchy']['page'] = array(
									'label' => __( 'Pages', 'broke-templates' ),
									'description' => __( 'Displayed on all pages that don\'t have a more specific widget area.', 'broke-templates' )
									);

		$conditions['hierarchy']['search'] = array(
									'label' => __( 'Search Results', 'broke-templates' ),
									'description' => __( 'Displayed on search results screens.', 'broke-templates' )
									);

		$conditions['hierarchy']['home'] = array(
									'label' => __( 'Default "Your Latest Posts" Screen', 'broke-templates' ),
									'description' => __( 'Displayed on the default "Your Latest Posts" screen.', 'broke-templates' )
									);

		$conditions['hierarchy']['front_page'] = array(
									'label' => __( 'Front Page', 'broke-templates' ),
									'description' => __( 'Displayed on any front page, regardless of the settings under the "Settings -> Reading" admin screen.', 'broke-templates' )
									);

		$conditions['hierarchy']['single'] = array(
									'label' => __( 'Single Entries', 'broke-templates' ),
									'description' => __( 'Displayed on single entries of any public post type other than "Pages".', 'broke-templates' )
									);

		$conditions['hierarchy']['archive'] = array(
									'label' => __( 'All Archives', 'broke-templates' ),
									'description' => __( 'Displayed on all archives (category, tag, taxonomy, post type, dated, author and search).', 'broke-templates' )
									);

		$conditions['hierarchy']['author'] = array(
									'label' => __( 'Author Archives', 'broke-templates' ),
									'description' => __( 'Displayed on all author archive screens (that don\'t have a more specific sidebar).', 'broke-templates' )
									);

		$conditions['hierarchy']['date'] = array(
									'label' => __( 'Date Archives', 'broke-templates' ),
									'description' => __( 'Displayed on all date archives.', 'broke-templates' )
									);

		$conditions['hierarchy']['404'] = array(
									'label' => __( '404 Error Screens', 'broke-templates' ),
									'description' => __( 'Displayed on all 404 error screens.', 'broke-templates' )
									);

		$this->conditions_headings = (array) apply_filters( 'broke_conditions_headings', $conditions_headings );
		$this->conditions_reference = (array) apply_filters( 'broke_conditions_reference', $conditions );
	}

	public function add_metaboxes() {
		add_meta_box(
	    'broke_template_code-mb',
	    __( 'Template Code', 'broke-templates' ),
	    array( $this, 'render_code_mb' ),
	    'broke_template',
	    'advanced',
	    'high'
    );

    add_meta_box(
	    'broke-template-attributes-mb',
	    __( 'Conditions', 'broke-templates' ),
	    array( $this, 'render_attributes_mb' ),
	    'broke_template',
	    'side',
	    'default'
    );

		add_meta_box(
	    'broke-template-data-mb',
	    __( 'Data', 'broke-templates' ),
	    array( $this, 'render_data_mb' ),
	    'broke_template',
	    'advanced',
	    'default'
    );

		foreach( $this->get_post_types() as $post_type => $label ) {
			add_meta_box(
		    'broke-template-prerender-mb',
		    __( 'Prerender Template', 'broke-templates' ),
		    array( $this, 'prerender_template_mb' ),
		    $post_type,
		    'side',
		    'default'
	    );
		}
	}

	public function prerender_template_mb( $post, $metabox ) {
		wp_nonce_field( 'broke_template_prerender_nonce', 'broke_template_prerender_nonce_field' );

		$prerender_template_value = (int) get_post_meta( $post->ID, '_broke_prerender_template', true );

		global $post;

		$original_post = $post;

		// Get all the relevant templates.
		$args = array(
			'post_type'		=> 'broke_template',
			'meta_query' 	=> array(
				array(
					'key'				=> '_broke_attribute_method',
					'value'			=> 'prerender',
					'compare' 	=> '='
				)
			),
			'orderby'		=> 'menu_order, title',
			'order'			=> 'ASC'
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			?>
			<p class="broke_prerender_template_wrapper">
				<label for="broke_prerender_template" class="broke_prerender_template_label"><?php _e( 'Select template', 'broke-templates' ); ?></label>
			</p>
			<select name="broke_prerender_template" id="broke_prerender_template">
				<option value="0"<?php if ( $prerender_template_value == 0 ) echo ' selected="selected"'; ?>>
					<?php _e( 'None', 'broke-templates' ); ?>
				</option>
			<?php
			while ( $query->have_posts() ) :  $query->the_post(); ?>
				<option value="<?php echo get_the_ID(); ?>"<?php if ( $prerender_template_value == get_the_ID() ) echo ' selected="selected"'; ?>>
					<?php echo esc_attr( get_the_title() ); ?>
				</option>
			<?php endwhile; ?>
			</select>
			<p class="broke_prerender_warning"><?php _e( '<b>Warning:</b> This will replace the current content upon saving/updating.', 'broke-templates' ); ?></p>
			<?php
			wp_reset_postdata();
			wp_reset_query();

		} else {

			printf( '<p>%1$s</p>', __( 'No templates to prerender.', 'broke-templates' ) );

		}

		$post = $original_post;

		setup_postdata( $post );
	}

	public function save_prerender_template( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return $post_id;
    }

		if ( ! isset( $_POST['post_type'] ) ) {
			return $post_id;
		}

    if ( ! array_key_exists( $_POST['post_type'], $this->get_post_types() ) ) {
      return $post_id;
    }

		if ( isset( $_POST['broke_template_prerender_nonce_field'] ) && wp_verify_nonce( $_POST['broke_template_prerender_nonce_field'], 'broke_template_prerender_nonce' ) ) {

			update_post_meta( $post_id, '_broke_prerender_template', filter_var( $_POST['broke_prerender_template'], FILTER_SANITIZE_NUMBER_INT ) );

		}
	}

	public function render_the_prerendered_template( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) )
			return $post_id;

		if ( ! array_key_exists( 'broke_prerender_template', $_POST ) )
			return $post_id;

		$template_id = filter_var( $_POST['broke_prerender_template'], FILTER_SANITIZE_NUMBER_INT );

		if ( $template_id == 0 )
			return $post_id;

		$post_data = array(
			'ID'						=> $post_id,
			'post_content'	=> broke_shortcode( array( 'id' => $template_id ), null, 'broke_template', $this )
		);

		wp_update_post( $post_data, true );

		return $post_id;
	}

	public function render_code_mb( $post, $metabox ) {
		wp_nonce_field( 'broke_template_code_nonce', 'broke_template_code_nonce_field' );
    $code_template_value = broke_kses( get_post_meta( $post->ID, '_broke_template_code', true ) );
    ?>
		<div id="broke-editor">
    	<div id="broke_code_editor" v-cloak>{{ editorCode }}</div>
			<input type="hidden" name="broke_template_code" id="broke_template_code" v-model="code">
			<p><?php printf( __( 'The above code is rendered with Timber. Please read the <a href="%1$s" target="_blank">Timber documentation</a> for further information.', 'broke-templates' ), 'https://timber.github.io/docs/' ); ?></p>
		</div>
		<script>
		window.brokeEditorCode = `<?php echo $code_template_value; ?>`;
		</script>
    <?php
	}

  public function render_attributes_mb( $post, $metabox ) {
    wp_nonce_field( 'broke_attribute_nonce', 'broke_attribute_nonce_field' );

		if ( count( $this->conditions_reference ) <= 0 )
			$this->setup_default_conditions_reference();

		$selected_conditions = get_post_meta( $post->ID, '_broke_condition', false );

    $available_placements = array(
      array(
        'value' => 'prepend',
        'label' => __( 'Above content', 'broke-templates' )
      ),
      array(
        'value' => 'append',
        'label' => __( 'Below content', 'broke-templates' )
      ),
      array(
        'value' => 'replace',
        'label' => __( 'Replace content', 'broke-templates' )
      )
    );

    $attr_placement_value = esc_js( get_post_meta( $post->ID, '_broke_attribute_placement', true ) );

		if ( empty( $attr_placement_value ) ) {
			$attr_placement_value = 'replace';
		}

		$available_hooks = array(
      'the_content' => __( 'the_content', 'broke-templates' ),
      'woocommerce_short_description' => __( 'woocommerce_short_description', 'broke-templates' )
    );

    $attr_hook_value = esc_js( broke_allowed_hooks( get_post_meta( $post->ID, '_broke_attribute_hook', true ) ) );

		if ( empty( $attr_hook_value ) ) {
			$attr_hook_value = 'the_content';
		}

		$available_methods = array(
			'shortcode'	=> __( 'Shortcode', 'broke-templates' ),
			'autoload'	=> __( 'Autoload', 'broke-templates'),
			'prerender'	=> __( 'Prerender', 'broke-templates' )
		);

		$attr_method_value = esc_js( broke_allowed_methods( get_post_meta( $post->ID, '_broke_attribute_method', true ) ) );

		if ( empty( $attr_method_value ) ) {
			$attr_method_value = 'shortcode';
		}
		?>
		<div id="broke-conditions">
			<div class="broke-wrapper" v-cloak>
				<p class="broke_attribute_method_wrapper">
					<label for="broke_attribute_method" class="broke_attribute_method_label"><?php _e( 'Method', 'broke-templates' ); ?></label>
				</p>
				<select name="broke_attribute_method" id="broke_attribute_method" v-model="method">
					<?php foreach( $available_methods as $value => $method ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>">
								<?php echo esc_attr( $method ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<div class="broke_placement" v-if="method=='autoload' || method=='shortcode'">
					<p class="broke_attribute_placement_wrapper">
						<label for="broke_attribute_placement" class="broke_attribute_placement_label"><?php _e( 'Placement', 'broke-templates' ); ?></label>
					</p>
					<select name="broke_attribute_placement" id="broke_attribute_placement" v-model="placement">
						<?php foreach( $available_placements as $placement ) : ?>
							<option value="<?php echo esc_attr( $placement['value'] ); ?>">
									<?php echo esc_attr( $placement['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="broke_hook" v-if="method=='autoload'">
					<p class="broke_attribute_hook_wrapper">
						<label for="broke_attribute_hook" class="broke_attribute_hook_label"><?php _e( 'Hook', 'broke-templates' ); ?></label>
					</p>
					<select name="broke_attribute_hook" id="broke_attribute_hook" v-model="hook">
						<?php foreach( $available_hooks as $value => $hook ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>">
									<?php echo esc_attr( $hook ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="autoloaded_options" v-if="method=='autoload'">
					<h3><?php _e( 'Load on...', 'broke-templates' ); ?></h3>
					<div id="autoload-conditions">
						<?php foreach( $this->conditions_headings as $k => $heading ) : ?>
							<?php if ( array_key_exists( $k, $this->conditions_reference ) ) : ?>
								<h4><?php echo $heading; ?></h4>
								<div>
									<?php foreach( $this->conditions_reference[$k] as $i => $condition ) : ?>
										<p>
											<label title="<?php echo esc_attr( $condition['description'] ); ?>"><input type="checkbox" name="broke_conditions[]" value="<?php echo $i; ?>"<?php
											if ( in_array( $i, $selected_conditions ) ) {
												echo ' checked="checked"';
											}
											?>> <?php echo $condition['label']; ?></label>
										</p>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="broke_prerender" v-if="method=='prerender'">
					<h4><?php _e( 'Instructions', 'broke-templates' ); ?></h4>
					<p>Go to your <?php echo broke_fancy_implode( $this->get_post_types(), __( 'or', 'broke-templates' ) ); ?> and select this template to prerender the template upon saving.</p>
				</div>
				<div class="broke_shortcode" v-if="method=='shortcode'">
					<h4><?php _e( 'Instructions', 'broke-templates' ); ?></h4>
					<p>Use this shortcode where you want this template: <b>[broke_template id=&quot;<?php echo $post->ID; ?>&quot;][/broke_template]</b></p>
				</div>
			</div>
		</div>
		<script>
			window.brokeConditions = {
				placement: '<?php echo $attr_placement_value; ?>',
				hook: '<?php echo $attr_hook_value; ?>',
				method: '<?php echo $attr_method_value; ?>'
			};
		</script>
    <?php
  }

	public function render_data_mb( $post, $metabox ) {
    wp_nonce_field( 'broke_data_nonce', 'broke_data_nonce_field' );

		$data_sources = array(
			'single'	=> __( 'Single', 'broke-templates' ),
			'query'		=> __( 'Query', 'broke-templates' )
		);
    $data_source_value = esc_js( get_post_meta( $post->ID, '_broke_data_source', true ) );

		if( empty( $data_source_value ) ) {
			$data_source_value = 'single';
		}

		$data_query_value = json_encode( get_post_meta( $post->ID, '_broke_data_query', true ), JSON_PRETTY_PRINT );

		if ( $data_query_value == '""' ) {
			$data_query_value = json_encode(
				array(
					'post_type'              => array( 'post' ),
					'post_status'            => array( 'publish' ),
					'posts_per_page'         => '10',
					'order'                  => 'DESC',
					'orderby'                => 'date',
				),
				JSON_PRETTY_PRINT
			);
		}
		?>
		<div id="broke-data">
			<p class="broke_data_sources_wrapper">
				<label for="broke_data_sources" class="broke_data_sources_label"><?php _e( 'Source', 'broke-templates' ); ?></label>
			</p>
			<select name="broke_data_sources" id="broke_data_sources" v-model="source">
				<?php foreach( $data_sources as $key => $source ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_attr( $source ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p v-if="source === 'single'"><?php _e( 'This exposes a <b>post</b> object referencing the current post object in WordPress.', 'broke-templates' ); ?></p>
			<div id="broke_query" v-show="source === 'query'">
				<p><?php _e( 'This exposes a <b>posts</b> array in your code referencing the post objects as a result from the query below.', 'broke-templates' ); ?></p>
				<p class="broke_data_query_wrapper">
					<label for="broke_data_query" class="broke_data_query_label"><?php _e( 'Query (in JSON)', 'broke-templates' ); ?></label>
				</p>
				<div id="broke_query_editor">{{ query }}</div>
				<input type="hidden" name="broke_template_query" id="broke_template_query" v-model="query">
			</div>
		</div>
		<script>
			window.brokeData = {
				source: '<?php echo $data_source_value; ?>',
				query: `<?php echo $data_query_value; ?>`
			};
		</script>
    <?php
  }

  public function save_data( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return $post_id;
    }

    if ( ! isset( $_POST['post_type'] ) || 'broke_template' !== $_POST['post_type'] ) {
      return $post_id;
    }

		$capabilities = apply_filters( 'broke_capabilites', 'switch_themes' );

    if ( ! current_user_can( $capabilities ) ) {
      return $post_id;
    }

    if ( isset( $_POST['broke_attribute_nonce_field'] ) && wp_verify_nonce( $_POST['broke_attribute_nonce_field'], 'broke_attribute_nonce' ) ) {

			if ( isset( $_POST['broke_attribute_placement'] ) ) {
				update_post_meta( $post_id, '_broke_attribute_placement', sanitize_text_field( $_POST['broke_attribute_placement'] ) );
			}

			update_post_meta( $post_id, '_broke_attribute_hook', sanitize_text_field( $_POST['broke_attribute_hook'] ) );

			update_post_meta( $post_id, '_broke_attribute_method', sanitize_text_field( $_POST['broke_attribute_method'] ) );

			delete_post_meta( $post_id, '_broke_condition' );

			if ( isset( $_POST['broke_conditions'] ) && ( 0 < count( $_POST['broke_conditions'] ) ) ) {
				foreach ( $_POST['broke_conditions'] as $k => $v ) {
					add_post_meta( $post_id, '_broke_condition', sanitize_text_field( $v ), false );
				}
			}
    }

    if ( isset( $_POST['broke_template_code_nonce_field'] ) && wp_verify_nonce( $_POST['broke_template_code_nonce_field'], 'broke_template_code_nonce' ) ) {
      update_post_meta( $post_id, '_broke_template_code',  broke_kses( $_POST['broke_template_code'] ) );
    }

		if ( isset( $_POST['broke_data_nonce_field'] ) && wp_verify_nonce( $_POST['broke_data_nonce_field'], 'broke_data_nonce' ) ) {
      update_post_meta( $post_id, '_broke_data_source', sanitize_text_field(  $_POST['broke_data_sources'] ) );

			update_post_meta( $post_id, '_broke_data_query', (array) json_decode( stripslashes( $_POST['broke_template_query'] ) ) );
    }

  }

}
