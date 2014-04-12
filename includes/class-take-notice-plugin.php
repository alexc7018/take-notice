<?php
/**
 * class-take-notice-plugin.php
 */
class Take_Notice_Plugin {

	private $_post_type;
	private $_available_post_types = array();
	private $_available_taxonomies = array();

	/**
	 * Construct
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$this->dir = plugin_dir_path( __FILE__ );
		$this->url = plugins_url( '', __FILE__ );
		$this->_post_type = 'notice';

		$ignore_post_types = array(
			'revision',
			'nav_menu_item',
			$this->_post_type,
		);
		$post_types = get_post_types();
		foreach ( $post_types as $post_type_id ) {
			if ( ! in_array( $post_type_id, $ignore_post_types ) ) {
				$post_type        = get_post_type_object( $post_type_id );
				$post_type_labels = get_post_type_labels( $post_type );

				$this->_available_post_types[$post_type_id] = $post_type_labels->name;
			}
		}

		$ignore_taxonomies = array(
			'nav_menu',
			'link_category',
			'post_format',
		);
		$taxonomies = get_taxonomies();
		foreach ( $taxonomies as $taxonomy_id ) {
			if ( ! in_array( $taxonomy_id, $ignore_taxonomies ) ) {
				$taxonomy        = get_taxonomy( $taxonomy_id );
				$taxonomy_labels = get_taxonomy_labels( $taxonomy );

				$this->_available_taxonomies[$taxonomy_id] = $taxonomy_labels->name;
			}
		}

		$this->_meta_box();

		add_action( 'init',                  array( $this, 'register_post_type' ) );
		add_filter( 'the_content',           array( $this, 'add_to_content' ), 1, 1 );
		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ), 10, 1 );
		add_action( 'save_post',             array( $this, 'save_post' ), 99, 1 );
	}

	private function _meta_box() {

		$show_on_all_options = array(
			'everywhere' => 'Everywhere',
		);
		if ( 'posts' == get_option( 'show_on_front' ) ) {
			$show_on_all_options['home'] = 'Home page (latest posts)';
		} else {
			$show_on_all_options['front_page'] = 'Home page';
			$show_on_all_options['home']       = 'Latest posts';
		}

		$display_fields_0 = array();
		$display_fields_1 = array();
		$display_fields_2 = array();

		foreach ( $this->_available_post_types as $post_type_id => $post_type_name ) {
			$show_on_all_options['all_' . $post_type_id] = 'All ' . $post_type_name;

			$posts = array();
			$post_objects = get_posts( array(
				'posts_per_page' => -1,
				'post_type'      => $post_type_id,
				'orderby'        => 'title',
				'order' => 'ASC',
			) );
			foreach ( $post_objects as $post_object ) {
				$posts[$post_object->ID] = $post_object->post_title;
			}
			$display_fields_1[] = array(
				'id' => 'show_on_specific_' . $post_type_id,
				'type' => 'select',
				'multiple' => true,
				'label' => 'Show on Specific ' . $post_type_name,
				'options' => $posts,
			);
		}

		foreach ( $this->_available_taxonomies as $taxonomy_id => $taxonomy_name ) {
			$terms = array();
			$term_objects = get_terms( $taxonomy_id );
			foreach ( $term_objects as $term ) {
				$terms[$term->term_id] = $term->name;
			}
			$display_fields_2[] = array(
				'id' => 'show_on_specific_' . $taxonomy_id,
				'type' => 'select',
				'multiple' => true,
				'label' => 'Show on Posts in Specific ' . $taxonomy_name,
				'options' => $terms,
			);
		}

		$display_fields_0 = array(
			array(
				'id' => 'position_in_content',
				'type' => 'select',
				'label' => 'Position in Content',
				'options' => array(
					'before' => 'Before content',
					'after' => 'After content',
				),
			),
			array(
				'id' => 'show_on',
				'type' => 'checkboxes',
				'label' => 'Show on',
				'options' => $show_on_all_options,
			),
		);

		$display_fields = array_merge( $display_fields_0, $display_fields_1, $display_fields_2 );

		$appearance_fields = array(
			array(
				'id' => 'show_title',
				'type' => 'checkboxes',
				'label' => 'Show Notice Title',
				'options' => array(
					'show_title' => 'Display the notice title'
				),
			),
			array(
				'id' => 'background_color',
				'type' => 'color',
				'label' => 'Background Color',
				'placeholder' => '#e5e5e5',
			),
			array(
				'id' => 'border_color',
				'type' => 'color',
				'label' => 'Border Color',
				'placeholder' => '#bbbbbb',
			),
			array(
				'id' => 'text_color',
				'type' => 'color',
				'label' => 'Text Color',
				'placeholder' => '#555555',
			),
		);

		if ( ! class_exists( 'ATG_Meta_Box' ) ) {
			require_once TAKENOTICE_PATH . 'includes/atg-meta-box/class-atg-meta-box.php';
		}

		new ATG_Meta_Box( 'take_notice_display', array(
			'title'      => 'Notice Display Options',
			'post_types' => array( 'notice' ),
			'fields'     => $display_fields,
			'prefix'     => 'take_notice',
		) );

		new ATG_Meta_Box( 'take_notice_appearance', array(
			'title'      => 'Notice Appearance',
			'post_types' => array( 'notice' ),
			'fields'     => $appearance_fields,
			'prefix'     => 'take_notice',
		) );
	}

	/**
	 * Add all notices to content
	 *
	 * @since 1.0
	 */
	public function add_to_content( $content ) {
		if ( ( is_singular() ) ) :

			$post_type_id = get_post_type();

			$meta_queries = array(
				'relation' => 'OR',
				array(
					'key'   => '_take_notice_show_on',
					'value' => 'all_' . $post_type_id,
				),
				array(
					'key'   => '_take_notice_show_on_specific_' . $post_type_id,
					'value' => get_the_ID(),
				),
			);

			foreach ( get_the_taxonomies( get_the_ID() ) as $tax_id => $term_list ) {
				$terms = get_the_terms( get_the_ID(), $tax_id );
				if ( ! empty( $terms ) ) {
					$term_ids = wp_list_pluck( $terms, 'term_id' );
					$meta_queries[] = array(
						'key' => '_take_notice_show_on_specific_' . $tax_id,
						'value' => $term_ids,
						'compare' => 'IN',
					);
				}
			}

			$notices = get_posts( array(
				'posts_per_page' => -1,
				'post_type'      => $this->_post_type,
				'meta_query'     => $meta_queries,
			) );
			foreach ( $notices as $notice ) {
				if ( 'before' == get_post_meta( $notice->ID, '_take_notice_position_in_content', true ) ) {
					$content = $this->_display_notice( $notice, 'before' ) . $content;
				} else {
					$content .= $this->_display_notice( $notice, 'after' );
				}
			}

		endif; // is_singular()

		return $content;
	}


	/**
	 * Register post type
	 *
	 * @since 1.0
	 */
	public function register_post_type() {

		register_post_type( 'notice', array(
			'labels'             => array(
				'name'               => __( 'Notices' ),
				'singular_name'      => __( 'Notice' ),
				'add_new'            => __( 'Add New' ),
				'add_new_item'       => __( 'Add New Notice' ),
				'edit_item'          => __( 'Edit Notice' ),
				'new_item'           => __( 'New Notice' ),
				'view_item'          => __( 'View Notice' ),
				'search_items'       => __( 'Search Notices' ),
				'not_found'          => __( 'No notices found' ),
				'not_found_in_trash' => __( 'No notices found in Trash' ),
				'parent_item_colon'  => __( 'Parent Notice:' ),
				'menu_name'          => __( 'Notices' )
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-format-aside',
			'supports'           => array( 'title', 'editor', 'author' )
		) );

	}

	public function post_updated_messages( $messages ) {
		$messages[$this->_post_type] = array(
			0  => '',
			1  => __( 'Notice updated.', 'takenotice' ),
			2  => __( 'Custom field updated.', 'takenotice' ),
			3  => __( 'Custom field deleted.', 'takenotice' ),
			4  => __( 'Notice updated.', 'takenotice' ),
			5  => '',
			6  => __( 'Notice published.', 'takenotice' ),
			7  => __( 'Notice saved.', 'takenotice' ),
			8  => __( 'Notice submitted.', 'takenotice' ),
			9  => sprintf(
				__( 'Notice scheduled for: <strong>%1$s</strong>.', 'takenotice' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'takenotice' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Notice draft updated.', 'takenotice' ),
		);

		return $messages;
	}

	private function _display_notice( $notice, $position ) {
		$notice_html = get_transient( 'take_notice_' . $notice->ID );
		if ( ! $notice_html ) {
			ob_start();
			$show_title       = get_post_meta( $notice->ID, '_take_notice_show_title', true );
			$background_color = get_post_meta( $notice->ID, '_take_notice_background_color', true );
			$border_color     = get_post_meta( $notice->ID, '_take_notice_border_color', true );
			$text_color       = get_post_meta( $notice->ID, '_take_notice_text_color', true );

			$content = wpautop( wptexturize( $notice->post_content ) );
			?>
			<div class="take-notice <?php echo $position; ?>-content" style="background: <?php echo esc_attr( $background_color ); ?>; border: 3px solid <?php echo esc_attr( $border_color ); ?>; color: <?php echo esc_attr( $text_color ); ?>">
				<?php if ( $show_title ) : ?>
					<h2><?php echo esc_html( $notice->post_title ); ?></h2>
				<?php endif; ?>
				<?php echo $content; ?>
			</div>
			<?php
			$notice_html = ob_get_clean();
			set_transient( 'take_notice_' . $notice->ID, trim( $notice_html ) );
		}
		return apply_filters( 'take_notice_notice_html', $notice_html, $notice );
	}

	public function save_post( $post_id ) {
		if ( get_post_type( $post_id ) == $this->_post_type ) {
			delete_transient( 'take_notice_' . $post_id );
		}
	}

	/**
	 * Scripts and styles for the front-end
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'take-notice',
			TAKENOTICE_URL . 'assets/css/take-notice.css',
			array(),
			TAKENOTICE_VERSION
		);
	}

	/**
	 * Scripts and styles for the admin
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script(
			'take-notice-admin',
			TAKENOTICE_URL . 'assets/js/take_notice.min.js',
			array( 'jquery', 'wp-color-picker' ),
			TAKENOTICE_VERSION
		);
	}

}

