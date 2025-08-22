<?php
/*
Plugin Name: WPC Linked Variation for WooCommerce
Plugin URI: https://wpclever.net/
Description: WPC Linked Variation built to link separate products together by attributes.
Version: 4.3.6
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-linked-variation
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.8
WC requires at least: 3.0
WC tested up to: 10.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WPCLV_VERSION' ) && define( 'WPCLV_VERSION', '4.3.6' );
! defined( 'WPCLV_LITE' ) && define( 'WPCLV_LITE', __FILE__ );
! defined( 'WPCLV_FILE' ) && define( 'WPCLV_FILE', __FILE__ );
! defined( 'WPCLV_URI' ) && define( 'WPCLV_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCLV_DIR' ) && define( 'WPCLV_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WPCLV_SUPPORT' ) && define( 'WPCLV_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=wpclv&utm_campaign=wporg' );
! defined( 'WPCLV_REVIEWS' ) && define( 'WPCLV_REVIEWS', 'https://wordpress.org/support/plugin/wpc-linked-variation/reviews/?filter=5' );
! defined( 'WPCLV_CHANGELOG' ) && define( 'WPCLV_CHANGELOG', 'https://wordpress.org/plugins/wpc-linked-variation/#developers' );
! defined( 'WPCLV_DISCUSSION' ) && define( 'WPCLV_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-linked-variation' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCLV_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpclv_init' ) ) {
	add_action( 'plugins_loaded', 'wpclv_init', 11 );

	function wpclv_init() {
		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpclv_notice_wc' );

			return null;
		}

		if ( ! class_exists( 'WPCleverWpclv' ) ) {
			class WPCleverWpclv {
				protected static $settings = [];
				protected static $localization = [];
				protected static $instance = null;

				public static function instance() {
					if ( is_null( self::$instance ) ) {
						self::$instance = new self();
					}

					return self::$instance;
				}

				function __construct() {
					self::$settings     = (array) get_option( 'wpclv_settings', [] );
					self::$localization = (array) get_option( 'wpclv_localization', [] );

					// init
					add_action( 'init', [ $this, 'init' ] );

					// meta box
					add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
					add_action( 'save_post_wpclv', [ $this, 'save_meta_boxes' ] );

					// column
					add_filter( 'manage_edit-wpclv_columns', [ $this, 'custom_column' ] );
					add_action( 'manage_wpclv_posts_custom_column', [ $this, 'custom_column_value' ], 10, 2 );

					add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
					add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
					add_action( 'admin_init', [ $this, 'register_settings' ] );
					add_action( 'admin_menu', [ $this, 'admin_menu' ] );

					switch ( self::get_setting( 'position', 'above' ) ) {
						case 'above':
							add_action( 'woocommerce_single_product_summary', [ $this, 'render_single' ], 25 );
							break;
						case 'below':
							add_action( 'woocommerce_single_product_summary', [ $this, 'render_single' ], 35 );
							break;
						case 'below_title':
							add_action( 'woocommerce_single_product_summary', [ $this, 'render_single' ], 6 );
							break;
						case 'below_price':
							add_action( 'woocommerce_single_product_summary', [ $this, 'render_single' ], 11 );
							break;
						case 'below_excerpt':
							add_action( 'woocommerce_single_product_summary', [ $this, 'render_single' ], 21 );
							break;
					}

					switch ( self::get_setting( 'position_archive', 'no' ) ) {
						case 'above':
							add_action( 'woocommerce_after_shop_loop_item', [ $this, 'render_archive' ], 9 );
							break;
						case 'below':
							add_action( 'woocommerce_after_shop_loop_item', [ $this, 'render_archive' ], 11 );
							break;
						case 'below_title':
							add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'render_archive' ], 4 );
							break;
						case 'below_price':
							add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'render_archive' ], 11 );
							break;
					}

					// link
					add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
					add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

					// ajax
					add_action( 'wp_ajax_wpclv_search_term', [ $this, 'ajax_search_term' ] );
					add_action( 'wc_ajax_wpclv_load_content', [ $this, 'ajax_load_content' ] );

					// WPC Smart Messages
					add_filter( 'wpcsm_locations', [ $this, 'wpcsm_locations' ] );
				}

				public static function get_settings() {
					return apply_filters( 'wpclv_get_settings', self::$settings );
				}

				public static function get_setting( $name, $default = false ) {
					if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
						$setting = self::$settings[ $name ];
					} else {
						$setting = get_option( 'wpclv_' . $name, $default );
					}

					return apply_filters( 'wpclv_get_setting', $setting, $name, $default );
				}

				public static function localization( $key = '', $default = '' ) {
					$str = '';

					if ( ! empty( $key ) && ! empty( self::$localization[ $key ] ) ) {
						$str = self::$localization[ $key ];
					} elseif ( ! empty( $default ) ) {
						$str = $default;
					}

					return apply_filters( 'wpclv_localization_' . $key, $str );
				}

				function init() {
					// load text-domain
					load_plugin_textdomain( 'wpc-linked-variation', false, basename( WPCLV_DIR ) . '/languages/' );

					// shortcode
					add_shortcode( 'wpclv', [ $this, 'shortcode' ] );

					// post type
					$labels = [
						'name'          => _x( 'Linked Variations', 'Post Type General Name', 'wpc-linked-variation' ),
						'singular_name' => _x( 'Linked Variation', 'Post Type Singular Name', 'wpc-linked-variation' ),
						'add_new_item'  => esc_html__( 'Add New Linked Variation', 'wpc-linked-variation' ),
						'add_new'       => esc_html__( 'Add New', 'wpc-linked-variation' ),
						'edit_item'     => esc_html__( 'Edit Linked Variation', 'wpc-linked-variation' ),
						'update_item'   => esc_html__( 'Update Linked Variation', 'wpc-linked-variation' ),
						'search_items'  => esc_html__( 'Search Linked Variation', 'wpc-linked-variation' ),
					];

					$args = [
						'label'               => apply_filters( 'wpclv_post_type_label', esc_html__( 'Linked Variation', 'wpc-linked-variation' ) ),
						'labels'              => apply_filters( 'wpclv_post_type_labels', $labels ),
						'supports'            => [ 'title' ],
						'hierarchical'        => false,
						'public'              => false,
						'show_ui'             => true,
						'show_in_menu'        => true,
						'show_in_nav_menus'   => true,
						'show_in_admin_bar'   => true,
						'menu_position'       => 28,
						'menu_icon'           => 'dashicons-admin-links',
						'can_export'          => true,
						'has_archive'         => false,
						'exclude_from_search' => true,
						'publicly_queryable'  => false,
						'capability_type'     => 'post',
						'show_in_rest'        => false,
					];

					register_post_type( 'wpclv', apply_filters( 'wpclv_post_type_args', $args ) );
				}

				function shortcode( $attrs ) {
					$output = '';
					$attrs  = shortcode_atts( [ 'id' => null, 'limit' => 0, 'hide' => '' ], $attrs, 'wpclv' );

					if ( ! $attrs['id'] ) {
						global $product;

						if ( $product && is_a( $product, 'WC_Product' ) ) {
							$attrs['id'] = $product->get_id();
						}
					}

					if ( $attrs['id'] ) {
						ob_start();

						if ( self::enable_ajax( 'shortcode' ) ) {
							// render wrapper only
							echo '<div class="' . esc_attr( apply_filters( 'wpclv_wrap_class', 'wpclv-attributes wpclv-attributes-shortcode wpclv-attributes-' . $attrs['id'], 'shortcode' ) ) . '" data-id="' . esc_attr( $attrs['id'] ) . '"></div>';
						} else {
							self::render_content( $attrs['id'], absint( $attrs['limit'] ), $attrs['hide'], 'shortcode' );
						}

						$output = ob_get_clean();
					}

					return apply_filters( 'wpclv_shortcode', $output, $attrs );
				}

				function add_meta_boxes() {
					add_meta_box( 'wpclv_configuration', esc_html__( 'Configuration', 'wpc-linked-variation' ), [
						$this,
						'meta_box_callback'
					], 'wpclv', 'advanced', 'low' );
				}

				function meta_box_callback( $post ) {
					$post_id = $post->ID;
					$link    = get_post_meta( $post_id, 'wpclv_link', true );
					?>
                    <table class="form-table">
                        <tr>
                            <td colspan="2">
                                <div class="wpclv_links">
									<?php self::get_link( $link ?? null ); ?>
                                </div>
                            </td>
                        </tr>
                    </table>
					<?php
				}

				function get_link( $link = null ) {
					$link_attributes = $link['attributes'] ?? [];
					$link_images     = $link['images'] ?? [];
					$link_swatches   = $link['swatches'] ?? [];
					$link_source     = $link['source'] ?? 'products';
					$link_products   = $link['products'] ?? '';
					$link_terms      = $link['terms'] ?? '';
					$link_limit      = $link['limit'] ?? 500;
					$link_orderby    = $link['orderby'] ?? 'default';
					$link_order      = $link['order'] ?? 'default';
					$terms_all       = $link['terms_all'] ?? '';
					$wc_attributes   = wc_get_attribute_taxonomies();
					$attributes      = [];

					foreach ( $wc_attributes as $wc_attribute ) {
						$attributes[ 'id:' . $wc_attribute->attribute_id ] = $wc_attribute->attribute_label;
					}
					?>
                    <div class="wpclv_link">
                        <div class="wpclv_tr">
                            <div class="wpclv_th"><?php esc_html_e( 'Source', 'wpc-linked-variation' ); ?></div>
                            <div class="wpclv_td">
                                <label> <select class="wpclv-source" name="wpclv_link[source]">
                                        <option value="products" <?php selected( $link_source, 'products' ); ?>><?php esc_html_e( 'Products', 'wpc-linked-variation' ); ?></option>
										<?php
										$taxonomies = get_object_taxonomies( 'product', 'objects' ); //$taxonomies = get_taxonomies( [ 'object_type' => [ 'product' ] ], 'objects' );

										foreach ( $taxonomies as $taxonomy ) {
											echo '<option value="' . esc_attr( $taxonomy->name ) . '" ' . ( $link_source === $taxonomy->name ? 'selected' : '' ) . ' disabled>' . esc_html( $taxonomy->label ) . '</option>';
										}
										?>
                                    </select> </label>
                            </div>
                        </div>
                        <div class="wpclv_tr wpclv-source-hide wpclv-source-products">
                            <div class="wpclv_th">
								<?php esc_html_e( 'Products', 'wpc-linked-variation' ); ?>
                            </div>
                            <div class="wpclv_td wpclv_link_td">
                                <input class="wpclv-products" type="hidden" name="wpclv_link[products]"
                                       value="<?php echo esc_attr( $link_products ); ?>"/>
                                <label>
                                    <select class="wc-product-search wpclv-product-search" multiple="multiple"
                                            data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wpc-linked-variation' ); ?>"
                                            data-action="woocommerce_json_search_products">
										<?php
										$_product_ids = explode( ',', $link_products );

										foreach ( $_product_ids as $_product_id ) {
											$_product = wc_get_product( $_product_id );

											if ( $_product ) {
												echo '<option value="' . esc_attr( $_product_id ) . '" selected>' . wp_kses_post( $_product->get_formatted_name() ) . '</option>';
											}
										}
										?>
                                    </select> </label>
                            </div>
                        </div>
                        <div class="wpclv_tr wpclv-source-hide wpclv-source-terms">
                            <div class="wpclv_th wpclv-source-terms-label">
								<?php esc_html_e( 'Terms', 'wpc-linked-variation' ); ?>
                            </div>
                            <div class="wpclv_td wpclv_link_td" style="display: flex; align-items: center;">
                                <span class="wpclv-source-terms-all"><label><input type="checkbox"
                                                                                   name="wpclv_link[terms_all]"
                                                                                   value="1" <?php echo( ! empty( $terms_all ) ? 'checked' : '' ); ?>/><?php esc_html_e( 'All (any)', 'wpc-linked-variation' ); ?></label> <u><?php esc_html_e( 'or', 'wpc-linked-variation' ); ?></u> </span>
                                <span class="wpclv-source-terms-select" style="flex-grow: 1;">
                                    <input class="wpclv-terms-val" type="hidden" style="width: 100%"
                                           name="wpclv_link[terms]" value="<?php echo esc_attr( $link_terms ); ?>"/>
                                    <?php
                                    if ( ! is_array( $link_terms ) ) {
	                                    $link_terms = array_map( 'trim', explode( ',', $link_terms ) );
                                    }
                                    ?>
                                    <label>
                                        <select class="wpclv-terms-select" multiple="multiple"
                                                data-<?php echo esc_attr( $link_source ); ?>="<?php echo esc_attr( implode( ',', $link_terms ) ); ?>">
                                            <?php
                                            if ( ! empty( $link_terms ) ) {
	                                            foreach ( $link_terms as $t ) {
		                                            if ( $term = get_term_by( 'slug', $t, $link_source ) ) {
			                                            echo '<option value="' . esc_attr( $t ) . '" selected>' . esc_html( $term->name ) . '</option>';
		                                            }
	                                            }
                                            }
                                            ?>
                                        </select>
                                    </label>
                                </span>
                            </div>
                        </div>
                        <div class="wpclv_tr wpclv-source-hide wpclv-source-terms">
                            <div class="wpclv_th">
								<?php esc_html_e( 'Limit', 'wpc-linked-variation' ); ?>
                            </div>
                            <div class="wpclv_td wpclv_link_td">
                                <label>
                                    <input type="number" name="wpclv_link[limit]" min="-1" step="1"
                                           value="<?php echo esc_attr( $link_limit ); ?>"/>
                                </label>
                            </div>
                        </div>
                        <div class="wpclv_tr wpclv-source-hide wpclv-source-terms">
                            <div class="wpclv_th">
								<?php esc_html_e( 'Orderby', 'wpc-linked-variation' ); ?>
                            </div>
                            <div class="wpclv_td wpclv_link_td">
                                <label> <select name="wpclv_link[orderby]">
                                        <option value="default" <?php selected( $link_orderby, 'default' ); ?>><?php esc_html_e( 'Default', 'wpc-linked-variation' ); ?></option>
                                        <option value="none" <?php selected( $link_orderby, 'none' ); ?>><?php esc_html_e( 'None', 'wpc-linked-variation' ); ?></option>
                                        <option value="ID" <?php selected( $link_orderby, 'ID' ); ?>><?php esc_html_e( 'ID', 'wpc-linked-variation' ); ?></option>
                                        <option value="name" <?php selected( $link_orderby, 'name' ); ?>><?php esc_html_e( 'Name', 'wpc-linked-variation' ); ?></option>
                                        <option value="type" <?php selected( $link_orderby, 'type' ); ?>><?php esc_html_e( 'Type', 'wpc-linked-variation' ); ?></option>
                                        <option value="rand" <?php selected( $link_orderby, 'rand' ); ?>><?php esc_html_e( 'Rand', 'wpc-linked-variation' ); ?></option>
                                        <option value="date" <?php selected( $link_orderby, 'date' ); ?>><?php esc_html_e( 'Date', 'wpc-linked-variation' ); ?></option>
                                        <option value="price" <?php selected( $link_orderby, 'price' ); ?>><?php esc_html_e( 'Price', 'wpc-linked-variation' ); ?></option>
                                        <option value="modified" <?php selected( $link_orderby, 'modified' ); ?>><?php esc_html_e( 'Modified', 'wpc-linked-variation' ); ?></option>
                                    </select> </label>
                            </div>
                        </div>
                        <div class="wpclv_tr wpclv-source-hide wpclv-source-terms">
                            <div class="wpclv_th">
								<?php esc_html_e( 'Order', 'wpc-linked-variation' ); ?>
                            </div>
                            <div class="wpclv_td wpclv_link_td">
                                <label> <select name="wpclv_link[order]">
                                        <option value="default" <?php selected( $link_order, 'default' ); ?>><?php esc_html_e( 'Default', 'wpc-linked-variation' ); ?></option>
                                        <option value="DESC" <?php selected( $link_order, 'DESC' ); ?>><?php esc_html_e( 'DESC', 'wpc-linked-variation' ); ?></option>
                                        <option value="ASC" <?php selected( $link_order, 'ASC' ); ?>><?php esc_html_e( 'ASC', 'wpc-linked-variation' ); ?></option>
                                    </select> </label>
                            </div>
                        </div>
                        <div class="wpclv_tr wpclv_tr_heading">
                            <div class="wpclv_th"><?php esc_html_e( 'Linked by (attributes)', 'wpc-linked-variation' ); ?></div>
                        </div>
                        <div class="wpclv_tr">
                            <div class="wpclv_td wpclv_link_td">
								<?php
								$saved_attributes = [];

								foreach ( $link_attributes as $attr ) {
									$saved_attributes[ $attr ] = $attributes[ $attr ];
								}

								$merge_attributes = array_merge( $saved_attributes, $attributes );

								if ( $merge_attributes ) {
									echo '<div class="wpclv-attributes">';

									foreach ( $merge_attributes as $attribute_id => $attribute_label ) {
										if ( $attribute = wc_get_attribute( (int) filter_var( $attribute_id, FILTER_SANITIZE_NUMBER_INT ) ) ) {
											echo '<div class="wpclv-attribute"><span class="move">' . esc_html__( 'Move', 'wpc-linked-variation' ) . '</span><span class="checkbox"><label><input type="checkbox" name="wpclv_link[attributes][]" value="' . $attribute_id . '" ' . ( is_array( $link_attributes ) && in_array( $attribute_id, $link_attributes ) ? 'checked' : '' ) . '/>' . $attribute_label . ' <span class="slug">' . $attribute->slug . '</span></label></span><span class="display"><label><input type="checkbox" class="wpclv_display_checkbox" name="wpclv_link[images][]" value="' . $attribute_id . '" ' . ( is_array( $link_images ) && in_array( $attribute_id, $link_images ) ? 'checked' : '' ) . '/>' . esc_html__( 'Show images', 'wpc-linked-variation' ) . '</label></span><span class="display"><label><input type="checkbox" class="wpclv_display_checkbox" name="wpclv_link[dropdown][]" value="' . $attribute_id . '" ' . ( isset( $link['dropdown'] ) && is_array( $link['dropdown'] ) && in_array( $attribute_id, $link['dropdown'] ) ? 'checked' : '' ) . '/>' . esc_html__( 'Use dropdown', 'wpc-linked-variation' ) . '</label></span><span class="display"><label><input type="checkbox" class="wpclv_display_checkbox" name="wpclv_link[swatches][]" value="' . $attribute_id . '" ' . ( isset( $link_swatches ) && is_array( $link_swatches ) && in_array( $attribute_id, $link_swatches ) ? 'checked' : '' ) . '/>' . esc_html__( 'Use swatches', 'wpc-linked-variation' ) . '</label></span></div>';
										}
									}

									echo '</div>';
								}
								?>
                            </div>
                        </div>
                        <div class="wpclv_tr">
                            <div class="wpclv_th"></div>
                            <div class="wpclv_td" style="text-align: end;">
								<?php add_thickbox(); ?>
                                To use swatches, you need to install and activate
                                <a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=wpc-variation-swatches&TB_iframe=true&width=800&height=550' ) ); ?>"
                                   class="thickbox" title="WPC Variation Swatches">WPC Variation Swatches</a>.
                            </div>
                        </div>
                    </div>
					<?php
				}

				function save_meta_boxes( $post_id ) {
					if ( isset( $_POST['wpclv_link'] ) ) {
						update_post_meta( $post_id, 'wpclv_link', self::sanitize_array( $_POST['wpclv_link'] ) );
					}
				}

				function sanitize_array( $arr ) {
					foreach ( (array) $arr as $k => $v ) {
						if ( is_array( $v ) ) {
							$arr[ $k ] = self::sanitize_array( $v );
						} else {
							$arr[ $k ] = sanitize_text_field( $v );
						}
					}

					return $arr;
				}

				function custom_column( $columns ) {
					return [
						'cb'                  => $columns['cb'],
						'title'               => esc_html__( 'Title', 'wpc-linked-variation' ),
						'wpclv_configuration' => esc_html__( 'Configuration', 'wpc-linked-variation' ),
						'date'                => esc_html__( 'Date', 'wpc-linked-variation' )
					];
				}

				function custom_column_value( $column, $postid ) {
					if ( $column == 'wpclv_configuration' ) {
						$info = get_post_meta( $postid, 'wpclv_link', true );

						if ( is_array( $info ) && ! empty( $info ) ) {
							if ( isset( $info['source'] ) ) {
								// source
								switch ( $info['source'] ) {
									case 'products':
										echo esc_html__( 'Products', 'wpc-linked-variation' ) . ': ';
										$names = [];

										if ( ! empty( $info['products'] ) ) {
											$products = explode( ',', $info['products'] );

											foreach ( $products as $pid ) {
												if ( $name = get_the_title( $pid ) ) {
													$names[] = $name;
												}
											}

											echo implode( ', ', $names );
										}

										break;
									case 'categories':
										echo esc_html__( 'Categories', 'wpc-linked-variation' ) . ': ' . $info['categories'];

										break;
									case 'tags':
										echo esc_html__( 'Tags', 'wpc-linked-variation' ) . ': ' . $info['tags'];

										break;
									default:
										if ( $taxonomy = get_taxonomy( $info['source'] ) ) {
											echo esc_html( $taxonomy->label ) . ': ' . $info['terms'];
										}

										break;
								}
							}

							if ( ! empty( $info['attributes'] ) ) {
								// attributes
								echo '<br/>';
								echo esc_html__( 'Attributes', 'wpc-linked-variation' ) . ': ';
								$attr_names = [];

								foreach ( $info['attributes'] as $attr_id ) {
									if ( $attr = wc_get_attribute( absint( str_replace( 'id:', '', $attr_id ) ) ) ) {
										$attr_names[] = $attr->name;
									}
								}

								if ( ! empty( $attr_names ) ) {
									echo implode( ', ', $attr_names );
								}
							}
						}
					}
				}

				function enqueue_scripts() {
					if ( self::get_setting( 'tooltip_library', 'hint' ) === 'hint' ) {
						wp_enqueue_style( 'hint', WPCLV_URI . 'assets/libs/hint/hint.css' );
					}

					if ( self::get_setting( 'tooltip_library', 'hint' ) === 'tippy' ) {
						wp_enqueue_script( 'popper', WPCLV_URI . 'assets/libs/tippy/popper.min.js', [ 'jquery' ], WPCLV_VERSION );
						wp_enqueue_script( 'tippy', WPCLV_URI . 'assets/libs/tippy/tippy-bundle.umd.min.js', [ 'jquery' ], WPCLV_VERSION );
					}

					wp_enqueue_style( 'wpclv-frontend', WPCLV_URI . 'assets/css/frontend.css', [], WPCLV_VERSION );
					wp_enqueue_script( 'wpclv-frontend', WPCLV_URI . 'assets/js/frontend.js', [ 'jquery' ], WPCLV_VERSION, true );
					wp_localize_script( 'wpclv-frontend', 'wpclv_vars', [
							'wc_ajax_url'     => WC_AJAX::get_endpoint( '%%endpoint%%' ),
							'nonce'           => wp_create_nonce( 'wpclv-security' ),
							'ajax_single'     => self::enable_ajax( 'single' ),
							'ajax_shortcode'  => self::enable_ajax( 'shortcode' ),
							'tooltip_library' => self::get_setting( 'tooltip_library', 'hint' ),
						]
					);
				}

				function admin_enqueue_scripts() {
					wp_enqueue_style( 'wpclv-backend', WPCLV_URI . 'assets/css/backend.css', [ 'woocommerce_admin_styles' ], WPCLV_VERSION );
					wp_enqueue_script( 'wpclv-backend', WPCLV_URI . 'assets/js/backend.js', [
						'jquery',
						'wc-enhanced-select',
						'jquery-ui-sortable',
						'selectWoo'
					], WPCLV_VERSION, true );
					wp_localize_script( 'wpclv-backend', 'wpclv_vars', [
							'wpclv_nonce' => wp_create_nonce( 'wpclv_nonce' )
						]
					);
				}

				function register_settings() {
					// settings
					register_setting( 'wpclv_settings', 'wpclv_settings' );
					// localization
					register_setting( 'wpclv_localization', 'wpclv_localization' );
				}

				function admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Linked Variation', 'wpc-linked-variation' ), esc_html__( 'Linked Variation', 'wpc-linked-variation' ), 'manage_options', 'wpclever-wpclv', [
						$this,
						'admin_menu_content'
					] );
				}

				function admin_menu_content() {
					add_thickbox();
					$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
					?>
                    <div class="wpclever_settings_page wrap">
                        <div class="wpclever_settings_page_header">
                            <a class="wpclever_settings_page_header_logo" href="https://wpclever.net/"
                               target="_blank" title="Visit wpclever.net"></a>
                            <div class="wpclever_settings_page_header_text">
                                <div class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Linked Variation', 'wpc-linked-variation' ) . ' ' . esc_html( WPCLV_VERSION ) . ' ' . ( defined( 'WPCLV_PREMIUM' ) ? '<span class="premium" style="display: none">' . esc_html__( 'Premium', 'wpc-linked-variation' ) . '</span>' : '' ); ?></div>
                                <div class="wpclever_settings_page_desc about-text">
                                    <p>
										<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-linked-variation' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                        <br/>
                                        <a href="<?php echo esc_url( WPCLV_REVIEWS ); ?>"
                                           target="_blank"><?php esc_html_e( 'Reviews', 'wpc-linked-variation' ); ?></a>
                                        |
                                        <a href="<?php echo esc_url( WPCLV_CHANGELOG ); ?>"
                                           target="_blank"><?php esc_html_e( 'Changelog', 'wpc-linked-variation' ); ?></a>
                                        |
                                        <a href="<?php echo esc_url( WPCLV_DISCUSSION ); ?>"
                                           target="_blank"><?php esc_html_e( 'Discussion', 'wpc-linked-variation' ); ?></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <h2></h2>
						<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                            <div class="notice notice-success is-dismissible">
                                <p><?php esc_html_e( 'Settings updated.', 'wpc-linked-variation' ); ?></p>
                            </div>
						<?php } ?>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpclv&tab=settings' ) ); ?>"
                                   class="<?php echo $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
									<?php esc_html_e( 'Settings', 'wpc-linked-variation' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpclv&tab=localization' ) ); ?>"
                                   class="<?php echo $active_tab === 'localization' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
									<?php esc_html_e( 'Localization', 'wpc-linked-variation' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpclv' ) ); ?>"
                                   class="nav-tab">
									<?php esc_html_e( 'Linked Variations', 'wpc-linked-variation' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpclv&tab=premium' ) ); ?>"
                                   class="<?php echo $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>"
                                   style="color: #c9356e">
									<?php esc_html_e( 'Premium Version', 'wpc-linked-variation' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>"
                                   class="nav-tab">
									<?php esc_html_e( 'Essential Kit', 'wpc-linked-variation' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( $active_tab === 'settings' ) {
								$position              = self::get_setting( 'position', 'above' );
								$position_archive      = self::get_setting( 'position_archive', 'no' );
								$archive_limit         = self::get_setting( 'archive_limit', '10' );
								$tooltip_library       = self::get_setting( 'tooltip_library', 'hint' );
								$tooltip_position      = self::get_setting( 'tooltip_position', 'top' );
								$tooltip_content       = self::get_setting( 'tooltip_content', 'attribute' );
								$hide_empty            = self::get_setting( 'hide_empty', 'yes' );
								$exclude_hidden        = self::get_setting( 'exclude_hidden', 'no' );
								$exclude_unpurchasable = self::get_setting( 'exclude_unpurchasable', 'no' );
								$link                  = self::get_setting( 'link', 'yes' );
								$nofollow              = self::get_setting( 'nofollow', 'no' );
								?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr>
                                            <th><?php esc_html_e( 'Position on single page', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[position]">
                                                        <option value="above" <?php selected( $position, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'wpc-linked-variation' ); ?></option>
                                                        <option value="below" <?php selected( $position, 'below' ); ?>><?php esc_html_e( 'Under the add to cart button', 'wpc-linked-variation' ); ?></option>
                                                        <option value="below_title" <?php selected( $position, 'below_title' ); ?>><?php esc_html_e( 'Under the title', 'wpc-linked-variation' ); ?></option>
                                                        <option value="below_price" <?php selected( $position, 'below_price' ); ?>><?php esc_html_e( 'Under the price', 'wpc-linked-variation' ); ?></option>
                                                        <option value="below_excerpt" <?php selected( $position, 'below_excerpt' ); ?>><?php esc_html_e( 'Under the excerpt', 'wpc-linked-variation' ); ?></option>
                                                        <option value="no" <?php selected( $position, 'no' ); ?>><?php esc_html_e( 'None (hide it)', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                                <span class="description"><?php esc_html_e( 'Choose the position to show the linked variations on single product page.', 'wpc-linked-variation' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Position on archive page', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[position_archive]">
                                                        <option value="above" <?php selected( $position_archive, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'wpc-linked-variation' ); ?></option>
                                                        <option value="below" <?php selected( $position_archive, 'below' ); ?>><?php esc_html_e( 'Under the add to cart button', 'wpc-linked-variation' ); ?></option>
                                                        <option value="below_title" <?php selected( $position_archive, 'below_title' ); ?>><?php esc_html_e( 'Under the title', 'wpc-linked-variation' ); ?></option>
                                                        <option value="below_price" <?php selected( $position_archive, 'below_price' ); ?>><?php esc_html_e( 'Under the price', 'wpc-linked-variation' ); ?></option>
                                                        <option value="no" <?php selected( $position_archive, 'no' ); ?>><?php esc_html_e( 'None (hide it)', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                                <span class="description"><?php esc_html_e( 'Choose the position to show the linked variations on archive page.', 'wpc-linked-variation' ); ?></span>
                                                <p>
													<?php esc_html_e( 'Limit', 'wpc-linked-variation' ); ?>
                                                    <label>
                                                        <input name="wpclv_settings[archive_limit]" type="number"
                                                               min="0" max="500"
                                                               value="<?php echo esc_attr( $archive_limit ); ?>"/>
                                                    </label>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Shortcode', 'wpc-linked-variation' ); ?></th>
                                            <td>
												<?php printf( /* translators: shortcode */ esc_html__( 'You can use the shortcode %s to show the list where you want.', 'wpc-linked-variation' ), '<code>[wpclv]</code>' ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Tooltip library', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[tooltip_library]">
                                                        <option value="hint" <?php selected( $tooltip_library, 'hint' ); ?>><?php esc_html_e( 'Hint.css', 'wpc-linked-variation' ); ?></option>
                                                        <option value="tippy" <?php selected( $tooltip_library, 'tippy' ); ?>><?php esc_html_e( 'Tippy.js', 'wpc-linked-variation' ); ?></option>
                                                        <option value="none" <?php selected( $tooltip_library, 'none' ); ?>><?php esc_html_e( 'None (Disable)', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                                <span class="description">Read more about <a
                                                            href="https://kushagra.dev/lab/hint/" target="_blank">Hint.css</a> and <a
                                                            href="https://atomiks.github.io/tippyjs/v6/getting-started/"
                                                            target="_blank">Tippy.js</a>. Use Tippy.js if you want to show the attribute/product's name, description, image on the tooltip.</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Tooltip position', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[tooltip_position]">
                                                        <option value="top" <?php selected( $tooltip_position, 'top' ); ?>><?php esc_html_e( 'Top', 'wpc-linked-variation' ); ?></option>
                                                        <option value="right" <?php selected( $tooltip_position, 'right' ); ?>><?php esc_html_e( 'Right', 'wpc-linked-variation' ); ?></option>
                                                        <option value="bottom" <?php selected( $tooltip_position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'wpc-linked-variation' ); ?></option>
                                                        <option value="left" <?php selected( $tooltip_position, 'left' ); ?>><?php esc_html_e( 'Left', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                                <span class="description">For Hint.css only.</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Tooltip content', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[tooltip_content]">
                                                        <option value="attribute" <?php selected( $tooltip_content, 'attribute' ); ?>><?php esc_html_e( 'Attribute information', 'wpc-linked-variation' ); ?></option>
                                                        <option value="product" <?php selected( $tooltip_content, 'product' ); ?>><?php esc_html_e( 'Product information', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Hide empty attribute terms', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[hide_empty]">
                                                        <option value="yes" <?php selected( $hide_empty, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-linked-variation' ); ?></option>
                                                        <option value="no" <?php selected( $hide_empty, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                                <span class="description"><?php esc_html_e( 'Hide attribute terms that haven\'t any products.', 'wpc-linked-variation' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Exclude hidden product', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[exclude_hidden]">
                                                        <option value="yes" <?php selected( $exclude_hidden, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-linked-variation' ); ?></option>
                                                        <option value="no" <?php selected( $exclude_hidden, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Exclude unpurchasable product', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[exclude_unpurchasable]">
                                                        <option value="yes" <?php selected( $exclude_unpurchasable, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-linked-variation' ); ?></option>
                                                        <option value="no" <?php selected( $exclude_unpurchasable, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Link to individual product', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[link]">
                                                        <option value="yes" <?php selected( $link, 'yes' ); ?>><?php esc_html_e( 'Open in the same tab', 'wpc-linked-variation' ); ?></option>
                                                        <option value="yes_blank" <?php selected( $link, 'yes_blank' ); ?>><?php esc_html_e( 'Open in the new tab', 'wpc-linked-variation' ); ?></option>
                                                        <option value="yes_popup" <?php selected( $link, 'yes_popup' ); ?>><?php esc_html_e( 'Open quick view popup', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label> <span class="description">If you choose "Open quick view popup", please install <a
                                                            href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woo-smart-quick-view&TB_iframe=true&width=800&height=550' ) ); ?>"
                                                            class="thickbox" title="WPC Smart Quick View">WPC Smart Quick View</a> to make it work.</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Use nofollow links', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label> <select name="wpclv_settings[nofollow]">
                                                        <option value="yes" <?php selected( $nofollow, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-linked-variation' ); ?></option>
                                                        <option value="no" <?php selected( $nofollow, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-linked-variation' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'wpclv_settings' ); ?><?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab === 'localization' ) { ?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th scope="row"><?php esc_html_e( 'General', 'wpc-linked-variation' ); ?></th>
                                            <td>
												<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'wpc-linked-variation' ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'More', 'wpc-linked-variation' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="text" class="regular-text"
                                                           name="wpclv_localization[more]"
                                                           value="<?php echo esc_attr( self::localization( 'more' ) ); ?>"
                                                           placeholder="<?php /* translators: count */
													       esc_attr_e( '+%d More', 'wpc-linked-variation' ); ?>"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'wpclv_localization' ); ?><?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab == 'premium' ) { ?>
                                <div class="wpclever_settings_page_content_text">
                                    <p>
                                        Get the Premium Version just $29!
                                        <a href="https://wpclever.net/downloads/wpc-linked-variation?utm_source=pro&utm_medium=wpclv&utm_campaign=wporg"
                                           target="_blank">https://wpclever.net/downloads/wpc-linked-variation</a>
                                    </p>
                                    <p><strong>Extra features for Premium Version:</strong></p>
                                    <ul style="margin-bottom: 0">
                                        <li>- Use Categories, Tags, or Attributes as the source.</li>
                                        <li>- Get the lifetime update & premium support.</li>
                                    </ul>
                                </div>
							<?php } ?>
                        </div><!-- /.wpclever_settings_page_content -->
                        <div class="wpclever_settings_page_suggestion">
                            <div class="wpclever_settings_page_suggestion_label">
                                <span class="dashicons dashicons-yes-alt"></span> Suggestion
                            </div>
                            <div class="wpclever_settings_page_suggestion_content">
                                <div>
                                    To display custom engaging real-time messages on any wished positions, please
                                    install
                                    <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC
                                        Smart Messages</a> plugin. It's free!
                                </div>
                                <div>
                                    Wanna save your precious time working on variations? Try our brand-new free plugin
                                    <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC
                                        Variation Bulk Editor</a> and
                                    <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC
                                        Variation Duplicator</a>.
                                </div>
                            </div>
                        </div>
                    </div>
					<?php
				}

				public static function term( $type, $attribute, $term, $active = false, $product_id = 0, $imperfect = false ) {
					$link             = self::get_setting( 'link', 'yes' );
					$nofollow         = self::get_setting( 'nofollow', 'no' ) === 'yes';
					$tooltip_library  = self::get_setting( 'tooltip_library', 'hint' );
					$tooltip_position = self::get_setting( 'tooltip_position', 'top' );
					$tooltip_content  = self::get_setting( 'tooltip_content', 'attribute' );

					if ( $tooltip_library === 'tippy' ) {
						$tooltip_class = 'wpclv-tippy-tooltip tippy--' . $tooltip_position;

						if ( ( $tooltip_content === 'product' ) && $product_id ) {
							$thumbnail_size  = apply_filters( 'wpclv_product_thumbnail_size', 'woocommerce_thumbnail' );
							$thumbnail_size  = apply_filters( 'wpclv_product_thumbnail_size_tippy', $thumbnail_size );
							$tooltip_content = 'data-tippy-content="' . esc_attr( htmlentities( '<span class="wpclv-tippy wpclv-tippy-' . esc_attr( $term->term_id ) . '"><span class="wpclv-tippy-inner"><span class="wpclv-tippy-title">' . esc_html( get_the_title( $product_id ) ) . '</span>' . ( get_post_thumbnail_id( $product_id ) ? '<span class="wpclv-tippy-image">' . get_the_post_thumbnail( $product_id, $thumbnail_size ) . '</span>' : '' ) . ( ! empty( get_the_excerpt( $product_id ) ) ? '<span class="wpclv-tippy-desc">' . get_the_excerpt( $product_id ) . '</span>' : '' ) . '</span></span>' ) ) . '"';
						} else {
							$tooltip_content = 'data-tippy-content="' . esc_attr( htmlentities( '<span class="wpclv-tippy wpclv-tippy-' . esc_attr( $term->term_id ) . '"><span class="wpclv-tippy-inner"><span class="wpclv-tippy-title">' . esc_html( $term->name ) . '</span>' . ( ! empty( $term->description ) ? '<span class="wpclv-tippy-desc">' . $term->description . '</span>' : '' ) . '</span></span>' ) ) . '"';
						}
					} elseif ( $tooltip_library === 'hint' ) {
						$tooltip_class = 'hint--' . $tooltip_position;

						if ( ( $tooltip_content === 'product' ) && $product_id ) {
							$tooltip_content = 'aria-label="' . esc_attr( get_the_title( $product_id ) ) . '"';
						} else {
							$tooltip_content = 'aria-label="' . esc_attr( $term->name ) . '"';
						}
					} else {
						$tooltip_class   = '';
						$tooltip_content = '';
					}

					$tooltip_content = apply_filters( 'wpclv_tooltip_content', $tooltip_content, $type, $attribute, $term, $active, $product_id, $imperfect );
					$tooltip_class   = apply_filters( 'wpclv_tooltip_class', $tooltip_class, $type, $attribute, $term, $active, $product_id, $imperfect );
					$term_class      = apply_filters( 'wpclv_term_class', 'wpclv-term ' . $tooltip_class . ( $active ? ' active' : '' ) . ( $imperfect ? ' imperfect' : '' ), $type, $attribute, $term, $active, $product_id, $imperfect );

					switch ( $type ) {
						case 'swatches':
							$attribute_type = $attribute->type ?? 'select';

							if ( ! in_array( $attribute_type, [ 'button', 'color', 'image' ] ) ) {
								$attribute_type = 'button';
							}

							switch ( $attribute_type ) {
								case 'button' :
									$val = get_term_meta( $term->term_id, 'wpcvs_button', true ) ?: $term->name;

									$html = '<div class="' . esc_attr( $term_class ) . '" ' . $tooltip_content . '>';

									if ( $product_id && ! $active ) {
										$html .= '<a href="' . ( $link === 'yes_popup' ? 'javascript:void(0);' : get_the_permalink( $product_id ) ) . '" ' . ( $nofollow ? 'rel="nofollow"' : '' ) . ' title="' . esc_attr( apply_filters( 'wpclv_term_title', get_the_title( $product_id ), $term, $product_id ) ) . '" ' . ( $link === 'yes_popup' ? 'class="woosq-link" data-id="' . $product_id . '"' : '' ) . ' ' . ( $link === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . esc_html( $val ) . '</a>';
									} else {
										$html .= '<span>' . esc_html( $val ) . '</span>';
									}

									$html .= '</div>';

									break;
								case 'color':
									$val = get_term_meta( $term->term_id, 'wpcvs_color', true ) ?: '';

									$html = '<div class="' . esc_attr( 'wpclv-term-color ' . $term_class ) . '" ' . $tooltip_content . '>';

									if ( $product_id && ! $active ) {
										$html .= '<a ' . ( ! empty( $val ) ? 'style="background-color: ' . esc_attr( $val ) . '"' : '' ) . ' href="' . ( $link === 'yes_popup' ? 'javascript:void(0);' : get_the_permalink( $product_id ) ) . '" ' . ( $nofollow ? 'rel="nofollow"' : '' ) . ' title="' . esc_attr( apply_filters( 'wpclv_term_title', get_the_title( $product_id ), $term, $product_id ) ) . '" ' . ( $link === 'yes_popup' ? 'class="woosq-link" data-id="' . $product_id . '"' : '' ) . ' ' . ( $link === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . esc_html( $val ) . '</a>';
									} else {
										$html .= '<span ' . ( ! empty( $val ) ? 'style="background-color: ' . esc_attr( $val ) . '"' : '' ) . '>' . esc_html( $val ) . '</span>';
									}

									$html .= '</div>';

									break;
								case 'image':
									$val = get_term_meta( $term->term_id, 'wpcvs_image', true ) ? wp_get_attachment_thumb_url( get_term_meta( $term->term_id, 'wpcvs_image', true ) ) : wc_placeholder_img_src();

									$html = '<div class="' . esc_attr( 'wpclv-term-image ' . $term_class ) . '" ' . $tooltip_content . '>';

									if ( $product_id && ! $active ) {
										$html .= '<a href="' . ( $link === 'yes_popup' ? 'javascript:void(0);' : get_the_permalink( $product_id ) ) . '" ' . ( $nofollow ? 'rel="nofollow"' : '' ) . ' title="' . esc_attr( apply_filters( 'wpclv_term_title', get_the_title( $product_id ), $term, $product_id ) ) . '" ' . ( $link === 'yes_popup' ? 'class="woosq-link" data-id="' . $product_id . '"' : '' ) . ' ' . ( $link === 'yes_blank' ? 'target="_blank"' : '' ) . '><img src="' . esc_url( $val ) . '" alt="' . esc_attr( $term->name ) . '"/></a>';
									} else {
										$html .= '<span><img src="' . esc_url( $val ) . '" alt="' . esc_attr( $term->name ) . '"/></span>';
									}

									$html .= '</div>';

									break;
								default:
									$html = '';
							}

							echo apply_filters( 'wpclv_term_swatches', $html, $term, $product_id );

							break;
						case 'image':
							$html = '<div class="' . esc_attr( 'wpclv-term-image ' . $term_class ) . '" ' . $tooltip_content . '>';

							if ( $product_id && ( $product_thumbnail_id = get_post_thumbnail_id( $product_id ) ) ) {
								$thumbnail_size = apply_filters( 'wpclv_product_thumbnail_size', 'woocommerce_thumbnail' );
								$term_image     = '<img src="' . wp_get_attachment_image_url( $product_thumbnail_id, $thumbnail_size ) . '" alt="' . esc_attr( $term->name ) . '"/>';
							} else {
								$term_image = wc_placeholder_img();
							}

							if ( $product_id && ! $active ) {
								$html .= '<a href="' . ( $link === 'yes_popup' ? 'javascript:void(0);' : get_the_permalink( $product_id ) ) . '" ' . ( $nofollow ? 'rel="nofollow"' : '' ) . ' title="' . esc_attr( apply_filters( 'wpclv_term_title', get_the_title( $product_id ), $term, $product_id ) ) . '" ' . ( $link === 'yes_popup' ? 'class="woosq-link" data-id="' . $product_id . '"' : '' ) . ' ' . ( $link === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . $term_image . '</a>';
							} else {
								$html .= '<span>' . $term_image . '</span>';
							}

							$html .= '</div>';

							echo apply_filters( 'wpclv_term_image', $html, $term, $product_id );

							break;
						case 'dropdown':
							$html = '';

							if ( $product_id && ! $active ) {
								$html .= '<option value="' . esc_url( get_the_permalink( $product_id ) ) . '">' . esc_html( $term->name ) . '</option>';
							} else {
								if ( $product_id ) {
									$html .= '<option value="' . esc_url( get_the_permalink( $product_id ) ) . '" selected>' . esc_html( $term->name ) . '</option>';
								} else {
									$html .= '<option disabled selected>' . esc_html( $term->name ) . '</option>';
								}
							}

							echo apply_filters( 'wpclv_term_dropdown', $html, $term, $product_id );

							break;
						case 'button':
							$html = '<div class="' . esc_attr( 'wpclv-term-button ' . $term_class ) . '" ' . $tooltip_content . '>';

							if ( $product_id && ! $active ) {
								$html .= '<a href="' . ( $link === 'yes_popup' ? 'javascript:void(0);' : get_the_permalink( $product_id ) ) . '" ' . ( $nofollow ? 'rel="nofollow"' : '' ) . ' title="' . esc_attr( apply_filters( 'wpclv_term_title', get_the_title( $product_id ), $term, $product_id ) ) . '" ' . ( $link === 'yes_popup' ? 'class="woosq-link" data-id="' . $product_id . '"' : '' ) . ' ' . ( $link === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . esc_html( $term->name ) . '</a>';
							} else {
								$html .= '<span>' . esc_html( $term->name ) . '</span>';
							}

							$html .= '</div>';

							echo apply_filters( 'wpclv_term_button', $html, $term, $product_id );

							break;
					}
				}

				public static function ajax_load_content() {
					if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpclv-security' ) ) {
						die( 'Permissions check failed!' );
					}

					if ( ! isset( $_POST['id'] ) ) {
						return;
					}

					self::render_content( sanitize_text_field( $_POST['id'] ), 0, '', 'ajax' );
					wp_die();
				}

				public static function render_archive( $product_id = null ) {
					if ( ! $product_id ) {
						global $product;
						$_product   = $product;
						$product_id = $_product->get_id();
					} else {
						$_product = wc_get_product( $product_id );
					}

					if ( ! $_product || ! is_a( $_product, 'WC_Product' ) ) {
						return;
					}

					if ( self::enable_ajax( 'archive' ) ) {
						// render wrapper only
						echo '<div class="' . esc_attr( apply_filters( 'wpclv_wrap_class', 'wpclv-attributes wpclv-attributes-archive wpclv-attributes-' . $product_id, 'archive' ) ) . '" data-id="' . esc_attr( $product_id ) . '"></div>';
					} else {
						$limit = absint( self::get_setting( 'archive_limit', '10' ) );
						self::render_content( $product_id, $limit, '', 'archive' );
					}
				}

				public static function render_single( $product_id = null, $limit = 0, $hide = '' ) {
					if ( ! $product_id ) {
						global $product;
						$_product   = $product;
						$product_id = $_product->get_id();
					} else {
						$_product = wc_get_product( $product_id );
					}

					if ( ! $_product || ! is_a( $_product, 'WC_Product' ) ) {
						return;
					}

					if ( self::enable_ajax( 'single' ) ) {
						// render wrapper only
						echo '<div class="' . esc_attr( apply_filters( 'wpclv_wrap_class', 'wpclv-attributes wpclv-attributes-single wpclv-attributes-' . $product_id, 'single' ) ) . '" data-id="' . esc_attr( $product_id ) . '"></div>';
					} else {
						self::render_content( $product_id, $limit, $hide, 'single' );
					}
				}

				public static function render_content( $product_id = null, $limit = 0, $hide = '', $context = 'default' ) {
					if ( ! $product_id ) {
						global $product;
						$_product   = $product;
						$product_id = $_product->get_id();
					} else {
						$_product = wc_get_product( $product_id );
					}

					if ( ! $product_id || ! is_a( $_product, 'WC_Product' ) ) {
						return;
					}

					if ( ! self::enable_cache( 'content' ) || ( false === ( $link_content = get_transient( 'wpclv_linked_content_' . $context . '_' . $product_id ) ) ) ) {
						$link_data = self::get_linked_data( $_product, $context );

						if ( empty( $link_data ) ) {
							do_action( 'wpclv_no_linked_data', $product_id, $context );

							return;
						}

						// exclude current product
						$link_products = self::get_linked_products( $link_data, $context );
						$link_products = apply_filters( 'wpclv_linked_products', array_diff( $link_products, [ $product_id ] ), $product_id );

						if ( empty( $link_products ) ) {
							do_action( 'wpclv_no_linked_products', $product_id, $context );

							return;
						}

						$link_attributes     = $link_data['attributes'] ?? [];
						$link_images         = $link_data['images'] ?? [];
						$link_swatches       = $link_data['swatches'] ?? [];
						$link_dropdown       = $link_data['dropdown'] ?? [];
						$hide_attributes     = ! empty( $hide ) ? explode( ',', $hide ) : [];
						$assigned_attributes = array_keys( $_product->get_attributes() );
						$product_attributes  = [];

						foreach ( $assigned_attributes as $assigned_attribute ) {
							$product_attributes[ $assigned_attribute ] = wc_get_product_terms( $product_id, $assigned_attribute, [ 'fields' => 'ids' ] );
						}

						ob_start();

						if ( ! empty( $link_attributes ) ) {
							do_action( 'wpclv_wrap_above', $link_attributes );

							if ( $context !== 'ajax' ) {
								echo '<div class="' . esc_attr( apply_filters( 'wpclv_wrap_class', 'wpclv-attributes wpclv-attributes-' . $context . ' wpclv-attributes-' . $product_id, $context ) ) . '" data-id="' . esc_attr( $product_id ) . '">';
							}

							do_action( 'wpclv_wrap_before', $link_attributes );

							$link_attributes_ids = array_map( function ( $e ) {
								return (int) filter_var( $e, FILTER_SANITIZE_NUMBER_INT );
							}, $link_attributes );

							foreach ( $link_attributes as $link_attribute ) {
								$link_attribute_id = (int) filter_var( $link_attribute, FILTER_SANITIZE_NUMBER_INT );
								$attribute         = wc_get_attribute( $link_attribute_id );

								if ( ! $attribute || in_array( $attribute->slug, $hide_attributes ) ) {
									continue;
								}

								$attribute_limit = apply_filters( 'wpclv_attribute_limit', $limit, $attribute, $context );
								$args            = apply_filters( 'wpclv_get_terms_args', [
									'taxonomy'   => $attribute->slug,
									'hide_empty' => false
								], $attribute, $context );
								$terms           = get_terms( $args );
								$current_terms   = wc_get_product_terms( $product_id, $attribute->slug, [ 'fields' => 'slugs' ] );

								if ( empty( $terms ) || empty( $current_terms ) ) {
									continue;
								}

								$use_images   = in_array( $link_attribute, $link_images );
								$use_dropdown = in_array( $link_attribute, $link_dropdown );
								$use_swatches = in_array( $link_attribute, $link_swatches ) && class_exists( 'WPCleverWpcvs' );
								?>
                                <div class="<?php echo esc_attr( 'wpclv-attribute wpclv-attribute-' . $attribute->slug ); ?>">
									<?php do_action( 'wpclv_attribute_before', $attribute ); ?>
                                    <div class="wpclv-attribute-label">
										<?php
										do_action( 'wpclv_attribute_label_before', $attribute );
										echo apply_filters( 'wpclv_attribute_label', esc_html( wc_attribute_label( $attribute->name ) ), $attribute );
										do_action( 'wpclv_attribute_label_after', $attribute );
										?>
                                    </div>
                                    <div class="wpclv-terms">
										<?php
										do_action( 'wpclv_attribute_terms_before', $attribute );
										$count           = 0;
										$linked_products = [];

										if ( $use_dropdown ) {
											echo '<select class="wpclv-terms-select">';
										}

										foreach ( $terms as $term ) {
											if ( in_array( $term->slug, $current_terms ) ) {
												// current product
												if ( ! $attribute_limit || $count < $attribute_limit ) {
													if ( $use_images ) {
														self::term( 'image', $attribute, $term, true, $product_id );
													} elseif ( $use_swatches ) {
														self::term( 'swatches', $attribute, $term, true, $product_id );
													} elseif ( $use_dropdown ) {
														self::term( 'dropdown', $attribute, $term, true, $product_id );
													} else {
														self::term( 'button', $attribute, $term, true, $product_id );
													}
												}

												$count ++;
											} else {
												$tax_query     = [];
												$tax_query_ori = [
													'taxonomy' => $term->taxonomy,
													'term'     => $term->slug
												];

												foreach ( $product_attributes as $product_attribute_key => $product_attribute ) {
													$product_attribute_id = wc_attribute_taxonomy_id_by_name( $product_attribute_key );

													if ( ! in_array( $product_attribute_id, $link_attributes_ids ) ) {
														continue;
													}

													if ( $term->taxonomy != $product_attribute_key ) {
														$tax_query[] = [
															'taxonomy' => $product_attribute_key,
															'term'     => $product_attribute
														];
													}
												}

												array_push( $tax_query, $tax_query_ori );

												if ( $linked_id = self::get_linked_product_id( $tax_query, $link_products, $linked_products ) ) {
													$linked_products[] = $linked_id;

													if ( ! $attribute_limit || $count < $attribute_limit ) {
														if ( $use_images ) {
															self::term( 'image', $attribute, $term, false, $linked_id );
														} elseif ( $use_swatches ) {
															self::term( 'swatches', $attribute, $term, false, $linked_id );
														} elseif ( $use_dropdown ) {
															self::term( 'dropdown', $attribute, $term, false, $linked_id );
														} else {
															self::term( 'button', $attribute, $term, false, $linked_id );
														}
													}

													$count ++;
												} else {
													if ( $linked_id = apply_filters( 'wpclv_get_imperfect_product', true ) ? self::get_linked_product_id( [ $tax_query_ori ], $link_products, $linked_products ) : 0 ) {
														$linked_products[] = $linked_id;

														if ( ! $attribute_limit || $count < $attribute_limit ) {
															if ( $use_images ) {
																self::term( 'image', $attribute, $term, false, $linked_id, true );
															} elseif ( $use_swatches ) {
																self::term( 'swatches', $attribute, $term, false, $linked_id, true );
															} elseif ( $use_dropdown ) {
																self::term( 'dropdown', $attribute, $term, false, $linked_id, true );
															} else {
																self::term( 'button', $attribute, $term, false, $linked_id, true );
															}
														}

														$count ++;
													} elseif ( self::get_setting( 'hide_empty', 'yes' ) === 'no' ) {
														if ( ! $attribute_limit || $count < $attribute_limit ) {
															if ( $use_images ) {
																self::term( 'image', $attribute, $term, false );
															} elseif ( $use_swatches ) {
																self::term( 'swatches', $attribute, $term, false );
															} elseif ( $use_dropdown ) {
																self::term( 'dropdown', $attribute, $term, false );
															} else {
																self::term( 'button', $attribute, $term, false );
															}
														}

														$count ++;
													}
												}
											}
										}

										if ( $use_dropdown ) {
											echo '</select>';
										}

										if ( $attribute_limit && ( $attribute_limit < $count ) ) {
											echo '<div class="wpclv-more"><a href="' . esc_url( $_product->get_permalink() ) . '">' . sprintf( apply_filters( 'wpclv_more', self::localization( 'more', /* translators: count */ esc_html__( '+%d More', 'wpc-linked-variation' ) ), ( $count - $attribute_limit ) ), ( $count - $attribute_limit ) ) . '</a></div>';
										}

										do_action( 'wpclv_attribute_terms_after', $attribute );
										?>
                                    </div>
									<?php do_action( 'wpclv_attribute_after', $attribute ); ?>
                                </div>
							<?php }

							do_action( 'wpclv_wrap_after', $link_attributes );

							if ( $context !== 'ajax' ) {
								echo '</div><!-- /wpclv-attributes -->';
							}

							do_action( 'wpclv_wrap_below', $link_attributes );
						}

						$link_content = ob_get_clean();

						if ( self::enable_cache( 'content' ) ) {
							set_transient( 'wpclv_linked_content_' . $context . '_' . $product_id, $link_content, 24 * HOUR_IN_SECONDS );
						}
					}

					echo $link_content;
				}

				public static function get_linked_products( $link_data, $context = 'default' ) {
					$link_id = $link_data['id'] ?? 0;

					if ( ! self::enable_cache( 'products' ) || ( false === ( $link_products = get_transient( 'wpclv_linked_products_' . $link_id ) ) ) ) {
						$link_products = [];
						$link_source   = $link_data['source'] ?? 'products';

						if ( ( $link_source === 'products' ) && ! empty( $link_data['products'] ) ) {
							$link_products = explode( ',', $link_data['products'] );
						}

						// exclude hidden or unpurchasable
						if ( ( self::get_setting( 'exclude_hidden', 'no' ) === 'yes' ) || ( self::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes' ) ) {
							foreach ( $link_products as $key => $link_product_id ) {
								$link_product = wc_get_product( $link_product_id );

								if ( ! $link_product || ( ! $link_product->is_visible() && ( self::get_setting( 'exclude_hidden', 'no' ) === 'yes' ) ) || ( ( ! $link_product->is_purchasable() || ! $link_product->is_in_stock() ) && ( self::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes' ) ) ) {
									unset( $link_products[ $key ] );
								}
							}
						}

						if ( self::enable_cache( 'products' ) ) {
							set_transient( 'wpclv_linked_products_' . $link_id, $link_products, 24 * HOUR_IN_SECONDS );
						}
					}

					return apply_filters( 'wpclv_get_linked_products', $link_products, $link_data, $context );
				}

				public static function get_linked_data( $product, $context = 'default' ) {
					if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
						return false;
					}

					$product_id = $product->get_id();

					if ( ! self::enable_cache( 'data' ) || ( false === ( $linked_data = get_transient( 'wpclv_linked_data_' . $product_id ) ) ) ) {
						$linked_data = [];
						$links       = get_posts( apply_filters( 'wpclv_get_linked_args', [
							'post_type'              => 'wpclv',
							'post_status'            => 'publish',
							'posts_per_page'         => 500, // get all linked
							'no_found_rows'          => true,
							'update_post_term_cache' => false,
							'update_post_meta_cache' => false,
							'fields'                 => 'ids'
						] ) );

						if ( ! empty( $links ) ) {
							foreach ( $links as $link_id ) {
								$link = get_post_meta( $link_id, 'wpclv_link', true );

								if ( ! empty( $link ) ) {
									$link_source = $link['source'] ?? 'products';

									if ( ( $link_source === 'products' ) && ! empty( $link['products'] ) ) {
										$product_ids = explode( ',', $link['products'] );

										if ( in_array( $product_id, $product_ids ) ) {
											$linked_data       = $link;
											$linked_data['id'] = $link_id;
											break;
										}
									}

									if ( ( $link_source === 'categories' ) && ! empty( $link['categories'] ) ) {
										$categories = array_map( 'trim', explode( ',', $link['categories'] ) );

										if ( has_term( $categories, 'product_cat', $product_id ) ) {
											$linked_data       = $link;
											$linked_data['id'] = $link_id;
											break;
										}
									}

									if ( ( $link_source === 'tags' ) && ! empty( $link['tags'] ) ) {
										$tags = array_map( 'trim', explode( ',', $link['tags'] ) );

										if ( has_term( $tags, 'product_tag', $product_id ) ) {
											$linked_data       = $link;
											$linked_data['id'] = $link_id;
											break;
										}
									}

									if ( ! in_array( $link_source, [ 'products', 'categories', 'tags' ] ) ) {
										$terms_all = $link['terms_all'] ?? '';

										if ( ! empty( $terms_all ) ) {
											if ( has_term( '', $link_source, $product_id ) ) {
												$linked_data       = $link;
												$linked_data['id'] = $link_id;
												break;
											}
										} elseif ( ! empty( $link['terms'] ) ) {
											$terms = array_map( 'trim', explode( ',', $link['terms'] ) );

											if ( has_term( $terms, $link_source, $product_id ) ) {
												$linked_data       = $link;
												$linked_data['id'] = $link_id;
												break;
											}
										}
									}
								}
							}
						}

						if ( self::enable_cache( 'data' ) ) {
							set_transient( 'wpclv_linked_data_' . $product_id, $linked_data, 24 * HOUR_IN_SECONDS );
						}
					}

					return apply_filters( 'wpclv_get_linked_data', $linked_data, $product, $context );
				}

				public static function enable_cache( $context = 'default' ) {
					return apply_filters( 'wpclv_enable_cache', false, $context );
				}

				public static function enable_ajax( $context = 'default' ) {
					return apply_filters( 'wpclv_enable_ajax', false, $context );
				}

				// return post id
				public static function get_linked_product_id( $tax_query, $link_products = [], $linked_products = [] ) {
					if ( apply_filters( 'wpclv_exclude_linked_products', true ) ) {
						// don't get a product twice
						$get_products = array_diff( $link_products, $linked_products );
					} else {
						$get_products = $link_products;
					}

					if ( empty( $get_products ) || empty( $tax_query ) ) {
						return false;
					}

					foreach ( $get_products as $product_id ) {
						$valid = true;

						foreach ( $tax_query as $tax ) {
							if ( ! has_term( $tax['term'], $tax['taxonomy'], $product_id ) ) {
								$valid = false;
								break;
							}
						}

						if ( $valid ) {
							return $product_id;
						}
					}

					return false;
				}

				function action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$settings         = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpclv&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-linked-variation' ) . '</a>';
						$linked           = '<a href="' . esc_url( admin_url( 'edit.php?post_type=wpclv' ) ) . '">' . esc_html__( 'Linked Variations', 'wpc-linked-variation' ) . '</a>';
						$links['premium'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpclv&tab=premium' ) ) . '">' . esc_html__( 'Premium Version', 'wpc-linked-variation' ) . '</a>';
						array_unshift( $links, $settings, $linked );
					}

					return (array) $links;
				}

				function row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = [
							'support' => '<a href="' . esc_url( WPCLV_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-linked-variation' ) . '</a>',
						];

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}

				function ajax_search_term() {
					$return = [];

					$args = [
						'taxonomy'   => sanitize_text_field( $_REQUEST['taxonomy'] ),
						'orderby'    => 'id',
						'order'      => 'ASC',
						'hide_empty' => false,
						'fields'     => 'all',
						'name__like' => sanitize_text_field( $_REQUEST['q'] ),
					];

					$terms = get_terms( $args );

					if ( count( $terms ) ) {
						foreach ( $terms as $term ) {
							$return[] = [ $term->slug, $term->name ];
						}
					}

					wp_send_json( $return );
				}

				function wpcsm_locations( $locations ) {
					$locations['WPC Linked Variation'] = [
						'wpclv_wrap_above'             => esc_html__( 'Before container', 'wpc-linked-variation' ),
						'wpclv_wrap_below'             => esc_html__( 'After container', 'wpc-linked-variation' ),
						'wpclv_wrap_before'            => esc_html__( 'Before attributes', 'wpc-linked-variation' ),
						'wpclv_wrap_after'             => esc_html__( 'After attributes', 'wpc-linked-variation' ),
						'wpclv_attribute_before'       => esc_html__( 'Before attribute', 'wpc-linked-variation' ),
						'wpclv_attribute_after'        => esc_html__( 'After attribute', 'wpc-linked-variation' ),
						'wpclv_attribute_label_before' => esc_html__( 'Before attribute label', 'wpc-linked-variation' ),
						'wpclv_attribute_label_after'  => esc_html__( 'After attribute label', 'wpc-linked-variation' ),
					];

					return $locations;
				}
			}

			return WPCleverWpclv::instance();
		}

		return null;
	}
}

if ( ! function_exists( 'wpclv_notice_wc' ) ) {
	function wpclv_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Linked Variation</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
