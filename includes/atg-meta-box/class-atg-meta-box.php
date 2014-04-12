<?php

if ( ! class_exists( 'ATG_Meta_Box' ) ) :

	/**
	 * Class ATG_Meta_Box
	 */
	class ATG_Meta_Box {

		/**
		 * Version number
		 */
		const VERSION = '0.1-alpha';

		/**
		 * Prefix used for meta keys, array keys, input IDs and names
		 */
		private $prefix;

		/**
		 * The meta box ID
		 */
		private $id;

		/**
		 * Define the post types the meta box should appear on
		 */
		private $post_types;

		/**
		 * Define the fields in the meta box
		 *
		 * Array of arrays. See _get_field_html for available array keys.
		 */
		private $fields;

		/**
		 * Title displayed for the meta box on the edit screen
		 */
		private $title;

		/**
		 * Constructor
		 */
		public function __construct( $id, $args ) {
			if ( ! isset( $args['post_types'], $args['fields'], $args['title'] ) ) {
				return;
			}
			$this->id         = $id;
			$this->post_types = $args['post_types'];
			$this->fields     = $args['fields'];
			$this->title      = $args['title'];

			if ( isset( $args['prefix'] ) ) {
				$this->prefix = $args['prefix'];
			} else {
				$this->prefix = 'atg';
			}

			add_action( 'add_meta_boxes',        array( $this, 'add_meta_box' ) );
			add_action( 'save_post',             array( $this, 'save_post' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Queue the scripts and styles we need in the admin
		 */
		public function enqueue_scripts() {
			wp_enqueue_style( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'wp-color-picker' );

			// Select2 jQuery plugin
			wp_enqueue_style(
				'jquery-select2',
				plugins_url( 'select2.css', __FILE__ ),
				null,
				'3.4.6'
			);
			wp_register_script(
				'jquery-select2',
				plugins_url( 'select2.min.js', __FILE__ ),
				array( 'jquery' ),
				'3.4.6',
				true
			);

			// Meta Box
			wp_enqueue_style(
				'atg-meta-box',
				plugins_url( 'meta-box.css', __FILE__ ),
				null,
				self::VERSION
			);
			wp_enqueue_script(
				'atg-meta-box',
				plugins_url( 'meta-box.js', __FILE__ ),
				array( 'jquery-ui-datepicker', 'wp-color-picker', 'jquery-select2' ),
				self::VERSION,
				true
			);
		}

		/**
		 * Add the meta box to the edit screen
		 *
		 * @param $post_type
		 */
		public function add_meta_box( $post_type ) {
			if ( in_array( $post_type, $this->post_types ) ) {
				add_meta_box(
					$this->id,
					$this->title,
					array( $this, 'render_meta_box_content' ),
					$post_type,
					'advanced',
					'high'
				);
			}
		}

		/**
		 * Display the contents of the meta box
		 *
		 * @param $post
		 */
		public function render_meta_box_content( $post ) {
			wp_nonce_field( 'atg_meta_box_save', $this->prefix . '_' . $this->id . '_nonce' );

			foreach ( $this->fields as $field ) {
				$field_meta_id  = '_' . $this->prefix . '_' . $field['id'];
				$field['value'] = get_post_meta( $post->ID, $field_meta_id );
				if ( 1 == count( $field['value'] ) ) {
					$field['value'] = $field['value'][0];
				}
				echo $this->_get_field_html( $field );
			}
		}

		private function _parse_field_defaults( $field ) {
			$field = wp_parse_args( $field, array(
				'id'          => '',
				'label'       => '',
				'desc'        => '',
				'type'        => 'text',
				'options'     => array(),
				'placeholder' => '',
				'class'       => '',
				'value'       => '',
				'multiple'    => false,
			) );

			return $field;
		}

		/**
		 * Get the markup for an individual field
		 *
		 * @param $field
		 * @return mixed|void
		 */
		private function _get_field_html( $field ) {
			$field = $this->_parse_field_defaults( $field );

			$field_id   = $this->prefix . '_' . $field['id'];
			$field_name = $this->prefix . '_' . $field['id'];
			ob_start();
			?>
			<div class="atg_field">
				<div class="atg_field_label">
					<label for="<?php echo esc_attr( $field_id ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
					</label>
				<span class="atg_description">
					<?php echo esc_html( $field['desc'] ); ?>
				</span>
				</div>
				<div class="atg_field_content <?php echo esc_attr( $field['class'] ); ?>">
					<?php
					switch ( $field['type'] ) {
						case 'wysiwyg' :
							wp_editor(
								$field['value'],
								$field_id,
								array(
									'media_buttons' => false,
									'textarea_name' => $field_name,
								)
							);
							break;

						case 'upload' :
							$attachment_url = '';
							$img_src        = '';
							if ( ! empty( $field['value'] ) ) {
								$attachment_url = wp_get_attachment_url( $field['value'] );
								if ( wp_attachment_is_image( $field['value'] ) ) {
									$img_src = $attachment_url;
								} else {
									$attachment = wp_get_attachment_image_src( $field['value'], null, true );
									$img_src    = $attachment[0];
								}
							}
							?>
							<div class="uploader">
								<input type="hidden"
								       name="<?php echo esc_attr( $field_name ); ?>"
								       id="<?php echo esc_attr( $field_id ); ?>"
								       class="atg_attachment_id"
								       value="<?php echo esc_attr( $field['value'] ); ?>"/>
								<input type="button"
								       class="button button-primary atg_upload"
								       value="Upload"/>
								<a href="#"
								   class="dashicons dashicons-dismiss atg_reset"
								   <?php if (empty( $field['value'] )) { ?>style="display: none;"<?php } ?>></a>
							<span class="atg_filename">
								<?php echo basename( $attachment_url ); ?>
							</span>
								<img src="<?php echo esc_url( $img_src ); ?>"/>
							</div>
							<?php
							break;

						case 'color' :
							if ( empty( $field['value'] ) ) {
								$field['value'] = $field['placeholder'];
							}
							?>
							<input type="text"
							       id="<?php echo esc_attr( $field_id ); ?>"
							       name="<?php echo esc_attr( $field_name ); ?>"
							       value="<?php echo esc_attr( $field['value'] ); ?>"
							       class="atg_colorpicker"
							       data-default-color="<?php echo esc_attr( $field['placeholder'] ); ?>"/>
							<?php
							break;

						case 'date' :
							?>
							<input type="text"
							       id="<?php echo esc_attr( $field_id ); ?>"
							       name="<?php echo esc_attr( $field_name ); ?>"
							       value="<?php echo esc_attr( $field['value'] ); ?>"
							       class="atg_datepicker"/>
							<?php
							break;

						case 'checkboxes' :
							$i = 0;
							foreach ( $field['options'] as $opt_value => $opt_label ) :
								$opt_id   = $this->prefix . '_' . $field['id'] . '_' . $i;
								$opt_name = $this->prefix . '_' . $field['id'] . '[' . $opt_value . ']';
								?>
								<div class="checkbox-item">
									<input type="checkbox"
									       id="<?php echo esc_attr( $opt_id ); ?>"
									       name="<?php echo esc_attr( $opt_name ); ?>"
									       value="<?php echo esc_attr( $opt_value ); ?>"
										<?php echo checked( in_array( $opt_value, (array) $field['value'] ) ); ?> />
									<label for="<?php echo esc_attr( $opt_id ); ?>">
										<?php echo esc_html( $opt_label ); ?>
									</label>
								</div>
								<?php
								$i ++;
							endforeach;
							break;

						case 'radio' :
							$i = 0;
							foreach ( $field['options'] as $opt_value => $opt_label ) :
								$opt_id = $this->prefix . '_' . $field['id'] . '_' . $i;
								?>
								<div class="radio-item">
									<input type="radio"
									       id="<?php echo esc_attr( $opt_id ); ?>"
									       name="<?php echo esc_attr( $field_name ); ?>"
									       value="<?php echo esc_attr( $opt_value ); ?>"
										<?php echo checked( $field['value'], $opt_value ); ?> />
									<label for="<?php echo esc_attr( $opt_id ); ?>">
										<?php echo esc_html( $opt_label ); ?>
									</label>
								</div>
								<?php
								$i ++;
							endforeach;
							break;

						case 'select' :
							if ( is_array( $field['value'] ) ) {
								$field_value_array = $field['value'];
							} else {
								$field_value_array = array( $field['value'] );
							}
							?>
							<select id="<?php echo esc_attr( $field_id ); ?>"
							        name="<?php echo esc_attr( $field_name ); ?><?php if ( $field['multiple'] ) { ?>[]<?php } ?>"
							        data-placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
							        <?php if ( $field['multiple'] ) { ?>multiple="multiple"<?php } ?>>
								<?php
								foreach ( $field['options'] as $opt_value => $opt_label ) :
									?>
									<option value="<?php echo esc_attr( $opt_value ); ?>"
										<?php echo selected( in_array( $opt_value, $field_value_array ) ); ?>>
										<?php echo esc_html( $opt_label ); ?>
									</option>
								<?php
								endforeach;
								?>
							</select>
							<?php
							break;

						case 'textarea' :
							?>
							<textarea id="<?php echo esc_attr( $field_id ); ?>"
							          name="<?php echo esc_attr( $field_name ); ?>"
							          placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"><?php echo esc_textarea( $field['value'] ); ?></textarea>
							<?php
							break;

						case 'text' :
						default :
							?>
								<input type="text"
								       id="<?php echo esc_attr( $field_id ); ?>"
								       name="<?php echo esc_attr( $field_name ); ?>"
								       value="<?php echo esc_attr( $field['value'] ); ?>"
								       placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"/>
							<?php
							break;
					}
					?>
				</div>
			</div>
			<?php
			$field_html = ob_get_clean();
			return apply_filters( $this->prefix . '_meta_field_html', $field_html, $field );
		}

		/**
		 * Save the meta when the post is saved
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function save_post( $post_id ) {
			// Check & verify the nonce
			if (
				! isset( $_POST[$this->prefix . '_' . $this->id . '_nonce'] )
				|| ! wp_verify_nonce( $_POST[$this->prefix . '_' . $this->id . '_nonce'], 'atg_meta_box_save' )
			) {
				return;
			}

			// Make sure we're in a post type this meta box applies to
			if ( ! in_array( $_POST['post_type'], $this->post_types ) ) {
				return;
			}

			// Don't save the meta on autosave.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Check the user's permissions.
			if ( 'page' == $_POST['post_type'] && ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Loop through the fields and sanitize as necessary.
			$sanitized_values = array();
			foreach ( $this->fields as $field ) {
				$field       = $this->_parse_field_defaults( $field );
				$field_name  = $this->prefix . '_' . $field['id'];
				$field_value = isset( $_POST[$field_name] ) ? $_POST[$field_name] : null;

				switch ( $field['type'] ) {
					// WYSIWYG: use wp_kses to sanitize (keeps safe HTML)
					case 'wysiwyg' :
						$sanitized_value = wp_kses_post( $field_value );
						break;

					// Uploader: value should be an integer (attachment ID)
					case 'upload' :
						$sanitized_value = (int) $field_value;
						break;

					// Checkboxes: store as array of values
					case 'checkboxes' :
						$checked = array();
						foreach ( $field['options'] as $opt_value => $opt_label ) {
							if ( isset( $field_value[$opt_value] ) ) {
								$checked[] = $opt_value;
							}
						}
						$sanitized_value = $checked;
						break;

					// Select: store as array of values if it's a multi-select
					case 'select' :
						if ( $field['multiple'] ) {
							$sanitized_value = (array) $field_value;
						} else {
							$sanitized_value = sanitize_text_field( $field_value );
						}
						break;

					// Textarea: use wp_kses to sanitize (keeps safe HTML)
					case 'textarea' :
						$sanitized_value = wp_kses_post( $field_value );
						break;

					// Everything else: use sanitize_text_field
					case 'color' :
					case 'date' :
					case 'radio' :
					case 'text' :
					default :
						$sanitized_value = sanitize_text_field( $field_value );
						break;
				}

				$sanitized_values[$field['id']] = $sanitized_value;

				// See function comment for explanation of why this is here
				if ( $this->_field_should_be_updated( $post_id, $field, $sanitized_value ) ) {
					// Shazam!
					if ( is_array( $sanitized_value ) ) {
						delete_post_meta( $post_id, '_' . $field_name );
						foreach ( $sanitized_value as $value ) {
							add_post_meta( $post_id, '_' . $field_name, $value );
						}
					} else {
						update_post_meta( $post_id, '_' . $field_name, $sanitized_value );
					}
				}
			}

			do_action( 'atg_fields_updated', $this->fields, $sanitized_values );
		}

		/**
		 * Return true if update_post_meta should run for a particular field
		 *
		 * Allow update_post_meta to be hijacked in case a user wants to
		 * store the data differently (like putting multiple fields in one
		 * meta entry, or splitting up arrays into multiple meta entries).
		 *
		 * @param int   $post_id
		 * @param array $field
		 * @param mixed $sanitized_value
		 * @return bool
		 */
		private function _field_should_be_updated( $post_id, $field, $sanitized_value ) {
			return apply_filters( 'atg_update_field', true, $post_id, $field, $sanitized_value );
		}
	}

endif;
