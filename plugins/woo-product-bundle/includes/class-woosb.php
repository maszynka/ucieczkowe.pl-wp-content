<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverWoosb' ) && class_exists( 'WC_Product' ) ) {
	class WPCleverWoosb {
		protected static $instance = null;
		protected static $image_size = 'woocommerce_thumbnail';
		protected static $types = [
			'bundle',
			'woosb',
			'composite',
			'grouped',
			'woosg',
			'external',
			'variable',
			'variation'
		];

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			// Init
			add_action( 'init', [ $this, 'init' ] );

			// Add image to variation
			add_filter( 'woocommerce_available_variation', [ $this, 'available_variation' ], 10, 3 );

			// Settings
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );

			// Enqueue frontend scripts
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// Enqueue backend scripts
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

			// Backend AJAX
			add_action( 'wp_ajax_woosb_update_search_settings', [ $this, 'ajax_update_search_settings' ] );
			add_action( 'wp_ajax_woosb_get_search_results', [ $this, 'ajax_get_search_results' ] );

			// Add to selector
			add_filter( 'product_type_selector', [ $this, 'product_type_selector' ] );

			// Product data tabs
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );

			// Product tab
			if ( ( WPCleverWoosb_Helper()->get_setting( 'bundled_position', 'above' ) === 'tab' ) || ( WPCleverWoosb_Helper()->get_setting( 'bundles_position', 'no' ) === 'tab' ) ) {
				add_filter( 'woocommerce_product_tabs', [ $this, 'product_tabs' ] );
			}

			// Bundled products position
			switch ( WPCleverWoosb_Helper()->get_setting( 'bundled_position', 'above' ) ) {
				case 'below_title';
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundled' ], 6 );
					break;
				case 'below_price':
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundled' ], 11 );
					break;
				case 'below_excerpt';
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundled' ], 21 );
					break;
			}

			// Bundles position
			switch ( WPCleverWoosb_Helper()->get_setting( 'bundles_position', 'no' ) ) {
				case 'above':
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundles' ], 29 );
					break;
				case 'below':
					add_action( 'woocommerce_single_product_summary', [ $this, 'product_summary_bundles' ], 31 );
					break;
			}

			// Product data panels
			add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
			add_action( 'woocommerce_process_product_meta_woosb', [ $this, 'process_product_meta_woosb' ] );

			// Product price class
			add_filter( 'woocommerce_product_price_class', [ $this, 'product_price_class' ] );

			// Add to cart form & button
			add_action( 'woocommerce_woosb_add_to_cart', [ $this, 'add_to_cart_form' ] );
			add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'add_to_cart_button' ] );

			// Add to cart
			add_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart', [ $this, 'found_in_cart' ], 10, 2 );
			add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'add_to_cart_validation' ], 10, 3 );
			add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 2 );
			add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart' ], 10, 6 );
			add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'get_cart_item_from_session' ], 10, 2 );

			// Cart item
			add_filter( 'woocommerce_cart_item_name', [ $this, 'cart_item_name' ], 10, 2 );
			add_filter( 'woocommerce_cart_item_quantity', [ $this, 'cart_item_quantity' ], 10, 3 );
			add_filter( 'woocommerce_cart_item_remove_link', [ $this, 'cart_item_remove_link' ], 10, 2 );
			add_filter( 'woocommerce_cart_contents_count', [ $this, 'cart_contents_count' ] );
			add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 10, 2 );
			add_filter( 'woocommerce_cart_item_price', [ $this, 'cart_item_price' ], 9999, 2 );
			add_filter( 'woocommerce_cart_item_subtotal', [ $this, 'cart_item_subtotal' ], 9999, 2 );

			// Order
			add_filter( 'woocommerce_get_item_count', [ $this, 'get_item_count' ], 10, 3 );

			// Mini cart item visible
			add_filter( 'woocommerce_widget_cart_item_visible', [ $this, 'mini_cart_item_visible' ], 10, 2 );

			// Cart item visible
			add_filter( 'woocommerce_cart_item_visible', [ $this, 'cart_item_visible' ], 10, 2 );
			add_filter( 'woocommerce_checkout_cart_item_visible', [ $this, 'cart_item_visible' ], 10, 2 );

			// Order item visible
			add_filter( 'woocommerce_order_item_visible', [ $this, 'order_item_visible' ], 10, 2 );

			// Item class
			if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled', 'no' ) !== 'yes' ) {
				add_filter( 'woocommerce_cart_item_class', [ $this, 'cart_item_class' ], 10, 2 );
				add_filter( 'woocommerce_mini_cart_item_class', [ $this, 'cart_item_class' ], 10, 2 );
				add_filter( 'woocommerce_order_item_class', [ $this, 'cart_item_class' ], 10, 2 );
			}

			// Get item data
			if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled', 'no' ) === 'yes_text' || WPCleverWoosb_Helper()->get_setting( 'hide_bundled', 'no' ) === 'yes_list' ) {
				add_filter( 'woocommerce_get_item_data', [ $this, 'cart_item_meta' ], 10, 2 );
			}

			// Order item
			add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'create_order_line_item' ], 10, 3 );
			add_filter( 'woocommerce_order_item_name', [ $this, 'cart_item_name' ], 10, 2 );
			add_filter( 'woocommerce_order_formatted_line_subtotal', [ $this, 'formatted_line_subtotal' ], 10, 2 );

			if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled_order', 'no' ) === 'yes_text' || WPCleverWoosb_Helper()->get_setting( 'hide_bundled_order', 'no' ) === 'yes_list' ) {
				// Hide bundled products, just show the main product on order details (order confirmation or emails)
				add_action( 'woocommerce_order_item_meta_start', [ $this, 'order_item_meta_start' ], 10, 2 );
			}

			// Admin order
			add_action( 'woocommerce_ajax_add_order_item_meta', [ $this, 'ajax_add_order_item_meta' ], 10, 3 );
			add_filter( 'woocommerce_hidden_order_itemmeta', [ $this, 'hidden_order_itemmeta' ] );
			add_action( 'woocommerce_before_order_itemmeta', [ $this, 'before_order_itemmeta' ], 10, 2 );

			// Undo remove
			add_action( 'woocommerce_restore_cart_item', [ $this, 'restore_cart_item' ] );

			// Add settings link
			add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

			// Loop add-to-cart
			add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'loop_add_to_cart_link' ], 99, 2 );

			// Before calculate totals
			add_action( 'woocommerce_before_mini_cart_contents', [ $this, 'before_mini_cart_contents' ], 9999 );
			add_action( 'woocommerce_before_calculate_totals', [ $this, 'before_calculate_totals' ], 9999 );

			// Shipping
			add_filter( 'woocommerce_cart_shipping_packages', [ $this, 'cart_shipping_packages' ], 9 );
			add_filter( 'woocommerce_cart_contents_weight', [ $this, 'cart_contents_weight' ], 9 );

			// Price html
			add_filter( 'woocommerce_get_price_html', [ $this, 'get_price_html' ], 99, 2 );

			// Order again
			add_filter( 'woocommerce_order_again_cart_item_data', [ $this, 'order_again_cart_item_data' ], 10, 2 );
			add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'cart_loaded_from_session' ] );

			// Coupons
			add_filter( 'woocommerce_coupon_is_valid_for_product', [ $this, 'coupon_is_valid_for_product' ], 10, 4 );

			// Update stock status
			add_action( 'woocommerce_product_set_stock_status', [ $this, 'update_stock_status' ], 10, 3 );
			add_action( 'woocommerce_variation_set_stock_status', [ $this, 'update_stock_status' ], 10, 3 );

			// Admin
			add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );

			// Bulk action
			add_action( 'current_screen', [ $this, 'bulk_actions' ] );

			// Emails
			add_action( 'woocommerce_no_stock_notification', [ $this, 'no_stock_notification' ], 99 );
			add_action( 'woocommerce_low_stock_notification', [ $this, 'low_stock_notification' ], 99 );

			// Search filters
			if ( WPCleverWoosb_Helper()->get_setting( 'search_sku', 'no' ) === 'yes' ) {
				add_action( 'pre_get_posts', [ $this, 'search_sku' ], 99 );
			}

			if ( WPCleverWoosb_Helper()->get_setting( 'search_exact', 'no' ) === 'yes' ) {
				add_action( 'pre_get_posts', [ $this, 'search_exact' ], 99 );
			}

			if ( WPCleverWoosb_Helper()->get_setting( 'search_sentence', 'no' ) === 'yes' ) {
				add_action( 'pre_get_posts', [ $this, 'search_sentence' ], 99 );
			}

			// WPC Variations Radio Buttons
			add_filter( 'woovr_default_selector', [ $this, 'woovr_default_selector' ], 99, 4 );

			// WPC Smart Messages
			add_filter( 'wpcsm_locations', [ $this, 'wpcsm_locations' ] );

			// Export
			add_filter( 'woocommerce_product_export_meta_value', [ $this, 'export_process' ], 10, 3 );

			// Import
			add_filter( 'woocommerce_product_import_pre_insert_product_object', [ $this, 'import_process' ], 10, 2 );

			// WPML
			if ( function_exists( 'wpml_loaded' ) && apply_filters( 'woosb_wpml_filters', true ) ) {
				add_filter( 'woosb_item_id', [ $this, 'wpml_item_id' ], 99 );
			}
		}

		function init() {
			// load text-domain
			load_plugin_textdomain( 'woo-product-bundle', false, basename( WOOSB_DIR ) . '/languages/' );

			// image size
			self::$image_size = apply_filters( 'woosb_image_size', self::$image_size );

			// shortcode
			add_shortcode( 'woosb_form', [ $this, 'shortcode_form' ] );
			add_shortcode( 'woosb_bundled', [ $this, 'shortcode_bundled' ] );
			add_shortcode( 'woosb_bundles', [ $this, 'shortcode_bundles' ] );
		}

		function available_variation( $data, $variable, $variation ) {
			if ( apply_filters( 'woosb_add_variation_image', true ) && ( $image_id = $variation->get_image_id() ) ) {
				$data['woosb_image'] = wp_get_attachment_image( $image_id, self::$image_size );
			}

			return $data;
		}

		function register_settings() {
			// settings
			register_setting( 'woosb_settings', 'woosb_settings' );
			// localization
			register_setting( 'woosb_localization', 'woosb_localization' );
		}

		function admin_menu() {
			add_submenu_page( 'wpclever', esc_html__( 'WPC Product Bundles', 'woo-product-bundle' ), esc_html__( 'Product Bundles', 'woo-product-bundle' ), 'manage_options', 'wpclever-woosb', [
				$this,
				'admin_menu_content'
			] );
		}

		function admin_menu_content() {
			add_thickbox();
			$active_tab     = sanitize_key( $_GET['tab'] ?? 'settings' );
			$active_section = sanitize_key( $_GET['section'] ?? 'none' );
			$settings_class = 'wpclever_settings_page_content wpclever_settings_tab_' . $active_tab . ' wpclever_settings_section_' . $active_section;
			?>
            <div class="wpclever_settings_page wrap">
                <div class="wpclever_settings_page_header">
                    <a class="wpclever_settings_page_header_logo" href="https://wpclever.net/"
                       target="_blank" title="Visit wpclever.net"></a>
                    <div class="wpclever_settings_page_header_text">
                        <div class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Product Bundles', 'woo-product-bundle' ) . ' ' . esc_html( WOOSB_VERSION ) . ' ' . ( defined( 'WOOSB_PREMIUM' ) ? '<span class="premium" style="display: none">' . esc_html__( 'Premium', 'woo-product-bundle' ) . '</span>' : '' ); ?></div>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'woo-product-bundle' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WOOSB_REVIEWS ); ?>"
                                   target="_blank"><?php esc_html_e( 'Reviews', 'woo-product-bundle' ); ?></a> |
                                <a href="<?php echo esc_url( WOOSB_CHANGELOG ); ?>"
                                   target="_blank"><?php esc_html_e( 'Changelog', 'woo-product-bundle' ); ?></a> |
                                <a href="<?php echo esc_url( WOOSB_DISCUSSION ); ?>"
                                   target="_blank"><?php esc_html_e( 'Discussion', 'woo-product-bundle' ); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
                <h2></h2>
				<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e( 'Settings updated.', 'woo-product-bundle' ); ?></p>
                    </div>
				<?php } ?>
                <div class="wpclever_settings_page_nav">
                    <h2 class="nav-tab-wrapper">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=how' ) ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'how' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'How to use?', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=settings' ) ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'settings' && $active_section === 'none' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Settings', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=localization' ) ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'localization' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Localization', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=settings&section=compatible' ) ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'settings' && $active_section === 'compatible' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Compatible', 'woo-product-bundle' ); ?>
                        </a> <a href="<?php echo esc_url( WOOSB_DOCS ); ?>" class="nav-tab" target="_blank">
							<?php esc_html_e( 'Docs', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=premium' ) ); ?>"
                           class="<?php echo esc_attr( $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>"
                           style="color: #c9356e">
							<?php esc_html_e( 'Premium Version', 'woo-product-bundle' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
							<?php esc_html_e( 'Essential Kit', 'woo-product-bundle' ); ?>
                        </a>
                    </h2>
                </div>
                <div class="<?php echo esc_attr( $settings_class ); ?>">
					<?php if ( $active_tab === 'how' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p>
								<?php esc_html_e( 'When creating the product, please choose product data is "Smart Bundle" then you can see the search field to start search and add products to the bundle.', 'woo-product-bundle' ); ?>
                            </p>
                            <p>
                                <img src="<?php echo esc_url( WOOSB_URI . 'assets/images/how-01.jpg' ); ?>" alt=""/>
                            </p>
                        </div>
					<?php } elseif ( $active_tab === 'settings' ) {
						$price_format          = WPCleverWoosb_Helper()->get_setting( 'price_format', 'from_min' );
						$price_from            = WPCleverWoosb_Helper()->get_setting( 'bundled_price_from', 'sale_price' );
						$bundled_position      = WPCleverWoosb_Helper()->get_setting( 'bundled_position', 'above' );
						$layout                = WPCleverWoosb_Helper()->get_setting( 'layout', 'list' );
						$variations_selector   = WPCleverWoosb_Helper()->get_setting( 'variations_selector', 'default' );
						$selector_interface    = WPCleverWoosb_Helper()->get_setting( 'selector_interface', 'unset' );
						$bundled_thumb         = WPCleverWoosb_Helper()->get_setting( 'bundled_thumb', 'yes' );
						$bundled_qty           = WPCleverWoosb_Helper()->get_setting( 'bundled_qty', 'yes' );
						$bundled_desc          = WPCleverWoosb_Helper()->get_setting( 'bundled_description', 'no' );
						$bundled_price         = WPCleverWoosb_Helper()->get_setting( 'bundled_price', 'price' );
						$bundled_link          = WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' );
						$plus_minus            = WPCleverWoosb_Helper()->get_setting( 'plus_minus', 'no' );
						$change_image          = WPCleverWoosb_Helper()->get_setting( 'change_image', 'yes' );
						$change_price          = WPCleverWoosb_Helper()->get_setting( 'change_price', 'yes' );
						$bundles_position      = WPCleverWoosb_Helper()->get_setting( 'bundles_position', 'no' );
						$coupon_restrictions   = WPCleverWoosb_Helper()->get_setting( 'coupon_restrictions', 'no' );
						$exclude_unpurchasable = WPCleverWoosb_Helper()->get_setting( 'exclude_unpurchasable', 'no' );
						$contents_count        = WPCleverWoosb_Helper()->get_setting( 'cart_contents_count', 'bundle' );
						$hide_bundle_name      = WPCleverWoosb_Helper()->get_setting( 'hide_bundle_name', 'no' );
						$hide_bundled          = WPCleverWoosb_Helper()->get_setting( 'hide_bundled', 'no' );
						$hide_bundled_order    = WPCleverWoosb_Helper()->get_setting( 'hide_bundled_order', 'no' );
						$hide_bundled_mc       = WPCleverWoosb_Helper()->get_setting( 'hide_bundled_mini_cart', 'no' );
						$wcpdf_hide_bundles    = WPCleverWoosb_Helper()->get_setting( 'compatible_wcpdf_hide_bundles', 'no' );
						$wcpdf_hide_bundled    = WPCleverWoosb_Helper()->get_setting( 'compatible_wcpdf_hide_bundled', 'no' );
						$pklist_hide_bundles   = WPCleverWoosb_Helper()->get_setting( 'compatible_pklist_hide_bundles', 'no' );
						$pklist_hide_bundled   = WPCleverWoosb_Helper()->get_setting( 'compatible_pklist_hide_bundled', 'no' );
						?>
                        <form method="post" action="options.php">
                            <table class="form-table">
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'General', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Price format', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[price_format]" class="woosb_price_format">
                                                <option value="from_min" <?php selected( $price_format, 'from_min' ); ?>><?php esc_html_e( 'From min price', 'woo-product-bundle' ); ?></option>
                                                <option value="min_only" <?php selected( $price_format, 'min_only' ); ?>><?php esc_html_e( 'Min price only', 'woo-product-bundle' ); ?></option>
                                                <option value="min_max" <?php selected( $price_format, 'min_max' ); ?>><?php esc_html_e( 'Min - max', 'woo-product-bundle' ); ?></option>
                                                <option value="normal" <?php selected( $price_format, 'normal' ); ?>><?php esc_html_e( 'Regular and sale price', 'woo-product-bundle' ); ?></option>
                                                <option value="custom" <?php selected( $price_format, 'custom' ); ?>><?php esc_html_e( 'Custom', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Choose the price format for bundle on the shop/archive page.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="woosb_tr_show_if_price_format_custom">
                                    <th><?php esc_html_e( 'Default custom display price', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="regular-text"
                                                   name="woosb_settings[price_format_custom]"
                                                   placeholder="<?php /* translators: dynamic price */
											       esc_attr_e( 'before %s after', 'woo-product-bundle' ); ?>"
                                                   value="<?php /* translators: dynamic price */
											       echo WPCleverWoosb_Helper()->get_setting( 'price_format_custom', esc_html__( 'before %s after', 'woo-product-bundle' ) ); ?>"/>
                                        </label>
                                        <p class="description"><?php /* translators: dynamic price */
											esc_html_e( 'Use %s to show the dynamic price between your custom text. You still can overwrite it in each bundle.', 'woo-product-bundle' ); ?></p>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Calculate bundled prices', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundled_price_from]">
                                                <option value="sale_price" <?php selected( $price_from, 'sale_price' ); ?>><?php esc_html_e( 'from Sale price', 'woo-product-bundle' ); ?></option>
                                                <option value="regular_price" <?php selected( $price_from, 'regular_price' ); ?>><?php esc_html_e( 'from Regular price', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Bundled pricing methods: from Sale price (default) or Regular price.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'Bundled products', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Position', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundled_position]">
                                                <option value="above" <?php selected( $bundled_position, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'woo-product-bundle' ); ?></option>
                                                <option value="below" <?php selected( $bundled_position, 'below' ); ?>><?php esc_html_e( 'Under the add to cart button', 'woo-product-bundle' ); ?></option>
                                                <option value="below_title" <?php selected( $bundled_position, 'below_title' ); ?>><?php esc_html_e( 'Under the title', 'woo-product-bundle' ); ?></option>
                                                <option value="below_price" <?php selected( $bundled_position, 'below_price' ); ?>><?php esc_html_e( 'Under the price', 'woo-product-bundle' ); ?></option>
                                                <option value="below_excerpt" <?php selected( $bundled_position, 'below_excerpt' ); ?>><?php esc_html_e( 'Under the excerpt', 'woo-product-bundle' ); ?></option>
                                                <option value="tab" <?php selected( $bundled_position, 'tab' ); ?>><?php esc_html_e( 'In a new tab', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $bundled_position, 'no' ); ?>><?php esc_html_e( 'None (hide it)', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Choose the position to show the bundled products list.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Layout', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[layout]">
                                                <option value="list" <?php selected( $layout, 'list' ); ?>><?php esc_html_e( 'List', 'woo-product-bundle' ); ?></option>
                                                <option value="grid-2" <?php selected( $layout, 'grid-2' ); ?>><?php esc_html_e( 'Grid - 2 columns', 'woo-product-bundle' ); ?></option>
                                                <option value="grid-3" <?php selected( $layout, 'grid-3' ); ?>><?php esc_html_e( 'Grid - 3 columns', 'woo-product-bundle' ); ?></option>
                                                <option value="grid-4" <?php selected( $layout, 'grid-4' ); ?>><?php esc_html_e( 'Grid - 4 columns', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Variations selector', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <select name="woosb_settings[variations_selector]"
                                                    class="woosb_variations_selector">
                                                <option value="default" <?php selected( $variations_selector, 'default' ); ?>><?php esc_html_e( 'Default', 'woo-product-bundle' ); ?></option>
                                                <option value="woovr" <?php selected( $variations_selector, 'woovr' ); ?>><?php esc_html_e( 'Use WPC Variations Radio Buttons', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <p class="description">If you choose "Use WPC Variations Radio Buttons", please
                                            install
                                            <a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=wpc-variations-radio-buttons&TB_iframe=true&width=800&height=550' ) ); ?>"
                                               class="thickbox" title="WPC Variations Radio Buttons">WPC Variations
                                                Radio Buttons</a> to make it work.
                                        </p>
                                        <div class="woosb_show_if_woovr" style="margin-top: 10px">
											<?php esc_html_e( 'Selector interface', 'woo-product-bundle' ); ?>
                                            <label> <select name="woosb_settings[selector_interface]">
                                                    <option value="unset" <?php selected( $selector_interface, 'unset' ); ?>><?php esc_html_e( 'Unset', 'woo-product-bundle' ); ?></option>
                                                    <option value="ddslick" <?php selected( $selector_interface, 'ddslick' ); ?>><?php esc_html_e( 'ddSlick', 'woo-product-bundle' ); ?></option>
                                                    <option value="select2" <?php selected( $selector_interface, 'select2' ); ?>><?php esc_html_e( 'Select2', 'woo-product-bundle' ); ?></option>
                                                    <option value="default" <?php selected( $selector_interface, 'default' ); ?>><?php esc_html_e( 'Radio buttons', 'woo-product-bundle' ); ?></option>
                                                    <option value="select" <?php selected( $selector_interface, 'select' ); ?>><?php esc_html_e( 'HTML select tag', 'woo-product-bundle' ); ?></option>
                                                    <option value="grid-2" <?php selected( $selector_interface, 'grid-2' ); ?>><?php esc_html_e( 'Grid - 2 columns', 'woo-product-bundle' ); ?></option>
                                                    <option value="grid-3" <?php selected( $selector_interface, 'grid-3' ); ?>><?php esc_html_e( 'Grid - 3 columns', 'woo-product-bundle' ); ?></option>
                                                    <option value="grid-4" <?php selected( $selector_interface, 'grid-4' ); ?>><?php esc_html_e( 'Grid - 4 columns', 'woo-product-bundle' ); ?></option>
                                                </select> </label>
                                            <p class="description"><?php esc_html_e( 'Choose a selector interface that apply for variations of bundled products only.', 'woo-product-bundle' ); ?></p>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show thumbnail', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundled_thumb]">
                                                <option value="yes" <?php selected( $bundled_thumb, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $bundled_thumb, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show quantity', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundled_qty]">
                                                <option value="yes" <?php selected( $bundled_qty, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $bundled_qty, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Show the quantity number before product name.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show short description', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundled_description]">
                                                <option value="yes" <?php selected( $bundled_desc, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $bundled_desc, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show price', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundled_price]">
                                                <option value="price" <?php selected( $bundled_price, 'price' ); ?>><?php esc_html_e( 'Price at the last', 'woo-product-bundle' ); ?></option>
                                                <option value="subtotal" <?php selected( $bundled_price, 'subtotal' ); ?>><?php esc_html_e( 'Subtotal at the last', 'woo-product-bundle' ); ?></option>
                                                <option value="price_under_name" <?php selected( $bundled_price, 'price_under_name' ); ?>><?php esc_html_e( 'Price under the product name', 'woo-product-bundle' ); ?></option>
                                                <option value="subtotal_under_name" <?php selected( $bundled_price, 'subtotal_under_name' ); ?>><?php esc_html_e( 'Subtotal under the product name', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $bundled_price, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Link to individual product', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundled_link]">
                                                <option value="yes" <?php selected( $bundled_link, 'yes' ); ?>><?php esc_html_e( 'Yes, open in the same tab', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_blank" <?php selected( $bundled_link, 'yes_blank' ); ?>><?php esc_html_e( 'Yes, open in the new tab', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_popup" <?php selected( $bundled_link, 'yes_popup' ); ?>><?php esc_html_e( 'Yes, open quick view popup', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $bundled_link, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <p class="description">If you choose "Open quick view popup", please install
                                            <a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woo-smart-quick-view&TB_iframe=true&width=800&height=550' ) ); ?>"
                                               class="thickbox" title="WPC Smart Quick View">WPC Smart Quick View</a> to
                                            make it work.
                                        </p>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Show plus/minus button', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[plus_minus]">
                                                <option value="yes" <?php selected( $plus_minus, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $plus_minus, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Show the plus/minus button for the quantity input.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Change image', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[change_image]">
                                                <option value="yes" <?php selected( $change_image, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $change_image, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Change the main product image when choosing the variation of bundled products.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Change price', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[change_price]" class="woosb_change_price">
                                                <option value="yes" <?php selected( $change_price, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_custom" <?php selected( $change_price, 'yes_custom' ); ?>><?php esc_html_e( 'Yes, custom selector', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $change_price, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label> <label>
                                            <input type="text" name="woosb_settings[change_price_custom]"
                                                   value="<?php echo WPCleverWoosb_Helper()->get_setting( 'change_price_custom', '.summary > .price' ); ?>"
                                                   placeholder=".summary > .price" class="woosb_change_price_custom"/>
                                        </label>
                                        <p class="description"><?php esc_html_e( 'Change the main product price when choosing the variation of bundled products. It uses JavaScript to change product price so it is very dependent on theme’s HTML. If it cannot find and update the product price, please contact us and we can help you find the right selector or adjust the JS file.', 'woo-product-bundle' ); ?></p>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th>
										<?php esc_html_e( 'Bundles', 'woo-product-bundle' ); ?>
                                    </th>
                                    <td>
										<?php esc_html_e( 'Settings for bundles on the bundled product page.', 'woo-product-bundle' ); ?>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Position', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[bundles_position]">
                                                <option value="above" <?php selected( $bundles_position, 'above' ); ?>><?php esc_html_e( 'Above the add to cart button', 'woo-product-bundle' ); ?></option>
                                                <option value="below" <?php selected( $bundles_position, 'below' ); ?>><?php esc_html_e( 'Under the add to cart button', 'woo-product-bundle' ); ?></option>
                                                <option value="tab" <?php selected( $bundles_position, 'tab' ); ?>><?php esc_html_e( 'In a new tab', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $bundles_position, 'no' ); ?>><?php esc_html_e( 'None (hide it)', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Choose the position to show the bundles list.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'Cart & Checkout', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Coupon restrictions', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[coupon_restrictions]">
                                                <option value="no" <?php selected( $coupon_restrictions, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                                <option value="bundles" <?php selected( $coupon_restrictions, 'bundles' ); ?>><?php esc_html_e( 'Exclude bundles', 'woo-product-bundle' ); ?></option>
                                                <option value="bundled" <?php selected( $coupon_restrictions, 'bundled' ); ?>><?php esc_html_e( 'Exclude bundled products', 'woo-product-bundle' ); ?></option>
                                                <option value="both" <?php selected( $coupon_restrictions, 'both' ); ?>><?php esc_html_e( 'Exclude both bundles and bundled products', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Choose products you want to exclude from coupons.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Exclude un-purchasable products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[exclude_unpurchasable]">
                                                <option value="yes" <?php selected( $exclude_unpurchasable, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $exclude_unpurchasable, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <p class="description"><?php esc_html_e( 'Make the bundle still purchasable when one of the bundled products is un-purchasable. These bundled products are excluded from the orders.', 'woo-product-bundle' ); ?></p>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Cart contents count', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[cart_contents_count]">
                                                <option value="bundle" <?php selected( $contents_count, 'bundle' ); ?>><?php esc_html_e( 'Bundles only', 'woo-product-bundle' ); ?></option>
                                                <option value="bundled_products" <?php selected( $contents_count, 'bundled_products' ); ?>><?php esc_html_e( 'Bundled products only', 'woo-product-bundle' ); ?></option>
                                                <option value="both" <?php selected( $contents_count, 'both' ); ?>><?php esc_html_e( 'Both bundles and bundled products', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide bundle name before bundled products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[hide_bundle_name]">
                                                <option value="yes" <?php selected( $hide_bundle_name, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $hide_bundle_name, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide bundled products on mini-cart', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[hide_bundled_mini_cart]">
                                                <option value="yes" <?php selected( $hide_bundled_mc, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $hide_bundled_mc, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Hide bundled products, just show the main product on mini-cart.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide bundled products on cart & checkout page', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[hide_bundled]">
                                                <option value="yes" <?php selected( $hide_bundled, 'yes' ); ?>><?php esc_html_e( 'Yes, just show the main bundle', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_text" <?php selected( $hide_bundled, 'yes_text' ); ?>><?php esc_html_e( 'Yes, but shortly list bundled sub-product names under the main bundle in one line', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_list" <?php selected( $hide_bundled, 'yes_list' ); ?>><?php esc_html_e( 'Yes, but list bundled sub-product names under the main bundle in separate lines', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $hide_bundled, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide bundled products on order details', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[hide_bundled_order]">
                                                <option value="yes" <?php selected( $hide_bundled_order, 'yes' ); ?>><?php esc_html_e( 'Yes, just show the main bundle', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_text" <?php selected( $hide_bundled_order, 'yes_text' ); ?>><?php esc_html_e( 'Yes, but shortly list bundled sub-product names under the main bundle in one line', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_list" <?php selected( $hide_bundled_order, 'yes_list' ); ?>><?php esc_html_e( 'Yes, but list bundled sub-product names under the main bundle in separate lines', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $hide_bundled_order, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                        <p class="description"><?php esc_html_e( 'Hide bundled products, just show the main product on order details (order confirmation or emails).', 'woo-product-bundle' ); ?></p>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_none">
                                    <th colspan="2">
										<?php esc_html_e( 'Search', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
								<?php self::search_settings(); ?>
                                <tr class="heading show_if_section_compatible">
                                    <th colspan="2">
										<?php esc_html_e( 'WooCommerce PDF Invoices & Packing Slips', 'woo-product-bundle' ); ?>
                                        <a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/"
                                           target="_blank"><span class="dashicons dashicons-external"></span></a>
                                    </th>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundles', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[compatible_wcpdf_hide_bundles]">
                                                <option value="yes" <?php selected( $wcpdf_hide_bundles, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $wcpdf_hide_bundles, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundled products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[compatible_wcpdf_hide_bundled]">
                                                <option value="yes" <?php selected( $wcpdf_hide_bundled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $wcpdf_hide_bundled, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="heading show_if_section_compatible">
                                    <th colspan="2">
										<?php esc_html_e( 'WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels', 'woo-product-bundle' ); ?>
                                        <a href="https://wordpress.org/plugins/print-invoices-packing-slip-labels-for-woocommerce/"
                                           target="_blank"><span class="dashicons dashicons-external"></span></a>
                                    </th>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundles', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[compatible_pklist_hide_bundles]">
                                                <option value="yes" <?php selected( $pklist_hide_bundles, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no"<?php selected( $pklist_hide_bundles, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="show_if_section_compatible">
                                    <th><?php esc_html_e( 'Hide bundled products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label> <select name="woosb_settings[compatible_pklist_hide_bundled]">
                                                <option value="yes" <?php selected( $pklist_hide_bundled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $pklist_hide_bundled, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr class="submit show_if_section_all">
                                    <th colspan="2">
										<?php settings_fields( 'woosb_settings' ); ?><?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } elseif ( $active_tab === 'localization' ) { ?>
                        <form method="post" action="options.php">
                            <table class="form-table">
                                <tr class="heading">
                                    <th scope="row"><?php esc_html_e( 'General', 'woo-product-bundle' ); ?></th>
                                    <td>
										<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'woo-product-bundle' ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Total text', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[total]" class="regular-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'total' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Bundle price:', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Selected text', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[selected]" class="regular-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'selected' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Selected:', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Saved text', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[saved]" class="regular-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'saved' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( '(saved [d])', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                        <span class="description"><?php esc_html_e( 'Use [d] to show the saved percentage or amount.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Choose an attribute', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[choose]" class="regular-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'choose' ) ); ?>"
                                                   placeholder="<?php /* translators: attribute name */
											       esc_attr_e( 'Choose %s', 'woo-product-bundle' ); ?>"/> </label>
                                        <span class="description"><?php /* translators: attribute name */
											esc_html_e( 'Use %s to show the attribute name.', 'woo-product-bundle' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Clear', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[clear]" class="regular-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'clear' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Clear', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( '"Add to cart" button labels', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Shop/archive page', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <div style="margin-bottom: 5px">
                                            <label>
                                                <input type="text" class="regular-text"
                                                       name="woosb_localization[button_add]"
                                                       value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'button_add' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Add to cart', 'woo-product-bundle' ); ?>"/>
                                            </label>
                                            <span class="description"><?php esc_html_e( 'For purchasable bundle.', 'woo-product-bundle' ); ?></span>
                                        </div>
                                        <div style="margin-bottom: 5px">
                                            <label>
                                                <input type="text" class="regular-text"
                                                       name="woosb_localization[button_select]"
                                                       value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'button_select' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Select options', 'woo-product-bundle' ); ?>"/>
                                            </label>
                                            <span class="description"><?php esc_html_e( 'For purchasable bundle and has variable product(s).', 'woo-product-bundle' ); ?></span>
                                        </div>
                                        <div>
                                            <label>
                                                <input type="text" class="regular-text"
                                                       name="woosb_localization[button_read]"
                                                       value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'button_read' ) ); ?>"
                                                       placeholder="<?php esc_attr_e( 'Read more', 'woo-product-bundle' ); ?>"/>
                                            </label>
                                            <span class="description"><?php esc_html_e( 'For un-purchasable bundle.', 'woo-product-bundle' ); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Single product page', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[button_single]"
                                                   class="regular-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'button_single' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Add to cart', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'Cart & Checkout', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Bundles', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[bundles]" class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'bundles' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Bundles', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Bundled products', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[bundled_products]"
                                                   class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'bundled_products' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Bundled products', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php /* translators: bundled products */
										esc_html_e( 'Bundled products: %s', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[bundled_products_s]"
                                                   class="large-text" value="<?php /* translators: bundled products */
											echo esc_attr( WPCleverWoosb_Helper()->localization( 'bundled_products_s' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Bundled products: %s', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php /* translators: bundled in */
										esc_html_e( 'Bundled in: %s', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[bundled_in_s]"
                                                   class="large-text" value="<?php /* translators: bundled in */
											echo esc_attr( WPCleverWoosb_Helper()->localization( 'bundled_in_s' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Bundled in: %s', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'Alert', 'woo-product-bundle' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Require selection', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[alert_selection]"
                                                   class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'alert_selection' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Please select a purchasable variation for [name] before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Require purchasable', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[alert_unpurchasable]"
                                                   class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'alert_unpurchasable' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Product [name] is unpurchasable. Please remove it before adding the bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Enforce a selection', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[alert_empty]" class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'alert_empty' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Please choose at least one product before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Minimum required', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[alert_min]" class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'alert_min' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Please choose at least a total quantity of [min] products before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Maximum reached', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[alert_max]" class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'alert_max' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'Sorry, you can only choose at max a total quantity of [max] products before adding this bundle to the cart.', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Total minimum required', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[alert_total_min]"
                                                   class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'alert_total_min' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'The total must meet the minimum amount of [min].', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Total maximum required', 'woo-product-bundle' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" name="woosb_localization[alert_total_max]"
                                                   class="large-text"
                                                   value="<?php echo esc_attr( WPCleverWoosb_Helper()->localization( 'alert_total_max' ) ); ?>"
                                                   placeholder="<?php esc_attr_e( 'The total must meet the maximum amount of [max].', 'woo-product-bundle' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="submit">
                                    <th colspan="2">
										<?php settings_fields( 'woosb_localization' ); ?><?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } elseif ( $active_tab === 'tools' ) { ?>
                        <table class="form-table">
                            <tr class="heading">
                                <th scope="row"><?php esc_html_e( 'Data Migration', 'woo-product-bundle' ); ?></th>
                                <td>
									<?php esc_html_e( 'If you have updated WPC Product Bundles from a version before 7.0.0, please run the Migrate tool once.', 'woo-product-bundle' ); ?>
									<?php
									echo '<p>';
									$num   = absint( $_GET['num'] ?? 50 );
									$paged = absint( $_GET['paged'] ?? 1 );

									if ( isset( $_GET['act'] ) && ( $_GET['act'] === 'migrate' ) ) {
										$args = [
											'post_type'      => 'product',
											'posts_per_page' => $num,
											'paged'          => $paged,
											'meta_query'     => [
												[
													'key'     => 'woosb_ids',
													'compare' => 'EXISTS'
												]
											]
										];

										$posts = get_posts( $args );

										if ( ! empty( $posts ) ) {
											foreach ( $posts as $post ) {
												$ids = get_post_meta( $post->ID, 'woosb_ids', true );

												if ( ! empty( $ids ) && is_string( $ids ) ) {
													$items     = explode( ',', $ids );
													$new_items = [];

													foreach ( $items as $item ) {
														$item_data = explode( '/', $item );
														$item_key  = WPCleverWoosb_Helper()->generate_key();
														$item_id   = absint( $item_data[0] ?? 0 );

														if ( $item_product = wc_get_product( $item_id ) ) {
															$item_sku = $item_product->get_sku();
															$item_qty = (float) ( $item_data[1] ?? 1 );

															$new_items[ $item_key ] = [
																'id'  => $item_id,
																'sku' => $item_sku,
																'qty' => $item_qty,
															];
														}
													}

													update_post_meta( $post->ID, 'woosb_ids', $new_items );
												}
											}

											echo '<span style="color: #2271b1; font-weight: 700">' . esc_html__( 'Migrating...', 'woo-product-bundle' ) . '</span>';
											echo '<p class="description">' . esc_html__( 'Please wait until it has finished!', 'woo-product-bundle' ) . '</p>';
											?>
                                            <script type="text/javascript">
                                                (function ($) {
                                                    $(function () {
                                                        setTimeout(function () {
                                                            window.location.href = '<?php echo esc_url_raw( admin_url( 'admin.php?page=wpclever-woosb&tab=tools&act=migrate&num=' . $num . '&paged=' . ( $paged + 1 ) ) ); ?>';
                                                        }, 1000);
                                                    });
                                                })(jQuery);
                                            </script>
										<?php } else {
											echo '<span style="color: #2271b1; font-weight: 700">' . esc_html__( 'Finished!', 'woo-product-bundle' ) . '</span>';
										}
									} else {
										echo '<a class="button btn" href="' . esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=tools&act=migrate' ) ) . '">' . esc_html__( 'Migrate', 'woo-product-bundle' ) . '</a>';
									}
									echo '</p>';
									?>
                                </td>
                            </tr>
                        </table>
					<?php } elseif ( $active_tab === 'premium' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p>
                                Get the Premium Version just $29!
                                <a href="https://wpclever.net/downloads/product-bundles?utm_source=pro&utm_medium=woosb&utm_campaign=wporg"
                                   target="_blank">https://wpclever.net/downloads/product-bundles</a>
                            </p>
                            <p><strong>Extra features for Premium Version:</strong></p>
                            <ul style="margin-bottom: 0">
                                <li>- Add a variable product or a specific variation to a bundle.</li>
                                <li>- Insert heading/paragraph into bundled products list.</li>
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
                            To display custom engaging real-time messages on any wished positions, please install
                            <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart
                                Messages</a> plugin. It's free!
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

		function search_settings() {
			$search_sku        = WPCleverWoosb_Helper()->get_setting( 'search_sku', 'no' );
			$search_id         = WPCleverWoosb_Helper()->get_setting( 'search_id', 'no' );
			$search_exact      = WPCleverWoosb_Helper()->get_setting( 'search_exact', 'no' );
			$search_sentence   = WPCleverWoosb_Helper()->get_setting( 'search_sentence', 'no' );
			$search_same       = WPCleverWoosb_Helper()->get_setting( 'search_same', 'no' );
			$search_show_image = WPCleverWoosb_Helper()->get_setting( 'search_show_image', 'yes' );
			?>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search limit', 'woo-product-bundle' ); ?></th>
                <td>
                    <label>
                        <input type="number" class="woosb_search_limit" name="woosb_settings[search_limit]"
                               value="<?php echo WPCleverWoosb_Helper()->get_setting( 'search_limit', 10 ); ?>"/>
                    </label>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search by SKU', 'woo-product-bundle' ); ?></th>
                <td>
                    <label> <select name="woosb_settings[search_sku]" class="woosb_search_sku">
                            <option value="yes" <?php selected( $search_sku, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                            <option value="no" <?php selected( $search_sku, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                        </select> </label>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search by ID', 'woo-product-bundle' ); ?></th>
                <td>
                    <label> <select name="woosb_settings[search_id]" class="woosb_search_id">
                            <option value="yes" <?php selected( $search_id, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                            <option value="no" <?php selected( $search_id, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                        </select> </label>
                    <span class="description"><?php esc_html_e( 'Search by ID when entering the numeric only.', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search exact', 'woo-product-bundle' ); ?></th>
                <td>
                    <label> <select name="woosb_settings[search_exact]" class="woosb_search_exact">
                            <option value="yes" <?php selected( $search_exact, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                            <option value="no" <?php selected( $search_exact, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                        </select> </label>
                    <span class="description"><?php esc_html_e( 'Match whole product title or content?', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Search sentence', 'woo-product-bundle' ); ?></th>
                <td>
                    <label> <select name="woosb_settings[search_sentence]" class="woosb_search_sentence">
                            <option value="yes" <?php selected( $search_sentence, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                            <option value="no" <?php selected( $search_sentence, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                        </select> </label>
                    <span class="description"><?php esc_html_e( 'Do a phrase search?', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Accept same products', 'woo-product-bundle' ); ?></th>
                <td>
                    <label> <select name="woosb_settings[search_same]" class="woosb_search_same">
                            <option value="yes" <?php selected( $search_same, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                            <option value="no" <?php selected( $search_same, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                        </select> </label>
                    <span class="description"><?php esc_html_e( 'If yes, a product can be added many times.', 'woo-product-bundle' ); ?></span>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Product types', 'woo-product-bundle' ); ?></th>
                <td>
					<?php
					$search_types  = WPCleverWoosb_Helper()->get_setting( 'search_types', [ 'all' ] );
					$product_types = wc_get_product_types();
					$product_types = array_merge( [ 'all' => esc_html__( 'All', 'woo-product-bundle' ) ], $product_types );
					$key_pos       = array_search( 'variable', array_keys( $product_types ) );

					if ( $key_pos !== false ) {
						$key_pos ++;
						$second_array  = array_splice( $product_types, $key_pos );
						$product_types = array_merge( $product_types, [ 'variation' => esc_html__( ' → Variation', 'woo-product-bundle' ) ], $second_array );
					}

					echo '<select name="woosb_settings[search_types][]" class="woosb_search_types" multiple style="width: 200px; height: 150px;">';

					foreach ( $product_types as $key => $name ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . ( in_array( $key, $search_types, true ) ? 'selected' : '' ) . '>' . esc_html( $name ) . '</option>';
					}

					echo '</select>';
					?>
                </td>
            </tr>
            <tr class="show_if_section_none">
                <th><?php esc_html_e( 'Show image', 'woo-product-bundle' ); ?></th>
                <td>
                    <label> <select name="woosb_settings[search_show_image]" class="woosb_search_show_image">
                            <option value="yes" <?php selected( $search_show_image, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                            <option value="no" <?php selected( $search_show_image, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                        </select> </label>
                </td>
            </tr>
			<?php
		}

		function enqueue_scripts() {
			wp_enqueue_style( 'woosb-frontend', WOOSB_URI . 'assets/css/frontend.css', [], WOOSB_VERSION );
			wp_enqueue_script( 'woosb-frontend', WOOSB_URI . 'assets/js/frontend.js', [ 'jquery' ], WOOSB_VERSION, true );
			wp_localize_script( 'woosb-frontend', 'woosb_vars', apply_filters( 'woosb_vars', [
					'wc_price_decimals'           => wc_get_price_decimals(),
					'wc_price_format'             => get_woocommerce_price_format(),
					'wc_price_thousand_separator' => wc_get_price_thousand_separator(),
					'wc_price_decimal_separator'  => wc_get_price_decimal_separator(),
					'wc_currency_symbol'          => get_woocommerce_currency_symbol(),
					'price_decimals'              => apply_filters( 'woosb_price_decimals', wc_get_price_decimals() ),
					'price_format'                => get_woocommerce_price_format(), // old version before 7.1.0
					'price_thousand_separator'    => wc_get_price_thousand_separator(), // old version before 7.1.0
					'price_decimal_separator'     => wc_get_price_decimal_separator(), // old version before 7.1.0
					'currency_symbol'             => get_woocommerce_currency_symbol(), // old version before 7.1.0
					'trim_zeros'                  => apply_filters( 'woosb_price_trim_zeros', apply_filters( 'woocommerce_price_trim_zeros', false ) ),
					'round_price'                 => apply_filters( 'woosb_round_price', ! apply_filters( 'woosb_ignore_round_price', false ) ),
					'change_image'                => WPCleverWoosb_Helper()->get_setting( 'change_image', 'yes' ),
					'bundled_price'               => WPCleverWoosb_Helper()->get_setting( 'bundled_price', 'price' ),
					'bundled_price_from'          => WPCleverWoosb_Helper()->get_setting( 'bundled_price_from', 'sale_price' ),
					'change_price'                => WPCleverWoosb_Helper()->get_setting( 'change_price', 'yes' ),
					'price_selector'              => WPCleverWoosb_Helper()->get_setting( 'change_price_custom', '' ),
					'saved_text'                  => WPCleverWoosb_Helper()->localization( 'saved', esc_html__( '(saved [d])', 'woo-product-bundle' ) ),
					'price_text'                  => WPCleverWoosb_Helper()->localization( 'total', esc_html__( 'Bundle price:', 'woo-product-bundle' ) ),
					'selected_text'               => WPCleverWoosb_Helper()->localization( 'selected', esc_html__( 'Selected:', 'woo-product-bundle' ) ),
					'alert_selection'             => WPCleverWoosb_Helper()->localization( 'alert_selection', esc_html__( 'Please select a purchasable variation for [name] before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_unpurchasable'         => WPCleverWoosb_Helper()->localization( 'alert_unpurchasable', esc_html__( 'Product [name] is unpurchasable. Please remove it before adding the bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_empty'                 => WPCleverWoosb_Helper()->localization( 'alert_empty', esc_html__( 'Please choose at least one product before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_min'                   => WPCleverWoosb_Helper()->localization( 'alert_min', esc_html__( 'Please choose at least a total quantity of [min] products before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_max'                   => WPCleverWoosb_Helper()->localization( 'alert_max', esc_html__( 'Sorry, you can only choose at max a total quantity of [max] products before adding this bundle to the cart.', 'woo-product-bundle' ) ),
					'alert_total_min'             => WPCleverWoosb_Helper()->localization( 'alert_total_min', esc_html__( 'The total must meet the minimum amount of [min].', 'woo-product-bundle' ) ),
					'alert_total_max'             => WPCleverWoosb_Helper()->localization( 'alert_total_max', esc_html__( 'The total must meet the maximum amount of [max].', 'woo-product-bundle' ) ),
				] )
			);
		}

		function admin_enqueue_scripts() {
			wp_enqueue_style( 'hint', WOOSB_URI . 'assets/css/hint.css' );
			wp_enqueue_style( 'woosb-backend', WOOSB_URI . 'assets/css/backend.css', [], WOOSB_VERSION );
			wp_enqueue_script( 'woosb-backend', WOOSB_URI . 'assets/js/backend.js', [
				'jquery',
				'jquery-ui-dialog',
				'jquery-ui-sortable',
				'selectWoo',
			], WOOSB_VERSION, true );
			wp_localize_script( 'woosb-backend', 'woosb_vars', [
					'nonce'                    => wp_create_nonce( 'woosb-security' ),
					'price_decimals'           => wc_get_price_decimals(),
					'price_thousand_separator' => wc_get_price_thousand_separator(),
					'price_decimal_separator'  => wc_get_price_decimal_separator(),
					'round_price'              => apply_filters( 'woosb_round_price', ! apply_filters( 'woosb_ignore_round_price', false ) ),
				]
			);
		}

		function action_links( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WOOSB_FILE );
			}

			if ( $plugin === $file ) {
				$settings             = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'woo-product-bundle' ) . '</a>';
				$links['wpc-premium'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=premium' ) ) . '">' . esc_html__( 'Premium Version', 'woo-product-bundle' ) . '</a>';
				array_unshift( $links, $settings );
			}

			return (array) $links;
		}

		function row_meta( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WOOSB_FILE );
			}

			if ( $plugin === $file ) {
				$row_meta = [
					'docs'    => '<a href="' . esc_url( WOOSB_DOCS ) . '" target="_blank">' . esc_html__( 'Docs', 'woo-product-bundle' ) . '</a>',
					'support' => '<a href="' . esc_url( WOOSB_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'woo-product-bundle' ) . '</a>',
				];

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		function cart_contents_count( $count ) {
			// count for cart contents
			$cart_count = WPCleverWoosb_Helper()->get_setting( 'cart_contents_count', 'bundle' );

			if ( $cart_count !== 'both' ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( ( $cart_count === 'bundled_products' ) && ! empty( $cart_item['woosb_ids'] ) ) {
						$count -= $cart_item['quantity'];
					}

					if ( ( $cart_count === 'bundle' ) && ! empty( $cart_item['woosb_parent_id'] ) ) {
						$count -= $cart_item['quantity'];
					}
				}
			}

			return apply_filters( 'woosb_cart_contents_count', $count );
		}

		function get_item_count( $count, $type, $order ) {
			// count for order items
			$cart_count    = WPCleverWoosb_Helper()->get_setting( 'cart_contents_count', 'bundle' );
			$order_bundles = $order_bundled = 0;

			if ( $cart_count !== 'both' ) {
				$order_items = $order->get_items( 'line_item' );

				foreach ( $order_items as $order_item ) {
					if ( $order_item->get_meta( '_woosb_parent_id' ) ) {
						$order_bundled += $order_item->get_quantity();
					}

					if ( $order_item->get_meta( '_woosb_ids' ) ) {
						$order_bundles += $order_item->get_quantity();
					}
				}

				if ( ( $cart_count === 'bundled_products' ) && ( $order_bundled > 0 ) ) {
					return $count - $order_bundles;
				}

				if ( ( $cart_count === 'bundle' ) && ( $order_bundles > 0 ) ) {
					return $count - $order_bundled;
				}
			}

			return apply_filters( 'woosb_get_item_count', $count );
		}

		function cart_item_name( $name, $item ) {
			if ( empty( $item['woosb_parent_id'] ) ) {
				return $name;
			}

			$show_bundle_name = WPCleverWoosb_Helper()->get_setting( 'hide_bundle_name', 'no' ) === 'no';
			$parent_id        = apply_filters( 'woosb_item_id', $item['woosb_parent_id'] );

			if ( ( str_contains( $name, '</a>' ) ) && ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
				if ( $show_bundle_name ) {
					$_name = '<a href="' . esc_url( get_permalink( $parent_id ) ) . '">' . get_the_title( $parent_id ) . '</a>' . apply_filters( 'woosb_name_separator', ' &rarr; ' ) . apply_filters( 'woosb_item_product_name', $name, $item );
				} else {
					$_name = apply_filters( 'woosb_item_product_name', $name, $item );
				}
			} else {
				if ( $show_bundle_name ) {
					$_name = get_the_title( $parent_id ) . apply_filters( 'woosb_name_separator', ' &rarr; ' ) . wp_strip_all_tags( apply_filters( 'woosb_item_product_name', $name, $item ) );
				} else {
					$_name = wp_strip_all_tags( apply_filters( 'woosb_item_product_name', $name, $item ) );
				}
			}

			return apply_filters( 'woosb_cart_item_name', $_name, $name, $item );
		}

		function cart_item_removed( $cart_item_key, $cart ) {
			$new_keys = [];

			foreach ( $cart->cart_contents as $cart_key => $cart_item ) {
				if ( ! empty( $cart_item['woosb_key'] ) ) {
					$new_keys[ $cart_key ] = $cart_item['woosb_key'];
				}
			}

			if ( isset( $cart->removed_cart_contents[ $cart_item_key ]['woosb_keys'] ) ) {
				$keys = $cart->removed_cart_contents[ $cart_item_key ]['woosb_keys'];

				foreach ( $keys as $key ) {
					$cart->remove_cart_item( $key );

					if ( $new_key = array_search( $key, $new_keys ) ) {
						$cart->remove_cart_item( $new_key );
					}
				}
			}
		}

		function check_in_cart( $product_id ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( $cart_item['product_id'] == $product_id ) {
					return true;
				}
			}

			return false;
		}

		function found_in_cart( $found_in_cart, $product_id ) {
			if ( apply_filters( 'woosb_sold_individually_found_in_cart', true ) && self::check_in_cart( $product_id ) ) {
				return true;
			}

			return $found_in_cart;
		}

		function add_to_cart_validation( $passed, $product_id, $qty ) {
			if ( ! apply_filters( 'woosb_add_to_cart_validation', true ) || isset( $_REQUEST['order_again'] ) ) {
				return $passed;
			}

			if ( ( $product = wc_get_product( $product_id ) ) && is_a( $product, 'WC_Product_Woosb' ) ) {
				// get settings before re-building items
				$has_optional  = $product->has_optional();
				$has_variables = $product->has_variables();

				// get original items for validate
				$ori_items = $product->get_items();
				$ori_ids   = array_filter( array_column( $ori_items, 'id' ) );

				if ( isset( $_REQUEST['woosb_ids'] ) ) {
					$ids = WPCleverWoosb_Helper()->clean_ids( $_REQUEST['woosb_ids'] );
					$product->build_items( $ids );
				}

				if ( ( $items = $product->get_items() ) && ! empty( $items ) ) {
					$count                 = $total = $purchasable = 0;
					$min_whole             = (float) ( get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ?: 1 );
					$max_whole             = (float) ( get_post_meta( $product_id, 'woosb_limit_whole_max', true ) ?: - 1 );
					$total_min             = (float) ( get_post_meta( $product_id, 'woosb_total_limits_min', true ) ?: 0 );
					$total_max             = (float) ( get_post_meta( $product_id, 'woosb_total_limits_max', true ) ?: - 1 );
					$total_limits          = get_post_meta( $product_id, 'woosb_total_limits', true ) === 'on';
					$check_total           = ! $product->is_fixed_price() && ( $has_optional || $has_variables ) && $total_limits;
					$exclude_unpurchasable = $product->exclude_unpurchasable();

					foreach ( $items as $key => $item ) {
						$_id = $item['id'];

						if ( ! $_id ) {
							// exclude heading/paragraph
							continue;
						}

						$_qty     = $item['qty'];
						$count    += $_qty;
						$_parent  = 0;
						$_product = wc_get_product( $_id );

						if ( ! $_product ) {
							if ( ! $exclude_unpurchasable ) {
								wc_add_notice( esc_html__( 'One of the bundled products is unavailable.', 'woo-product-bundle' ), 'error' );
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							} else {
								continue;
							}
						}

						if ( $check_total ) {
							$total += wc_get_price_to_display( $_product, [ 'qty' => $_qty ] );
						}

						if ( $_product->is_type( 'variation' ) ) {
							$_parent = $_product->get_parent_id();
						}

						if ( ! function_exists( 'wpml_loaded' ) ) {
							// don't check it with wpml
							if ( ! isset( $ori_items[ $key ] ) || ( ! in_array( $_id, $ori_ids ) && ! in_array( $_parent, $ori_ids ) ) ) {
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							}
						}

						// validate qty
						if ( isset( $ori_items[ $key ]['optional'] ) ) {
							$_min = ! empty( $ori_items[ $key ]['min'] ) ? (float) $ori_items[ $key ]['min'] : 0;
							$_max = ! empty( $ori_items[ $key ]['max'] ) ? (float) $ori_items[ $key ]['max'] : 10000;

							if ( $_qty < $_min || $_qty > $_max ) {
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							}
						} else {
							if ( $_qty != $ori_items[ $key ]['qty'] ) {
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							}
						}

						if ( ! $exclude_unpurchasable ) {
							if ( $_product->is_type( 'variable' ) || $_product->is_type( 'woosb' ) || ! $_product->is_in_stock() || ! $_product->is_purchasable() ) {
								/* translators: product name */
								wc_add_notice( sprintf( esc_html__( '"%s" is un-purchasable.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							}

							if ( $_product->is_sold_individually() && apply_filters( 'woosb_sold_individually_found_in_cart', true ) && self::check_in_cart( $_id ) ) {
								/* translators: product name */
								wc_add_notice( sprintf( esc_html__( 'You cannot add another "%s" to the cart.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							}

							if ( post_password_required( $_id ) ) {
								/* translators: product name */
								wc_add_notice( sprintf( esc_html__( '"%s" is protected and cannot be purchased.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
								wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

								return false;
							}

							if ( $_product->managing_stock() ) {
								$qty_in_cart  = ( method_exists( WC()->cart, 'get_cart_item_quantities' ) ) && ( $quantities = WC()->cart->get_cart_item_quantities() ) && method_exists( $_product, 'get_stock_managed_by_id' ) && isset( $quantities[ $_product->get_stock_managed_by_id() ] ) ? $quantities[ $_product->get_stock_managed_by_id() ] : 0;
								$qty_to_check = 0;
								$_items       = $product->get_items();

								foreach ( $_items as $_item ) {
									if ( $_item['id'] == $_id ) {
										$qty_to_check += $_item['qty'];
									}
								}

								if ( ! $_product->has_enough_stock( $qty_in_cart + $qty_to_check * $qty ) ) {
									/* translators: product name */
									wc_add_notice( sprintf( esc_html__( '"%s" has not enough stock.', 'woo-product-bundle' ), esc_html( $_product->get_name() ) ), 'error' );
									wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

									return false;
								}
							}
						}

						$purchasable ++;
					}

					if ( ! $purchasable || ( $purchasable > count( $ori_ids ) ) ) {
						wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

						return false;
					}

					if ( ! $exclude_unpurchasable && ! $has_optional && ( $purchasable < count( $ori_ids ) ) ) {
						wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

						return false;
					}

					if ( $has_optional && ( ( $min_whole > 0 && $count < $min_whole ) || ( $max_whole > 0 && $count > $max_whole ) ) ) {
						wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

						return false;
					}

					if ( $check_total ) {
						if ( $discount_amount = $product->get_discount_amount() ) {
							$total -= $discount_amount;
						} elseif ( $discount_percentage = $product->get_discount_percentage() ) {
							$total = $total * ( 100 - $discount_percentage ) / 100;
						}

						if ( $total_min > 0 && $total < $total_min ) {
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}

						if ( $total_max > 0 && $total > $total_max ) {
							wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

							return false;
						}
					}
				} else {
					wc_add_notice( esc_html__( 'You cannot add this bundle to the cart.', 'woo-product-bundle' ), 'error' );

					return false;
				}
			}

			return $passed;
		}

		function add_cart_item_data( $cart_item_data, $product_id ) {
			if ( empty( $cart_item_data['woosb_ids'] ) && ( $_product = wc_get_product( $product_id ) ) && $_product->is_type( 'woosb' ) && ( $ids = $_product->get_ids_str() ) ) {
				// make sure that is bundle
				if ( isset( $_REQUEST['woosb_ids'] ) ) {
					$ids = WPCleverWoosb_Helper()->clean_ids( $_REQUEST['woosb_ids'] );
					unset( $_REQUEST['woosb_ids'] );
				}

				if ( ! empty( $ids ) ) {
					$cart_item_data['woosb_ids'] = $ids;
				}
			}

			return $cart_item_data;
		}

		function add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
			if ( ! empty( $cart_item_data['woosb_ids'] ) && isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
				unset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'] );
				WC()->cart->cart_contents[ $cart_item_key ]['data']->build_items( $cart_item_data['woosb_ids'] );
				$items = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_items();

				self::add_to_cart_items( $items, $cart_item_key, $product_id, $quantity );
			}
		}

		function restore_cart_item( $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_ids'] ) ) {
				unset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'] );
				WC()->cart->cart_contents[ $cart_item_key ]['data']->build_items( WC()->cart->cart_contents[ $cart_item_key ]['woosb_ids'] );
				$items      = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_items();
				$product_id = WC()->cart->cart_contents[ $cart_item_key ]['product_id'];
				$quantity   = WC()->cart->cart_contents[ $cart_item_key ]['quantity'];

				self::add_to_cart_items( $items, $cart_item_key, $product_id, $quantity );
			}
		}

		function add_to_cart_items( $items, $cart_item_key, $product_id, $quantity ) {
			if ( apply_filters( 'woosb_exclude_bundled', false ) ) {
				return;
			}

			$items = WPCleverWoosb_Helper()->minify_items( $items );

			$fixed_price           = WC()->cart->cart_contents[ $cart_item_key ]['data']->is_fixed_price();
			$discount_amount       = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_discount_amount();
			$discount_percentage   = WC()->cart->cart_contents[ $cart_item_key ]['data']->get_discount_percentage();
			$exclude_unpurchasable = WC()->cart->cart_contents[ $cart_item_key ]['data']->exclude_unpurchasable();

			// save current key associated with woosb_parent_key
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_key']             = $cart_item_key;
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_fixed_price']     = $fixed_price;
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_discount_amount'] = $discount_amount;
			WC()->cart->cart_contents[ $cart_item_key ]['woosb_discount']        = $discount_percentage;

			if ( is_array( $items ) && ( count( $items ) > 0 ) ) {
				foreach ( $items as $item ) {
					$_id           = $item['id'];
					$_qty          = $item['qty'];
					$_variation    = $item['attrs'];
					$_variation_id = 0;

					$_product = wc_get_product( $item['id'] );

					if ( ! $_product || ( $_qty <= 0 ) || in_array( $_product->get_type(), self::$types, true ) ) {
						continue;
					}

					if ( ( ! $_product->is_purchasable() || ! $_product->is_in_stock() ) && $exclude_unpurchasable ) {
						// exclude unpurchasable
						continue;
					}

					if ( $_product instanceof WC_Product_Variation ) {
						// ensure we don't add a variation to the cart directly by variation ID
						$_variation_id = $_id;
						$_id           = $_product->get_parent_id();

						if ( empty( $_variation ) ) {
							$_variation = $_product->get_variation_attributes();
						}
					}

					// add to cart
					$_data = [
						'woosb_qty'             => $_qty,
						'woosb_parent_id'       => $product_id,
						'woosb_parent_key'      => $cart_item_key,
						'woosb_fixed_price'     => $fixed_price,
						'woosb_discount_amount' => $discount_amount,
						'woosb_discount'        => $discount_percentage
					];

					$_key = WC()->cart->add_to_cart( $_id, $_qty * $quantity, $_variation_id, $_variation, $_data );

					if ( empty( $_key ) ) {
						if ( ! $exclude_unpurchasable ) {
							// can't add the bundled product
							if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'] ) ) {
								$keys = WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'];

								foreach ( $keys as $key ) {
									// remove all bundled products
									WC()->cart->remove_cart_item( $key );
								}

								// remove the bundle
								WC()->cart->remove_cart_item( $cart_item_key );

								// break out of the loop
								break;
							}
						}
					} elseif ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'] ) || ! in_array( $_key, WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'], true ) ) {
						// save current key
						WC()->cart->cart_contents[ $_key ]['woosb_key'] = $_key;

						// add keys for parent
						WC()->cart->cart_contents[ $cart_item_key ]['woosb_keys'][] = $_key;
					}
				} // end foreach
			}
		}

		function get_cart_item_from_session( $cart_item, $session_values ) {
			if ( ! empty( $session_values['woosb_ids'] ) ) {
				$cart_item['woosb_ids'] = $session_values['woosb_ids'];
			}

			if ( ! empty( $session_values['woosb_parent_id'] ) ) {
				$cart_item['woosb_parent_id']  = $session_values['woosb_parent_id'];
				$cart_item['woosb_parent_key'] = $session_values['woosb_parent_key'];
				$cart_item['woosb_qty']        = $session_values['woosb_qty'];
			}

			return $cart_item;
		}

		function before_mini_cart_contents() {
			WC()->cart->calculate_totals();
		}

		function before_calculate_totals( $cart_object ) {
			if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {
				// This is necessary for WC 3.0+
				return;
			}

			$cart_contents = $cart_object->cart_contents;
			$new_keys      = [];

			foreach ( $cart_contents as $cart_item_key => $cart_item ) {
				if ( ! empty( $cart_item['woosb_key'] ) ) {
					$new_keys[ $cart_item_key ] = $cart_item['woosb_key'];
				}
			}

			foreach ( $cart_contents as $cart_item_key => $cart_item ) {
				// bundled products
				if ( ! empty( $cart_item['woosb_parent_key'] ) ) {
					$parent_new_key = array_search( $cart_item['woosb_parent_key'], $new_keys );

					// remove orphaned bundled products
					if ( apply_filters( 'woosb_remove_orphaned_bundled_products', true ) ) {
						if ( ! $parent_new_key || ! isset( $cart_contents[ $parent_new_key ] ) || ( isset( $cart_contents[ $parent_new_key ]['woosb_keys'] ) && ! in_array( $cart_item_key, $cart_contents[ $parent_new_key ]['woosb_keys'] ) ) ) {
							unset( $cart_contents[ $cart_item_key ] );
							continue;
						}
					}

					// sync quantity
					if ( ! empty( $cart_item['woosb_qty'] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $cart_item['woosb_qty'] * $cart_contents[ $parent_new_key ]['quantity'];
					}

					// set price
					if ( isset( $cart_item['woosb_fixed_price'] ) && $cart_item['woosb_fixed_price'] ) {
						$cart_item['data']->set_price( 0 );
					} else {
						$_product = wc_get_product( ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'] );
						$_price   = (float) WPCleverWoosb_Helper()->get_price( $_product );

						if ( ! empty( $cart_item['woosb_discount'] ) ) {
							$_price *= ( 100 - (float) $cart_item['woosb_discount'] ) / 100;
						}

						$_price = WPCleverWoosb_Helper()->round_price( $_price );
						$_price = apply_filters( 'woosb_item_price_add_to_cart', $_price, $cart_item );
						$_price = apply_filters( 'woosb_item_price_before_set', $_price, $cart_item );
						$cart_item['data']->set_price( $_price );
					}
				}

				// bundles
				if ( ! empty( $cart_item['woosb_ids'] ) && isset( $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
					// set tax status 'none'
					$cart_item['data']->set_tax_status( 'none' );

					// set price zero, calculate later
					if ( isset( $cart_item['woosb_discount_amount'] ) && $cart_item['woosb_discount_amount'] ) {
						$bundles_price = - (float) $cart_item['woosb_discount_amount'];
					} else {
						$bundles_price = 0;
					}

					$cart_item['data']->set_price( apply_filters( 'woosb_bundles_price', $bundles_price, $cart_item ) );

					if ( ! empty( $cart_item['woosb_keys'] ) ) {
						$bundles_display_price = 0;

						foreach ( $cart_item['woosb_keys'] as $key ) {
							if ( isset( $cart_contents[ $key ], $cart_contents[ $key ]['data'] ) ) {
								$_product = wc_get_product( ! empty( $cart_contents[ $key ]['variation_id'] ) ? $cart_contents[ $key ]['variation_id'] : $cart_contents[ $key ]['product_id'] );
								$_price   = (float) WPCleverWoosb_Helper()->get_price( $_product );

								if ( ! empty( $cart_contents[ $key ]['woosb_discount'] ) ) {
									$_price *= ( 100 - (float) $cart_item['woosb_discount'] ) / 100;
								}

								$_price = WPCleverWoosb_Helper()->round_price( $_price );
								$_price = apply_filters( 'woosb_item_price_add_to_cart', $_price, $cart_item );

								if ( WC()->cart->display_prices_including_tax() ) {
									$_price = wc_get_price_including_tax( $cart_contents[ $key ]['data'], [
										'price' => $_price,
										'qty'   => $cart_contents[ $key ]['woosb_qty']
									] );
								} else {
									$_price = wc_get_price_excluding_tax( $cart_contents[ $key ]['data'], [
										'price' => $_price,
										'qty'   => $cart_contents[ $key ]['woosb_qty']
									] );
								}

								$bundles_display_price += WPCleverWoosb_Helper()->round_price( $_price );
							}
						}

						if ( ! empty( $cart_item['woosb_discount_amount'] ) ) {
							$bundles_display_price -= (float) $cart_item['woosb_discount_amount'];
						}

						$bundles_display_price = apply_filters( 'woosb_bundles_display_price', $bundles_display_price, $cart_item );

						if ( $cart_item['quantity'] > 0 ) {
							// store bundles total
							WC()->cart->cart_contents[ $cart_item_key ]['woosb_price'] = WPCleverWoosb_Helper()->round_price( $bundles_display_price );
						}
					}
				}
			}
		}

		function cart_item_price( $price, $cart_item ) {
			if ( isset( $cart_item['woosb_ids'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
				return apply_filters( 'woosb_cart_item_price', wc_price( $cart_item['woosb_price'] ), $price, $cart_item );
			}

			if ( isset( $cart_item['woosb_parent_id'], $cart_item['woosb_fixed_price'] ) && $cart_item['woosb_fixed_price'] ) {
				$_product = wc_get_product( $cart_item['product_id'] );

				if ( WC()->cart->display_prices_including_tax() ) {
					$_price = wc_price( wc_get_price_including_tax( $_product ) );
				} else {
					$_price = wc_price( wc_get_price_excluding_tax( $_product ) );
				}

				return apply_filters( 'woosb_cart_item_price', $_price, $price, $cart_item );
			}

			return $price;
		}

		function cart_item_subtotal( $subtotal, $cart_item = null ) {
			if ( isset( $cart_item['woosb_ids'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
				$_subtotal = wc_price( $cart_item['woosb_price'] * $cart_item['quantity'] );

				if ( wc_tax_enabled() && WC()->cart->display_prices_including_tax() && ! wc_prices_include_tax() ) {
					$_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

				return apply_filters( 'woosb_cart_item_subtotal', $_subtotal, $subtotal, $cart_item );
			}

			if ( isset( $cart_item['woosb_parent_id'], $cart_item['woosb_fixed_price'] ) && $cart_item['woosb_fixed_price'] ) {
				$_product = wc_get_product( $cart_item['product_id'] );

				if ( WC()->cart->display_prices_including_tax() ) {
					$_subtotal = wc_price( wc_get_price_including_tax( $_product, [ 'qty' => $cart_item['quantity'] ] ) );
				} else {
					$_subtotal = wc_price( wc_get_price_excluding_tax( $_product, [ 'qty' => $cart_item['quantity'] ] ) );
				}

				if ( wc_tax_enabled() && WC()->cart->display_prices_including_tax() && ! wc_prices_include_tax() ) {
					$_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

				return apply_filters( 'woosb_cart_item_subtotal', $_subtotal, $subtotal, $cart_item );
			}

			return $subtotal;
		}

		function mini_cart_item_visible( $visible, $cart_item ) {
			if ( isset( $cart_item['woosb_parent_id'] ) ) {
				if ( ! apply_filters( 'woosb_item_visible', true, $cart_item['data'], $cart_item['woosb_parent_id'] ) ) {
					return false;
				}

				if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled_mini_cart', 'no' ) === 'yes' ) {
					return false;
				}
			}

			return $visible;
		}

		function cart_item_visible( $visible, $cart_item ) {
			if ( isset( $cart_item['woosb_parent_id'] ) ) {
				if ( ! apply_filters( 'woosb_item_visible', true, $cart_item['data'], $cart_item['woosb_parent_id'] ) ) {
					return false;
				}

				if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled', 'no' ) !== 'no' ) {
					return false;
				}
			}

			return $visible;
		}

		function order_item_visible( $visible, $order_item ) {
			if ( $parent_id = $order_item->get_meta( '_woosb_parent_id' ) ) {
				if ( ! apply_filters( 'woosb_item_visible', true, $order_item->get_product(), $parent_id ) ) {
					return false;
				}

				if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled_order', 'no' ) !== 'no' ) {
					return false;
				}
			}

			return $visible;
		}

		function cart_item_class( $class, $cart_item ) {
			if ( isset( $cart_item['woosb_parent_id'] ) ) {
				$class .= ' woosb-cart-item woosb-cart-child woosb-item-child';
			} elseif ( isset( $cart_item['woosb_ids'] ) ) {
				$class .= ' woosb-cart-item woosb-cart-parent woosb-item-parent';
			}

			return $class;
		}

		function cart_item_meta( $data, $cart_item ) {
			if ( empty( $cart_item['woosb_ids'] ) ) {
				return $data;
			}

			$cart_item['data']->build_items( $cart_item['woosb_ids'] );
			$items     = $cart_item['data']->get_items();
			$parent_id = $cart_item['product_id'];

			if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled', 'no' ) === 'yes_list' ) {
				$items_str = [];

				if ( is_array( $items ) && ! empty( $items ) ) {
					foreach ( $items as $item ) {
						if ( ! apply_filters( 'woosb_item_visible', true, $item['id'], $parent_id ) ) {
							continue;
						}

						$items_str[] = apply_filters( 'woosb_order_bundled_product_name', '<li>' . $item['qty'] . ' × ' . get_the_title( $item['id'] ) . '</li>', $item );
					}
				}

				$data['woosb_data'] = [
					'key'     => WPCleverWoosb_Helper()->localization( 'bundled_products', esc_html__( 'Bundled products', 'woo-product-bundle' ) ),
					'value'   => esc_html( $cart_item['woosb_ids'] ),
					'display' => apply_filters( 'woosb_order_bundled_product_names', '<ul>' . implode( '', $items_str ) . '</ul>', $items ),
				];
			} else {
				$items_str = [];

				if ( is_array( $items ) && ! empty( $items ) ) {
					foreach ( $items as $item ) {
						if ( ! apply_filters( 'woosb_item_visible', true, $item['id'], $parent_id ) ) {
							continue;
						}

						$items_str[] = apply_filters( 'woosb_order_bundled_product_name', $item['qty'] . ' × ' . get_the_title( $item['id'] ), $item );
					}
				}

				$data['woosb_data'] = [
					'key'     => WPCleverWoosb_Helper()->localization( 'bundled_products', esc_html__( 'Bundled products', 'woo-product-bundle' ) ),
					'value'   => esc_html( $cart_item['woosb_ids'] ),
					'display' => apply_filters( 'woosb_order_bundled_product_names', implode( '; ', $items_str ), $items ),
				];
			}

			return $data;
		}

		function create_order_line_item( $order_item, $cart_item_key, $values ) {
			if ( isset( $values['woosb_parent_id'] ) ) {
				// use _ to hide the data
				$order_item->update_meta_data( '_woosb_parent_id', $values['woosb_parent_id'] );
			}

			if ( isset( $values['woosb_ids'] ) ) {
				// use _ to hide the data
				$order_item->update_meta_data( '_woosb_ids', $values['woosb_ids'] );
			}

			if ( isset( $values['woosb_price'] ) ) {
				// use _ to hide the data
				$order_item->update_meta_data( '_woosb_price', $values['woosb_price'] );
			}
		}

		function ajax_add_order_item_meta( $order_item_id, $order_item, $order ) {
			$quantity = $order_item->get_quantity();

			if ( 'line_item' === $order_item->get_type() ) {
				$product = $order_item->get_product();

				if ( $product && $product->is_type( 'woosb' ) && ( $items = $product->get_items() ) ) {
					$product_id = $product->get_id();
					$items      = WPCleverWoosb_Helper()->minify_items( $items );

					// get bundle info
					$fixed_price         = $product->is_fixed_price();
					$discount_amount     = $product->get_discount_amount();
					$discount_percentage = $product->get_discount_percentage();

					// add the bundle
					if ( ! $fixed_price ) {
						if ( $discount_amount ) {
							$product->set_price( - (float) $discount_amount );
						} else {
							WPCleverWoosb_Helper()->set_price( $product, 0 );
						}
					}

					if ( $order_id = $order->add_product( $product, $quantity ) ) {
						$order_item = $order->get_item( $order_id );
						$order_item->update_meta_data( '_woosb_ids', $product->get_ids_str(), true );
						$order_item->save();

						foreach ( $items as $item ) {
							$_product = wc_get_product( $item['id'] );

							if ( ! $_product || in_array( $_product->get_type(), self::$types, true ) ) {
								continue;
							}

							if ( $fixed_price ) {
								WPCleverWoosb_Helper()->set_price( $_product, 0 );
							} elseif ( $discount_percentage ) {
								$_price = (float) ( 100 - $discount_percentage ) * WPCleverWoosb_Helper()->get_price( $_product ) / 100;
								$_price = apply_filters( 'woosb_product_price_before_set', $_price, $_product );
								$_product->set_price( $_price );
							}

							// add bundled products
							$_order_item_id = $order->add_product( $_product, $item['qty'] * $quantity );

							if ( ! $_order_item_id ) {
								continue;
							}

							$_order_item = $order->get_item( $_order_item_id );
							$_order_item->update_meta_data( '_woosb_parent_id', $product_id, true );
							$_order_item->save();
						}

						// remove the old bundle
						$order->remove_item( $order_item_id );
					}
				}

				$order->save();
			}
		}

		function hidden_order_itemmeta( $hidden ) {
			return array_merge( $hidden, [
				'_woosb_parent_id',
				'_woosb_ids',
				'_woosb_price',
				'woosb_parent_id',
				'woosb_ids',
				'woosb_price'
			] );
		}

		function order_item_meta_start( $order_item_id, $order_item ) {
			if ( $ids = $order_item->get_meta( '_woosb_ids' ) ) {
				$parent    = $order_item->get_product();
				$parent_id = $parent->get_id();
				$items     = self::get_bundled( $ids, $parent );

				if ( WPCleverWoosb_Helper()->get_setting( 'hide_bundled_order', 'no' ) === 'yes_list' ) {
					$items_str = [];

					if ( is_array( $items ) && ! empty( $items ) ) {
						foreach ( $items as $item ) {
							if ( ! empty( $item['id'] ) ) {
								if ( ! apply_filters( 'woosb_item_visible', true, $item['id'], $parent_id ) ) {
									continue;
								}

								$items_str[] = apply_filters( 'woosb_order_bundled_product_name', '<li>' . $item['qty'] . ' × ' . get_the_title( $item['id'] ) . '</li>', $item );
							}
						}
					}

					$items_str = apply_filters( 'woosb_order_bundled_product_names', '<ul>' . implode( '', $items_str ) . '</ul>', $items );
				} else {
					$items_str = [];

					if ( is_array( $items ) && ! empty( $items ) ) {
						foreach ( $items as $item ) {
							if ( ! empty( $item['id'] ) ) {
								if ( ! apply_filters( 'woosb_item_visible', true, $item['id'], $parent_id ) ) {
									continue;
								}

								$items_str[] = apply_filters( 'woosb_order_bundled_product_name', $item['qty'] . ' × ' . get_the_title( $item['id'] ), $item );
							}
						}
					}

					$items_str = apply_filters( 'woosb_order_bundled_product_names', implode( '; ', $items_str ), $items );
				}

				echo apply_filters( 'woosb_before_order_itemmeta_bundles', '<div class="woosb-itemmeta-bundles">' . /* translators: bundled products */ sprintf( WPCleverWoosb_Helper()->localization( 'bundled_products_s', esc_html__( 'Bundled products: %s', 'woo-product-bundle' ) ), $items_str ) . '</div>', $order_item_id, $order_item );
			}
		}

		function before_order_itemmeta( $order_item_id, $order_item ) {
			// admin orders
			if ( ( $ids = $order_item->get_meta( '_woosb_ids' ) ) && ( $parent = $order_item->get_product() ) ) {
				$parent_id = $parent->get_id();
				$items     = self::get_bundled( $ids, $parent );
				$items_str = [];

				if ( is_array( $items ) && ! empty( $items ) ) {
					foreach ( $items as $item ) {
						if ( ! empty( $item['id'] ) ) {
							if ( ! apply_filters( 'woosb_item_visible', true, $item['id'], $parent_id ) ) {
								continue;
							}

							$items_str[] = apply_filters( 'woosb_admin_order_bundled_product_name', '<li>' . $item['qty'] . ' × ' . get_the_title( $item['id'] ) . '</li>', $item );
						}
					}
				}

				$items_str = apply_filters( 'woosb_admin_order_bundled_product_names', '<ul>' . implode( '', $items_str ) . '</ul>', $items );

				echo apply_filters( 'woosb_before_admin_order_itemmeta_bundles', '<div class="woosb-itemmeta-bundles woosb-admin-itemmeta-bundles">' . /* translators: bundled products */ sprintf( WPCleverWoosb_Helper()->localization( 'bundled_products_s', esc_html__( 'Bundled products: %s', 'woo-product-bundle' ) ), $items_str ) . '</div>', $order_item_id, $order_item );
			}

			if ( $parent_id = $order_item->get_meta( '_woosb_parent_id' ) ) {
				echo apply_filters( 'woosb_before_admin_order_itemmeta_bundled', '<div class="woosb-itemmeta-bundled woosb-admin-itemmeta-bundled">' . /* translators: bundled in */ sprintf( WPCleverWoosb_Helper()->localization( 'bundled_in_s', esc_html__( 'Bundled in: %s', 'woo-product-bundle' ) ), get_the_title( $parent_id ) ) . '</div>', $order_item_id, $order_item );
			}
		}

		function formatted_line_subtotal( $subtotal, $order_item ) {
			if ( isset( $order_item['_woosb_ids'], $order_item['_woosb_price'] ) ) {
				return apply_filters( 'woosb_order_item_subtotal', wc_price( $order_item['_woosb_price'] * $order_item['quantity'] ), $subtotal, $order_item );
			}

			return $subtotal;
		}

		function cart_item_remove_link( $link, $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['woosb_parent_key'] ) ) {
				$parent_key = WC()->cart->cart_contents[ $cart_item_key ]['woosb_parent_key'];

				if ( isset( WC()->cart->cart_contents[ $parent_key ] ) || array_search( $parent_key, array_column( WC()->cart->cart_contents, 'woosb_key', 'key' ) ) ) {
					return '';
				}
			}

			return $link;
		}

		function cart_item_quantity( $quantity, $cart_item_key, $cart_item ) {
			// add qty as text - not input
			if ( isset( $cart_item['woosb_parent_id'] ) ) {
				return $cart_item['quantity'];
			}

			return $quantity;
		}

		function ajax_update_search_settings() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'woosb-security' ) || ! current_user_can( 'manage_options' ) ) {
				die( 'Permissions check failed!' );
			}

			$settings                      = (array) get_option( 'woosb_settings', [] );
			$settings['search_limit']      = (int) sanitize_text_field( $_POST['limit'] );
			$settings['search_sku']        = sanitize_text_field( $_POST['sku'] );
			$settings['search_id']         = sanitize_text_field( $_POST['id'] );
			$settings['search_exact']      = sanitize_text_field( $_POST['exact'] );
			$settings['search_sentence']   = sanitize_text_field( $_POST['sentence'] );
			$settings['search_same']       = sanitize_text_field( $_POST['same'] );
			$settings['search_show_image'] = sanitize_text_field( $_POST['show_image'] );
			$settings['search_types']      = array_map( 'sanitize_text_field', (array) $_POST['types'] );

			update_option( 'woosb_settings', $settings );
			wp_die();
		}

		function ajax_get_search_results() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'woosb-security' ) ) {
				die( 'Permissions check failed!' );
			}

			$keyword   = sanitize_text_field( $_POST['keyword'] );
			$added_ids = explode( ',', WPCleverWoosb_Helper()->clean_ids( $_POST['ids'] ) );
			$types     = WPCleverWoosb_Helper()->get_setting( 'search_types', [ 'all' ] );

			if ( ( WPCleverWoosb_Helper()->get_setting( 'search_id', 'no' ) === 'yes' ) && is_numeric( $keyword ) ) {
				// search by id
				$query_args = [
					'p'         => absint( $keyword ),
					'post_type' => 'product'
				];
			} else {
				$limit = absint( WPCleverWoosb_Helper()->get_setting( 'search_limit', 10 ) );

				if ( $limit < 1 ) {
					$limit = 10;
				} elseif ( $limit > 500 ) {
					$limit = 500;
				}

				$query_args = [
					'is_woosb'       => true,
					'post_type'      => 'product',
					'post_status'    => [ 'publish', 'private' ],
					's'              => $keyword,
					'posts_per_page' => $limit
				];

				if ( ! empty( $types ) && ! in_array( 'all', $types, true ) ) {
					$product_types = $types;

					if ( in_array( 'variation', $types, true ) ) {
						$product_types[] = 'variable';
					}

					$query_args['tax_query'] = [
						[
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => $product_types,
						],
					];
				}

				if ( WPCleverWoosb_Helper()->get_setting( 'search_same', 'no' ) !== 'yes' ) {
					$query_args['post__not_in'] = array_map( 'absint', $added_ids );
				}
			}

			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {
				echo '<div class="woosb-ul">';

				while ( $query->have_posts() ) {
					$query->the_post();
					$_product = wc_get_product( get_the_ID() );

					if ( ! $_product ) {
						continue;
					}

					if ( ! $_product->is_type( 'variable' ) || in_array( 'variable', $types, true ) || in_array( 'all', $types, true ) ) {
						self::product_data_li( $_product, [ 'qty' => 1 ], '', true );
					}

					if ( $_product->is_type( 'variable' ) && ( empty( $types ) || in_array( 'all', $types, true ) || in_array( 'variation', $types, true ) ) ) {
						// show all children
						$children = $_product->get_children();

						if ( is_array( $children ) && count( $children ) > 0 ) {
							foreach ( $children as $child ) {
								$child_product = wc_get_product( $child );
								self::product_data_li( $child_product, [ 'qty' => 1 ], '', true );
							}
						}
					}
				}

				echo '</div>';
				wp_reset_postdata();
			} else {
				echo '<div class="woosb-ul"><span>' . /* translators: keyword */ sprintf( esc_html__( 'No results found for "%s"', 'woo-product-bundle' ), esc_html( $keyword ) ) . '</span></div>';
			}

			wp_die();
		}

		function search_sku( $query ) {
			if ( $query->is_search && isset( $query->query['is_woosb'] ) ) {
				global $wpdb;

				$sku = sanitize_text_field( $query->query['s'] );
				$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value = %s;", $sku ) );

				if ( ! $ids ) {
					return;
				}

				$posts = [];

				foreach ( $ids as $id ) {
					$post = get_post( $id );

					if ( $post->post_type === 'product_variation' ) {
						$posts[] = $post->post_parent;
					} else {
						$posts[] = $post->ID;
					}
				}

				unset( $query->query['s'], $query->query_vars['s'] );
				$query->set( 'post__in', $posts );
			}
		}

		function search_exact( $query ) {
			if ( $query->is_search && isset( $query->query['is_woosb'] ) ) {
				$query->set( 'exact', true );
			}
		}

		function search_sentence( $query ) {
			if ( $query->is_search && isset( $query->query['is_woosb'] ) ) {
				$query->set( 'sentence', true );
			}
		}

		function product_type_selector( $types ) {
			$types['woosb'] = esc_html__( 'Smart bundle', 'woo-product-bundle' );

			return $types;
		}

		function product_data_tabs( $tabs ) {
			$tabs['woosb'] = [
				'label'  => esc_html__( 'Bundled Products', 'woo-product-bundle' ),
				'target' => 'woosb_settings',
				'class'  => [ 'show_if_woosb' ],
			];

			return $tabs;
		}

		function product_summary_bundles() {
			self::show_bundles();
		}

		function product_summary_bundled() {
			self::show_bundled();
		}

		function product_tabs( $tabs ) {
			global $product;
			$product_id = $product->get_id();

			if ( ( WPCleverWoosb_Helper()->get_setting( 'bundled_position', 'above' ) === 'tab' ) && $product->is_type( 'woosb' ) ) {
				$tabs['woosb_bundled'] = apply_filters( 'woosb_bundled_tab', [
					'title'    => WPCleverWoosb_Helper()->localization( 'bundled_products', esc_html__( 'Bundled products', 'woo-product-bundle' ) ),
					'priority' => 50,
					'callback' => [ $this, 'product_tab_bundled' ]
				], $product );
			}

			if ( ( WPCleverWoosb_Helper()->get_setting( 'bundles_position', 'no' ) === 'tab' ) && ! $product->is_type( 'woosb' ) && self::get_bundles( $product_id ) ) {
				$tabs['woosb_bundles'] = apply_filters( 'woosb_bundles_tab', [
					'title'    => WPCleverWoosb_Helper()->localization( 'bundles', esc_html__( 'Bundles', 'woo-product-bundle' ) ),
					'priority' => 50,
					'callback' => [ $this, 'product_tab_bundles' ]
				], $product );
			}

			return $tabs;
		}

		function product_tab_bundled() {
			self::show_bundled();
		}

		function product_tab_bundles() {
			self::show_bundles();
		}

		function product_data_panels() {
			global $post, $thepostid, $product_object;

			if ( $product_object instanceof WC_Product ) {
				$product_id = $product_object->get_id();
			} elseif ( is_numeric( $thepostid ) ) {
				$product_id = $thepostid;
			} elseif ( $post instanceof WP_Post ) {
				$product_id = $post->ID;
			} else {
				$product_id = 0;
			}

			if ( ! $product_id ) {
				?>
                <div id='woosb_settings' class='panel woocommerce_options_panel woosb_table'>
                    <p style="padding: 0 12px; color: #c9356e"><?php esc_html_e( 'Product wasn\'t returned.', 'woo-product-bundle' ); ?></p>
                </div>
				<?php
				return;
			}

			if ( get_post_meta( $product_id, 'woosb_ids', true ) ) {
				$ids = get_post_meta( $product_id, 'woosb_ids', true );
			} elseif ( isset( $_GET['woosb_ids'] ) ) {
				$ids = implode( ',', explode( '.', sanitize_text_field( $_GET['woosb_ids'] ) ) );
			} else {
				$ids = '';
			}

			if ( ! empty( $_GET['woosb_ids'] ) ) {
				?>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        $('#product-type').val('woosb').trigger('change');
                    });
                </script>
				<?php
			}
			?>
            <div id='woosb_settings' class='panel woocommerce_options_panel woosb_table'>
                <div id="woosb_search_settings" style="display: none"
                     data-title="<?php esc_html_e( 'Search settings', 'woo-product-bundle' ); ?>">
                    <table>
						<?php self::search_settings(); ?>
                        <tr>
                            <th></th>
                            <td>
                                <button id="woosb_search_settings_update" class="button button-primary">
									<?php esc_html_e( 'Update Options', 'woo-product-bundle' ); ?>
                                </button>
                            </td>
                        </tr>
                    </table>
                </div>
                <table>
                    <tr>
                        <th><?php esc_html_e( 'Search', 'woo-product-bundle' ); ?> (<a
                                    href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-woosb&tab=settings#search' ) ); ?>"
                                    id="woosb_search_settings_btn"><?php esc_html_e( 'settings', 'woo-product-bundle' ); ?></a>)
                        </th>
                        <td>
                            <div class="w100">
                                <span class="loading" id="woosb_loading"
                                      style="display: none;"><?php esc_html_e( 'searching...', 'woo-product-bundle' ); ?></span>
                                <label for="woosb_keyword"></label><input type="search" id="woosb_keyword"
                                                                          placeholder="<?php esc_attr_e( 'Type any keyword to search', 'woo-product-bundle' ); ?>"/>
                                <div id="woosb_results" class="woosb_results" style="display: none;"></div>
                            </div>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Selected', 'woo-product-bundle' ); ?></th>
                        <td>
                            <div id="woosb_selected" class="woosb_selected">
                                <div class="woosb-ul">
									<?php
									if ( ! empty( $ids ) ) {
										$items = self::get_bundled( $ids, $product_id );

										if ( ! empty( $items ) ) {
											foreach ( $items as $key => $item ) {
												if ( ! empty( $item['id'] ) ) {
													if ( apply_filters( 'woosb_use_sku', false ) && ! empty( $item['sku'] ) ) {
														if ( $new_id = WPCleverWoosb_Helper()->get_product_id_from_sku( $item['sku'] ) ) {
															$item['id'] = $new_id;
														}
													}

													$_product = wc_get_product( $item['id'] );

													if ( ! $_product || in_array( $_product->get_type(), self::$types, true ) ) {
														continue;
													}

													self::product_data_li( $_product, $item, $key );
												} else {
													// new version 7.0
													self::text_data_li( $item, $key );
												}
											}
										}
									}
									?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th></th>
                        <td>
                            <div class="woosb-actions">
                                <a href="https://wpclever.net/downloads/product-bundles?utm_source=pro&utm_medium=woosb&utm_campaign=wporg"
                                   target="_blank" class="woosb_add_txt button"
                                   onclick="return confirm('This feature only available in Premium Version!\nBuy it now? Just $29')">
									<?php esc_html_e( '+ Add heading/paragraph', 'woo-product-bundle' ); ?>
                                </a>
                                <label for="woosb_bulk_actions"></label><select id="woosb_bulk_actions">
                                    <option value="none"><?php esc_html_e( 'Bulk actions', 'woo-product-bundle' ); ?></option>
                                    <option value="enable_optional"><?php esc_html_e( 'Enable "Custom quantity"', 'woo-product-bundle' ); ?></option>
                                    <option value="disable_optional"><?php esc_html_e( 'Disable "Custom quantity"', 'woo-product-bundle' ); ?></option>
                                    <option value="set_qty_default"><?php esc_html_e( 'Set default quantity', 'woo-product-bundle' ); ?></option>
                                    <option value="set_qty_min"><?php esc_html_e( 'Set min quantity', 'woo-product-bundle' ); ?></option>
                                    <option value="set_qty_max"><?php esc_html_e( 'Set max quantity', 'woo-product-bundle' ); ?></option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php echo esc_html__( 'Regular price', 'woo-product-bundle' ) . ' (' . esc_html( get_woocommerce_currency_symbol() ) . ')'; ?></th>
                        <td>
                            <span id="woosb_regular_price"></span>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Fixed price', 'woo-product-bundle' ); ?></th>
                        <td>
							<?php $disable_auto_price = get_post_meta( $product_id, 'woosb_disable_auto_price', true ) ?: apply_filters( 'woosb_disable_auto_price_default', 'off' ); ?>
                            <input id="woosb_disable_auto_price" name="woosb_disable_auto_price"
                                   type="checkbox" <?php echo esc_attr( $disable_auto_price === 'on' ? 'checked' : '' ); ?>/>
                            <label for="woosb_disable_auto_price"><?php esc_html_e( 'Disable auto calculate price.', 'woo-product-bundle' ); ?></label>
                            <label><?php /* translators: set price link */
								echo sprintf( esc_html__( 'If checked, %1$s click here to set price %2$s by manually.', 'woo-product-bundle' ), '<a id="woosb_set_regular_price">', '</a>' ); ?></label>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space woosb_tr_show_if_auto_price">
                        <th><?php esc_html_e( 'Discount', 'woo-product-bundle' ); ?></th>
                        <td style="vertical-align: middle; line-height: 30px;">
                            <label for="woosb_discount"></label><input id="woosb_discount" name="woosb_discount"
                                                                       type="number" min="0" step="0.0001" max="99.9999"
                                                                       style="width: 80px"
                                                                       value="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_discount', true ) ); ?>"/> <?php esc_html_e( '% or amount', 'woo-product-bundle' ); ?>
                            <label for="woosb_discount_amount"></label><input id="woosb_discount_amount"
                                                                              name="woosb_discount_amount" type="number"
                                                                              min="0" step="0.0001" style="width: 80px"
                                                                              value="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_discount_amount', true ) ); ?>"/> <?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
                            . <?php esc_html_e( 'If you fill both, the amount will be used.', 'woo-product-bundle' ); ?>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Quantity limits', 'woo-product-bundle' ); ?></th>
                        <td>
							<?php esc_html_e( 'Min', 'woo-product-bundle' ); ?>
                            <label>
                                <input name="woosb_limit_whole_min" type="number" min="1"
                                       style="width: 60px; float: none"
                                       value="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ); ?>"/>
                            </label>
							<?php esc_html_e( 'Max', 'woo-product-bundle' ); ?>
                            <label>
                                <input name="woosb_limit_whole_max" type="number" min="1"
                                       style="width: 60px; float: none"
                                       value="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_limit_whole_max', true ) ); ?>"/>
                            </label>
                            <span class="woocommerce-help-tip"
                                  data-tip="<?php esc_attr_e( 'Limit the total quantity when the bundle includes optional products.', 'woo-product-bundle' ); ?>"></span>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Total limits', 'woo-product-bundle' ); ?></th>
                        <td>
                            <input id="woosb_total_limits" name="woosb_total_limits"
                                   type="checkbox" <?php echo esc_attr( get_post_meta( $product_id, 'woosb_total_limits', true ) === 'on' ? 'checked' : '' ); ?>/>
                            <label for="woosb_total_limits"><?php esc_html_e( 'Configure total limits for the current bundle.', 'woo-product-bundle' ); ?></label>
                            <span class="woocommerce-help-tip"
                                  data-tip="<?php esc_attr_e( 'When a bundle includes variable or optional products, bundle\'s price will vary depending on the item selection. Thus, this option can be enabled to limit the bundle total\'s min-max.', 'woo-product-bundle' ); ?>"></span>
                            <span class="woosb_show_if_total_limits">
                                <?php esc_html_e( 'Min', 'woo-product-bundle' ); ?> <label
                                        for="woosb_total_limits_min"></label><input id="woosb_total_limits_min"
                                                                                    name="woosb_total_limits_min"
                                                                                    type="number" min="0"
                                                                                    style="width: 80px"
                                                                                    value="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_total_limits_min', true ) ); ?>"/>
                                <?php esc_html_e( 'Max', 'woo-product-bundle' ); ?> <label
                                        for="woosb_total_limits_max"></label><input id="woosb_total_limits_max"
                                                                                    name="woosb_total_limits_max"
                                                                                    type="number" min="0"
                                                                                    style="width: 80px"
                                                                                    value="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_total_limits_max', true ) ); ?>"/> <?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
                            </span>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Shipping fee', 'woo-product-bundle' ); ?></th>
                        <td>
							<?php $shipping_fee = get_post_meta( $product_id, 'woosb_shipping_fee', true ); ?>
                            <label for="woosb_shipping_fee"></label><select id="woosb_shipping_fee"
                                                                            name="woosb_shipping_fee">
                                <option value="whole" <?php selected( $shipping_fee, 'whole' ); ?>><?php esc_html_e( 'Apply to the main bundle product', 'woo-product-bundle' ); ?></option>
                                <option value="each" <?php selected( $shipping_fee, 'each' ); ?>><?php esc_html_e( 'Apply to each bundled sub-product', 'woo-product-bundle' ); ?></option>
                                <option value="both" <?php selected( $shipping_fee, 'both' ); ?>><?php esc_html_e( 'Apply to both bundle & bundled sub-product', 'woo-product-bundle' ); ?></option>
                            </select>
                        </td>
                    </tr>
					<?php if ( ! apply_filters( 'woosb_disable_inventory_management', false ) ) { ?>
                        <tr class="woosb_tr_space">
                            <th><?php esc_html_e( 'Manage stock', 'woo-product-bundle' ); ?></th>
                            <td>
                                <input id="woosb_manage_stock" name="woosb_manage_stock"
                                       type="checkbox" <?php echo esc_attr( get_post_meta( $product_id, 'woosb_manage_stock', true ) === 'on' ? 'checked' : '' ); ?>/>
                                <label for="woosb_manage_stock"><?php esc_html_e( 'Enable stock management at bundle level.', 'woo-product-bundle' ); ?></label>
                                <span class="woocommerce-help-tip"
                                      data-tip="<?php esc_attr_e( 'By default, the bundle\' stock was calculated automatically from bundled products. After enabling, please press "Update" then you can change the stock settings on the "Inventory" tab.', 'woo-product-bundle' ); ?>"></span>
                            </td>
                        </tr>
					<?php } ?>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Custom display price', 'woo-product-bundle' ); ?></th>
                        <td>
                            <label>
                                <input type="text" name="woosb_custom_price"
                                       value="<?php echo esc_attr( get_post_meta( $product_id, 'woosb_custom_price', true ) ); ?>"/>
                            </label> E.g: <code>From $10 to $100</code>. <?php /* translators: dynamic price */
							esc_html_e( 'You can use %s to show the dynamic price between your custom text.', 'woo-product-bundle' ); ?>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Exclude un-purchasable', 'woo-product-bundle' ); ?></th>
                        <td>
							<?php $exclude_unpurchasable = get_post_meta( $product_id, 'woosb_exclude_unpurchasable', true ) ?: 'unset'; ?>
                            <label> <select name="woosb_exclude_unpurchasable">
                                    <option value="unset" <?php selected( $exclude_unpurchasable, 'unset' ); ?>><?php esc_html_e( 'Default', 'woo-product-bundle' ); ?></option>
                                    <option value="yes" <?php selected( $exclude_unpurchasable, 'yes' ); ?>><?php esc_html_e( 'Yes', 'woo-product-bundle' ); ?></option>
                                    <option value="no" <?php selected( $exclude_unpurchasable, 'no' ); ?>><?php esc_html_e( 'No', 'woo-product-bundle' ); ?></option>
                                </select> </label>
                            <span class="woocommerce-help-tip"
                                  data-tip="<?php esc_attr_e( 'Make the bundle still purchasable when one of the bundled products is un-purchasable. These bundled products are excluded from the orders.', 'woo-product-bundle' ); ?>"></span>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Layout', 'woo-product-bundle' ); ?></th>
                        <td>
							<?php $layout = get_post_meta( $product_id, 'woosb_layout', true ) ?: 'unset'; ?>
                            <label> <select name="woosb_layout">
                                    <option value="unset" <?php selected( $layout, 'unset' ); ?>><?php esc_html_e( 'Default', 'woo-product-bundle' ); ?></option>
                                    <option value="list" <?php selected( $layout, 'list' ); ?>><?php esc_html_e( 'List', 'woo-product-bundle' ); ?></option>
                                    <option value="grid-2" <?php selected( $layout, 'grid-2' ); ?>><?php esc_html_e( 'Grid - 2 columns', 'woo-product-bundle' ); ?></option>
                                    <option value="grid-3" <?php selected( $layout, 'grid-3' ); ?>><?php esc_html_e( 'Grid - 3 columns', 'woo-product-bundle' ); ?></option>
                                    <option value="grid-4" <?php selected( $layout, 'grid-4' ); ?>><?php esc_html_e( 'Grid - 4 columns', 'woo-product-bundle' ); ?></option>
                                </select> </label>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Above text', 'woo-product-bundle' ); ?></th>
                        <td>
                            <div class="w100">
                                <label>
                                    <textarea
                                            name="woosb_before_text"><?php echo esc_textarea( get_post_meta( $product_id, 'woosb_before_text', true ) ); ?></textarea>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr class="woosb_tr_space">
                        <th><?php esc_html_e( 'Under text', 'woo-product-bundle' ); ?></th>
                        <td>
                            <div class="w100">
                                <label>
                                    <textarea
                                            name="woosb_after_text"><?php echo esc_textarea( get_post_meta( $product_id, 'woosb_after_text', true ) ); ?></textarea>
                                </label>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
			<?php
		}

		function product_data_li( $product, $item, $key = '', $search = false ) {
			if ( empty( $key ) ) {
				$key = WPCleverWoosb_Helper()->generate_key();
			}

			$qty           = $item['qty'] ?? 1;
			$min           = $item['min'] ?? '';
			$max           = $item['max'] ?? '';
			$optional      = $item['optional'] ?? 0;
			$terms         = $item['terms'] ?? [];
			$product_id    = $product->get_id();
			$product_sku   = $product->get_sku();
			$product_name  = $product->get_name();
			$product_class = 'woosb-li woosb-li-product';

			if ( ! $product->is_in_stock() ) {
				$product_class .= ' out-of-stock';
			}

			if ( in_array( $product->get_type(), self::$types, true ) ) {
				$product_class .= ' disabled';
			}

			if ( class_exists( 'WPCleverWoopq' ) && ( WPCleverWoopq::get_setting( 'decimal', 'no' ) === 'yes' ) ) {
				$step = '0.000001';
			} else {
				$step = 1;
			}

			if ( $product->is_sold_individually() ) {
				$qty_input = '<input type="number" class="woosb_item_qty_input" name="' . esc_attr( 'woosb_ids[' . $key . '][qty]' ) . '" value="' . esc_attr( $qty ) . '" min="0" step="' . esc_attr( $step ) . '" max="1"/>';
			} else {
				$qty_input = '<input type="number" class="woosb_item_qty_input" name="' . esc_attr( 'woosb_ids[' . $key . '][qty]' ) . '" value="' . esc_attr( $qty ) . '" min="0" step="' . esc_attr( $step ) . '"/>';
			}

			$price = WPCleverWoosb_Helper()->get_price( $product );
			$price = WPCleverWoosb_Helper()->round_price( $price );

			$price_max = WPCleverWoosb_Helper()->get_price( $product, 'max' );
			$price_max = WPCleverWoosb_Helper()->round_price( $price_max );

			// apply filter same as frontend
			$item_name = apply_filters( 'woosb_item_product_name', $product_name, $product );

			if ( $product->is_type( 'variation' ) ) {
				$edit_link = get_edit_post_link( $product->get_parent_id() );
			} else {
				$edit_link = get_edit_post_link( $product_id );
			}

			$product_info = apply_filters( 'woosb_item_product_info', $product->get_type() . '<br/>#' . $product_id, $product );

			if ( WPCleverWoosb_Helper()->get_setting( 'search_show_image', 'yes' ) === 'yes' ) {
				$product_image = apply_filters( 'woosb_item_product_image', '<span class="img">' . $product->get_image( [
						30,
						30
					] ) . '</span>', $product );
			} else {
				$product_image = '';
			}

			$product_attrs = apply_filters( 'woosb_item_product_attrs', [
				'key'       => $key,
				'name'      => $product_name,
				'sku'       => $product_sku,
				'id'        => $product_id,
				'price'     => $price,
				'price-max' => $price_max,
			] );

			echo '<div class="' . esc_attr( $product_class ) . '" ' . WPCleverWoosb_Helper()->data_attributes( $product_attrs ) . '>';
			echo '<div class="woosb-li-head">';
			echo '<input type="hidden" name="' . esc_attr( 'woosb_ids[' . $key . '][id]' ) . '" value="' . esc_attr( $product_id ) . '"/><input type="hidden" name="' . esc_attr( 'woosb_ids[' . $key . '][sku]' ) . '" value="' . esc_attr( $product_sku ) . '"/>';
			echo '<span class="move"></span><span class="qty hint--right" aria-label="' . esc_html__( 'Default quantity', 'woo-product-bundle' ) . '">' . $qty_input . '</span>' . $product_image . '<span class="data">' . ( $product->get_status() === 'private' ? '<span class="info">private</span> ' : '' ) . '<span class="name">' . esc_html( $item_name ) . '</span><span class="info">' . $product->get_price_html() . '</span> ' . ( $product->is_sold_individually() ? '<span class="info">' . esc_html__( 'sold individually', 'woo-product-bundle' ) . '</span> ' : '' ) . '</span><span class="type"><a href="' . esc_url( $edit_link ) . '" target="_blank">' . $product_info . '</a></span> ';

			if ( $search ) {
				echo '<span class="woosb-remove hint--left" aria-label="' . esc_attr__( 'Add', 'woo-product-bundle' ) . '">+</span>';
			} else {
				echo '<span class="woosb-remove hint--left" aria-label="' . esc_attr__( 'Remove', 'woo-product-bundle' ) . '">×</span>';
			}

			echo '</div><!-- /woosb-li-head -->';
			echo '<div class="woosb-li-body">';
			echo '<div class="qty_config"><div class="qty_config_inner">';
			echo '<span><input type="checkbox" class="optional_checkbox" id="' . esc_attr( 'optional_checkbox_' . $key ) . '" name="' . esc_attr( 'woosb_ids[' . $key . '][optional]' ) . '" value="1" ' . checked( $optional, 1, false ) . '/> <label for="' . esc_attr( 'optional_checkbox_' . $key ) . '">' . esc_html__( 'Custom quantity', 'woo-product-bundle' ) . '</label></span>';
			echo '<span class="optional_min_max">' . esc_html__( 'Min', 'woo-product-bundle' ) . ' <input class="optional_min_input" type="number" name="' . esc_attr( 'woosb_ids[' . $key . '][min]' ) . '" value="' . esc_attr( $min ) . '"/> ' . esc_html__( 'Max', 'woo-product-bundle' ) . ' <input class="optional_max_input" type="number" name="' . esc_attr( 'woosb_ids[' . $key . '][max]' ) . '" value="' . esc_attr( $max ) . '"/></span>';
			echo '</div><!-- /qty_config_inner --></div><!-- /qty_config -->';

			if ( $product->is_type( 'variable' ) ) {
				// settings form
				$attributes = $product->get_variation_attributes();

				echo '<div class="terms_config">By default, all existing terms of the current attribute(s) are enabled for variations. Users can type in to choose some term(s) and enable certain variations only. If any box is left blank, all current terms of the corresponding attribute(s) will be used.';

				if ( is_array( $attributes ) && ( count( $attributes ) > 0 ) ) {
					foreach ( $attributes as $attribute_name => $options ) {
						echo '<div style="margin-top: 10px">';
						echo '<div>' . esc_html( wc_attribute_label( $attribute_name ) ) . '</div>';

						if ( ! empty( $options ) ) {
							$attribute_name_st = sanitize_title( $attribute_name );
							echo '<select class="woosb_select_multiple" name="' . esc_attr( 'woosb_ids[' . $key . '][terms][' . $attribute_name_st . '][]' ) . '" multiple>';

							foreach ( $options as $option ) {
								echo '<option value="' . esc_attr( $option ) . '" ' . ( isset( $terms[ $attribute_name_st ] ) && in_array( $option, $terms[ $attribute_name_st ] ) ? 'selected' : '' ) . '>' . esc_html( $option ) . '</option>';
							}

							echo '</select>';
						}

						echo '</div>';
					}
				}

				echo '</div>';
			}

			echo '</div><!-- /woosb-li-body -->';
			echo '</div><!-- /woosb-li -->';
		}

		function text_data_li( $data = [], $key = '' ) {
			if ( empty( $key ) ) {
				$key = WPCleverWoosb_Helper()->generate_key();
			}

			$data = array_merge( [ 'type' => 'h1', 'text' => '' ], $data );
			$type = '<select name="' . esc_attr( 'woosb_ids[' . $key . '][type]' ) . '"><option value="h1" ' . selected( $data['type'], 'h1', false ) . '>H1</option><option value="h2" ' . selected( $data['type'], 'h2', false ) . '>H2</option><option value="h3" ' . selected( $data['type'], 'h3', false ) . '>H3</option><option value="h4" ' . selected( $data['type'], 'h4', false ) . '>H4</option><option value="h5" ' . selected( $data['type'], 'h5', false ) . '>H5</option><option value="h6" ' . selected( $data['type'], 'h6', false ) . '>H6</option><option value="p" ' . selected( $data['type'], 'p', false ) . '>p</option><option value="span" ' . selected( $data['type'], 'span', false ) . '>span</option><option value="none" ' . selected( $data['type'], 'none', false ) . '>none</option></select>';

			echo '<div class="woosb-li woosb-li-text"><div class="woosb-li-head"><span class="move"></span><span class="tag">' . $type . '</span><span class="data"><input type="text" name="' . esc_attr( 'woosb_ids[' . $key . '][text]' ) . '" value="' . esc_attr( $data['text'] ) . '"/></span><span class="woosb-remove hint--left" aria-label="' . esc_html__( 'Remove', 'woo-product-bundle' ) . '">×</span></div></div>';
		}

		function process_product_meta_woosb( $post_id ) {
			if ( isset( $_POST['woosb_ids'] ) ) {
				update_post_meta( $post_id, 'woosb_ids', WPCleverWoosb_Helper()->sanitize_array( $_POST['woosb_ids'] ) );
			} else {
				delete_post_meta( $post_id, 'woosb_ids' );
			}

			if ( isset( $_POST['woosb_disable_auto_price'] ) ) {
				update_post_meta( $post_id, 'woosb_disable_auto_price', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_disable_auto_price', 'off' );
			}

			if ( isset( $_POST['woosb_discount'] ) ) {
				update_post_meta( $post_id, 'woosb_discount', sanitize_text_field( $_POST['woosb_discount'] ) );
			} else {
				update_post_meta( $post_id, 'woosb_discount', 0 );
			}

			if ( isset( $_POST['woosb_discount_amount'] ) ) {
				update_post_meta( $post_id, 'woosb_discount_amount', sanitize_text_field( $_POST['woosb_discount_amount'] ) );
			} else {
				update_post_meta( $post_id, 'woosb_discount_amount', 0 );
			}

			if ( isset( $_POST['woosb_shipping_fee'] ) ) {
				update_post_meta( $post_id, 'woosb_shipping_fee', sanitize_text_field( $_POST['woosb_shipping_fee'] ) );
			}

			if ( isset( $_POST['woosb_manage_stock'] ) ) {
				update_post_meta( $post_id, 'woosb_manage_stock', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_manage_stock', 'off' );
			}

			if ( isset( $_POST['woosb_limit_whole_min'] ) ) {
				update_post_meta( $post_id, 'woosb_limit_whole_min', sanitize_text_field( $_POST['woosb_limit_whole_min'] ) );
			}

			if ( isset( $_POST['woosb_limit_whole_max'] ) ) {
				update_post_meta( $post_id, 'woosb_limit_whole_max', sanitize_text_field( $_POST['woosb_limit_whole_max'] ) );
			}

			if ( isset( $_POST['woosb_total_limits'] ) ) {
				update_post_meta( $post_id, 'woosb_total_limits', 'on' );
			} else {
				update_post_meta( $post_id, 'woosb_total_limits', 'off' );
			}

			if ( isset( $_POST['woosb_total_limits_min'] ) ) {
				update_post_meta( $post_id, 'woosb_total_limits_min', sanitize_text_field( $_POST['woosb_total_limits_min'] ) );
			}

			if ( isset( $_POST['woosb_total_limits_max'] ) ) {
				update_post_meta( $post_id, 'woosb_total_limits_max', sanitize_text_field( $_POST['woosb_total_limits_max'] ) );
			}

			if ( isset( $_POST['woosb_exclude_unpurchasable'] ) ) {
				update_post_meta( $post_id, 'woosb_exclude_unpurchasable', sanitize_text_field( $_POST['woosb_exclude_unpurchasable'] ) );
			}

			if ( isset( $_POST['woosb_layout'] ) ) {
				update_post_meta( $post_id, 'woosb_layout', sanitize_text_field( $_POST['woosb_layout'] ) );
			}

			if ( isset( $_POST['woosb_custom_price'] ) ) {
				update_post_meta( $post_id, 'woosb_custom_price', sanitize_post_field( 'post_content', $_POST['woosb_custom_price'], $post_id, 'display' ) );
			}

			if ( isset( $_POST['woosb_before_text'] ) ) {
				update_post_meta( $post_id, 'woosb_before_text', sanitize_post_field( 'post_content', $_POST['woosb_before_text'], $post_id, 'display' ) );
			}

			if ( isset( $_POST['woosb_after_text'] ) ) {
				update_post_meta( $post_id, 'woosb_after_text', sanitize_post_field( 'post_content', $_POST['woosb_after_text'], $post_id, 'display' ) );
			}
		}

		function product_price_class( $class ) {
			global $product;

			if ( $product && is_a( $product, 'WC_Product_Woosb' ) ) {
				$class .= ' woosb-price-' . $product->get_id();
			}

			return $class;
		}

		function add_to_cart_form() {
			global $product;

			if ( ! $product || ! is_a( $product, 'WC_Product_Woosb' ) ) {
				return;
			}

			if ( $product->has_variables() ) {
				wp_enqueue_script( 'wc-add-to-cart-variation' );
			}

			do_action( 'woosb_before_add_to_cart_form', $product );

			if ( ( WPCleverWoosb_Helper()->get_setting( 'bundled_position', 'above' ) === 'above' ) && apply_filters( 'woosb_show_bundled', true, $product->get_id() ) ) {
				self::show_bundled();
			}

			wc_get_template( 'single-product/add-to-cart/simple.php' );

			if ( ( WPCleverWoosb_Helper()->get_setting( 'bundled_position', 'above' ) === 'below' ) && apply_filters( 'woosb_show_bundled', true, $product->get_id() ) ) {
				self::show_bundled();
			}

			do_action( 'woosb_after_add_to_cart_form', $product );
		}

		function add_to_cart_button() {
			global $product;

			if ( $product && is_a( $product, 'WC_Product_Woosb' ) && ( $ids = $product->get_ids_str() ) ) {
				echo '<input name="woosb_ids" class="woosb-ids woosb-ids-' . esc_attr( $product->get_id() ) . '" type="hidden" value="' . esc_attr( $ids ) . '"/>';
			}
		}

		function loop_add_to_cart_link( $link, $product ) {
			if ( $product->is_type( 'woosb' ) && ( $product->has_variables() || $product->has_optional() ) ) {
				$link = str_replace( 'ajax_add_to_cart', '', $link );
			}

			return $link;
		}

		function cart_shipping_packages( $packages ) {
			if ( ! empty( $packages ) ) {
				foreach ( $packages as $package_key => $package ) {
					if ( ! empty( $package['contents'] ) ) {
						foreach ( $package['contents'] as $cart_item_key => $cart_item ) {
							if ( ! empty( $cart_item['woosb_parent_id'] ) && ( get_post_meta( $cart_item['woosb_parent_id'], 'woosb_shipping_fee', true ) === 'whole' ) ) {
								unset( $packages[ $package_key ]['contents'][ $cart_item_key ] );
							}

							if ( ! empty( $cart_item['woosb_ids'] ) && ( get_post_meta( $cart_item['data']->get_id(), 'woosb_shipping_fee', true ) === 'each' ) ) {
								unset( $packages[ $package_key ]['contents'][ $cart_item_key ] );
							}
						}
					}
				}
			}

			return $packages;
		}

		function cart_contents_weight( $weight ) {
			$weight = 0;

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( isset( $cart_item['data'] ) && is_a( $cart_item['data'], 'WC_Product' ) && $cart_item['data']->has_weight() ) {
					if ( ( ! empty( $cart_item['woosb_parent_id'] ) && ( get_post_meta( $cart_item['woosb_parent_id'], 'woosb_shipping_fee', true ) === 'whole' ) ) || ( ! empty( $cart_item['woosb_ids'] ) && ( get_post_meta( $cart_item['data']->get_id(), 'woosb_shipping_fee', true ) === 'each' ) ) ) {
						$weight += 0;
					} else {
						$weight += (float) $cart_item['data']->get_weight() * $cart_item['quantity'];
					}
				}
			}

			return $weight;
		}

		function get_price_html( $price_html, $product ) {
			if ( $product->is_type( 'woosb' ) && ( $items = $product->get_items() ) ) {
				$product_id            = $product->get_id();
				$exclude_unpurchasable = $product->exclude_unpurchasable();
				$custom_price          = get_post_meta( $product_id, 'woosb_custom_price', true );
				$price_format          = WPCleverWoosb_Helper()->get_setting( 'price_format', 'from_min' );
				$default_custom_price  = WPCleverWoosb_Helper()->get_setting( 'price_format_custom', /* translators: dynamic price */ esc_html__( 'before %s after', 'woo-product-bundle' ) );

				if ( ! empty( $custom_price ) ) {
					return str_replace( '%s', $price_html, $custom_price );
				}

				if ( ( $price_format === 'custom' ) && ! empty( $default_custom_price ) ) {
					return str_replace( '%s', $price_html, $default_custom_price );
				}

				if ( ! $product->is_fixed_price() && ! apply_filters( 'woosb_ignore_get_price_html', false ) ) {
					$discount_amount     = $product->get_discount_amount();
					$discount_percentage = $product->get_discount_percentage();

					if ( $product->has_optional() ) {
						if ( $price_format === 'min_only' || $price_format === 'from_min' ) {
							$items_optional = []; // collect optional items only

							foreach ( $items as $k => $item ) {
								if ( $_product = wc_get_product( $item['id'] ) ) {
									// exclude heading/paragraph
									$_unpurchasable = ! $_product->is_purchasable() || ! $_product->is_in_stock() || ! $_product->has_enough_stock( $item['qty'] );

									if ( $exclude_unpurchasable && ! $_unpurchasable ) {
										$items[ $k ]['price'] = 0;
									} else {
										$items[ $k ]['price'] = WPCleverWoosb_Helper()->get_price_to_display( $_product );
									}

									if ( ! empty( $item['optional'] ) && ! $_unpurchasable ) {
										$items_optional[ $k ] = $items[ $k ];
									}
								}
							}

							// min price
							$total_min = $total_qty = 0;

							foreach ( $items as $item ) {
								if ( isset( $item['price'] ) ) {
									// exclude heading/paragraph
									if ( ! empty( $item['optional'] ) && apply_filters( 'woosb_get_price_from_min_qty', true ) ) {
										$item_min  = ! empty( $item['min'] ) ? (float) $item['min'] : 0;
										$total_min += (float) $item['price'] * $item_min;
										$total_qty += $item_min;
									} else {
										$total_min += (float) $item['price'] * (float) $item['qty'];
										$total_qty += (float) $item['qty'];
									}

									if ( ! $discount_amount && $discount_percentage ) {
										$total_min *= ( 100 - (float) $discount_percentage ) / 100;
										$total_min = WPCleverWoosb_Helper()->round_price( $total_min );
									}
								}
							}

							// min whole
							$min_whole = (float) ( get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ?: 1 );
							$min_price = (float) ( ! empty( $items_optional ) ? min( array_column( $items_optional, 'price' ) ) : 0 );

							if ( ! $discount_amount && $discount_percentage ) {
								$min_price *= ( 100 - (float) $discount_percentage ) / 100;
								$min_price = WPCleverWoosb_Helper()->round_price( $min_price );
							}

							if ( $total_qty > 0 ) {
								// has min each
								if ( $min_whole > $total_qty ) {
									$total_min += ( $min_whole - $total_qty ) * $min_price;
								}
							} else {
								$total_min += $min_whole * $min_price;
							}

							// discount
							if ( $discount_amount ) {
								$total_min -= (float) $discount_amount;
							}

							switch ( $price_format ) {
								case 'min_only':
									$price_html = apply_filters( 'woosb_get_price_html_min_only', wc_price( $total_min ) . $product->get_price_suffix(), $total_min, $product );
									break;
								case 'from_min':
									$price_html = apply_filters( 'woosb_get_price_html_from_min', '<span>' . esc_html__( 'From', 'woo-product-bundle' ) . '</span> ' . wc_price( $total_min ) . $product->get_price_suffix(), $total_min, $product );
									break;
							}
						}
					} elseif ( $product->has_variables() ) {
						if ( $price_format === 'min_only' || $price_format === 'min_max' || $price_format === 'from_min' ) {
							$min_price = $max_price = 0;

							foreach ( $items as $item ) {
								if ( $_product = wc_get_product( $item['id'] ) ) {
									if ( $exclude_unpurchasable && ( ! $_product->is_purchasable() || ! $_product->is_in_stock() || ! $_product->has_enough_stock( $item['qty'] ) ) ) {
										continue;
									}

									$min_price += WPCleverWoosb_Helper()->get_price_to_display( $_product, $item['qty'] );
									$max_price += WPCleverWoosb_Helper()->get_price_to_display( $_product, $item['qty'], 'max' );
								}
							}

							if ( $discount_amount ) {
								$min_price -= (float) $discount_amount;
								$max_price -= (float) $discount_amount;
							} elseif ( $discount_percentage ) {
								$min_price *= (float) ( 100 - $discount_percentage ) / 100;
								$max_price *= (float) ( 100 - $discount_percentage ) / 100;
							}

							switch ( $price_format ) {
								case 'min_only':
									$price_html = apply_filters( 'woosb_get_price_html_min_only', wc_price( $min_price ) . $product->get_price_suffix(), $min_price, $product );
									break;
								case 'min_max':
									$price_html = apply_filters( 'woosb_get_price_html_min_max', wc_price( $min_price ) . ' - ' . wc_price( $max_price ) . $product->get_price_suffix(), $min_price, $max_price, $product );
									break;
								case 'from_min':
									$price_html = apply_filters( 'woosb_get_price_html_from_min', '<span>' . esc_html__( 'From', 'woo-product-bundle' ) . '</span> ' . wc_price( $min_price ) . $product->get_price_suffix(), $min_price, $product );
									break;
							}
						}
					} else {
						// auto calculated price
						$price_regular = $price_sale = $price = 0;

						foreach ( $items as $item ) {
							if ( $_product = wc_get_product( $item['id'] ) ) {
								if ( $exclude_unpurchasable && ( ! $_product->is_purchasable() || ! $_product->is_in_stock() || ! $_product->has_enough_stock( $item['qty'] ) ) ) {
									continue;
								}

								$_price = WPCleverWoosb_Helper()->get_price( $_product );

								if ( $discount_percentage ) {
									// when haven't discount_amount, apply the discount percentage
									$_price *= ( 100 - (float) $discount_percentage ) / 100;
									$_price = WPCleverWoosb_Helper()->round_price( $_price );
								}

								$_price        = apply_filters( 'woosb_item_price_add_to_cart', $_price, $_product );
								$price         += WPCleverWoosb_Helper()->get_price_to_display( $_product, [
									'price' => $_price,
									'qty'   => $item['qty']
								] );
								$price_sale    += WPCleverWoosb_Helper()->get_price_to_display( $_product, [ 'qty' => $item['qty'] ] );
								$price_regular += wc_get_price_to_display( $_product, [
									'qty'   => $item['qty'],
									'price' => $_product->get_regular_price()
								] );
							}
						}

						if ( $discount_amount ) {
							$price = $price_sale - $discount_amount;
						}

						do_action( 'woosb_after_calculate_prices', $product, $price_regular, $price_sale, $price );

						if ( $discount_amount || $discount_percentage ) {
							$price_html = wc_format_sale_price( wc_price( $price_sale ), wc_price( $price ) ) . $product->get_price_suffix();
						} else {
							if ( $price < $price_regular ) {
								$price_html = wc_format_sale_price( wc_price( $price_regular ), wc_price( $price ) ) . $product->get_price_suffix();
							} else {
								$price_html = wc_price( $price ) . $product->get_price_suffix();
							}
						}
					}
				}
			}

			return apply_filters( 'woosb_get_price_html', $price_html, $product );
		}

		function order_again_cart_item_data( $data, $item ) {
			if ( $ids = $item->get_meta( '_woosb_ids' ) ) {
				$data['woosb_order_again'] = 'yes';
				$data['woosb_ids']         = $ids;
			}

			if ( $parent_id = $item->get_meta( '_woosb_parent_id' ) ) {
				$data['woosb_order_again'] = 'yes';
				$data['woosb_parent_id']   = $parent_id;
			}

			return $data;
		}

		function cart_loaded_from_session( $cart ) {
			foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
				// remove orphaned products
				if ( isset( $cart_item['woosb_parent_key'] ) && ( $parent_key = $cart_item['woosb_parent_key'] ) && ! isset( $cart->cart_contents[ $parent_key ] ) ) {
					$cart->remove_cart_item( $cart_item_key );
				}

				// if order again, remove bundled products first
				if ( isset( $cart_item['woosb_order_again'], $cart_item['woosb_parent_id'] ) ) {
					$cart->remove_cart_item( $cart_item_key );
				}
			}

			foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
				// if order again, add bundled products again
				if ( isset( $cart_item['woosb_order_again'], $cart_item['woosb_ids'] ) ) {
					unset( $cart->cart_contents[ $cart_item_key ]['woosb_order_again'] );
					unset( $cart->cart_contents[ $cart_item_key ]['woosb_keys'] );
					$cart_item['data']->build_items( $cart_item['woosb_ids'] );
					$add_items = true;
					$items     = $cart_item['data']->get_items();

					foreach ( $items as $item ) {
						$_product = wc_get_product( $item['id'] );

						if ( ! $_product || in_array( $_product->get_type(), self::$types, true ) || ! $_product->is_in_stock() || ! $_product->is_purchasable() || empty( $item['qty'] ) || ! $_product->has_enough_stock( $item['qty'] * $cart_item['quantity'] ) ) {
							$add_items = false;
							break;
						}
					}

					if ( $add_items ) {
						self::add_to_cart_items( $items, $cart_item_key, $cart_item['product_id'], $cart_item['quantity'] );
					} else {
						unset( $cart->cart_contents[ $cart_item_key ] );
					}
				}
			}
		}

		function coupon_is_valid_for_product( $valid, $product, $coupon, $cart_item ) {
			if ( ( WPCleverWoosb_Helper()->get_setting( 'coupon_restrictions', 'no' ) === 'both' ) && ( isset( $cart_item['woosb_parent_id'] ) || isset( $cart_item['woosb_ids'] ) ) ) {
				// exclude both bundles and bundled products
				return false;
			}

			if ( ( WPCleverWoosb_Helper()->get_setting( 'coupon_restrictions', 'no' ) === 'bundles' ) && isset( $cart_item['woosb_ids'] ) ) {
				// exclude bundles
				return false;
			}

			if ( ( WPCleverWoosb_Helper()->get_setting( 'coupon_restrictions', 'no' ) === 'bundled' ) && isset( $cart_item['woosb_parent_id'] ) ) {
				// exclude bundled products
				return false;
			}

			if ( isset( $cart_item['woosb_parent_id'] ) && ( $parent = wc_get_product( $cart_item['woosb_parent_id'] ) ) ) {
				return $coupon->is_valid_for_product( $parent );
			}

			return $valid;
		}

		function update_stock_status( $product_id, $stock_status, $product ) {
			if ( ! $product->is_type( 'woosb' ) ) {
				$bundles = self::get_bundles( $product_id, 500, 0, 'edit' );

				if ( ! empty( $bundles ) ) {
					foreach ( $bundles as $bundle ) {
						$bundle_id      = $bundle->get_id();
						$visibility     = get_the_terms( $bundle_id, 'product_visibility' );
						$visibility_arr = array_values( wp_list_pluck( $visibility, 'name' ) );

						if ( $bundle->is_in_stock() ) {
							$visibility_new = array_diff( $visibility_arr, [ 'outofstock' ] );
						} else {
							$visibility_new = array_merge( $visibility_arr, [ 'outofstock' ] );
						}

						wp_set_post_terms( $bundle_id, $visibility_new, 'product_visibility' );
					}
				}
			}
		}

		function show_bundled( $product = null ) {
			if ( ! $product ) {
				global $product;
			}

			if ( ! $product || ! is_a( $product, 'WC_Product_Woosb' ) ) {
				return;
			}

			if ( $items = $product->get_items() ) {
				$order                 = 1;
				$product_id            = $product->get_id();
				$fixed_price           = $product->is_fixed_price();
				$has_variables         = $product->has_variables();
				$has_optional          = $product->has_optional();
				$discount_amount       = $product->get_discount_amount();
				$discount_percentage   = $product->get_discount_percentage();
				$exclude_unpurchasable = $product->exclude_unpurchasable();
				$total_limit           = get_post_meta( $product_id, 'woosb_total_limits', true ) === 'on';
				$total_min             = get_post_meta( $product_id, 'woosb_total_limits_min', true );
				$total_max             = get_post_meta( $product_id, 'woosb_total_limits_max', true );
				$whole_min             = get_post_meta( $product_id, 'woosb_limit_whole_min', true ) ?: 1;
				$whole_max             = get_post_meta( $product_id, 'woosb_limit_whole_max', true ) ?: '-1';
				$layout                = get_post_meta( $product_id, 'woosb_layout', true ) ?: 'unset';
				$layout                = $layout !== 'unset' ? $layout : WPCleverWoosb_Helper()->get_setting( 'layout', 'list' );
				$bundled_price         = WPCleverWoosb_Helper()->get_setting( 'bundled_price', 'price' );
				$products_class        = apply_filters( 'woosb_products_class', 'woosb-products woosb-products-layout-' . $layout, $product );
				$products_attrs        = apply_filters( 'woosb_products_attrs', [
					'discount-amount'       => $discount_amount,
					'discount'              => $discount_percentage,
					'fixed-price'           => $fixed_price ? 'yes' : 'no',
					'price'                 => wc_get_price_to_display( $product ),
					'price-suffix'          => htmlentities( $product->get_price_suffix() ),
					'variables'             => $has_variables ? 'yes' : 'no',
					'optional'              => $has_optional ? 'yes' : 'no',
					'min'                   => $whole_min,
					'max'                   => $whole_max,
					'total-min'             => $total_limit && $total_min ? $total_min : 0,
					'total-max'             => $total_limit && $total_max ? $total_max : '-1',
					'exclude-unpurchasable' => $exclude_unpurchasable ? 'yes' : 'no',
				], $product );

				do_action( 'woosb_before_wrap', $product );

				echo '<div class="woosb-wrap woosb-bundled" data-id="' . esc_attr( $product_id ) . '">';

				if ( $before_text = apply_filters( 'woosb_before_text', get_post_meta( $product_id, 'woosb_before_text', true ), $product_id ) ) {
					echo '<div class="woosb-before-text woosb-text">' . wp_kses_post( do_shortcode( $before_text ) ) . '</div>';
				}

				do_action( 'woosb_before_table', $product );

				echo '<div class="' . esc_attr( $products_class ) . '" ' . WPCleverWoosb_Helper()->data_attributes( $products_attrs ) . '>';
				// store global $product
				$global_product    = $product;
				$global_product_id = $product_id;

				foreach ( $items as $key => $item ) {
					if ( ! empty( $item['id'] ) ) {
						// exclude heading/paragraph
						$product  = wc_get_product( $item['id'] );
						$item_qty = (float) ( $item['qty'] ?? 1 );
						$item_min = ! empty( $item['min'] ) ? (float) $item['min'] : 0;
						$item_max = ! empty( $item['max'] ) ? (float) $item['max'] : 10000;
						$optional = ! empty( $item['optional'] );

						if ( ! $product || in_array( $product->get_type(), self::$types, true ) ) {
							continue;
						}

						if ( ! apply_filters( 'woosb_item_exclude', true, $product, $global_product ) ) {
							continue;
						}

						if ( $optional ) {
							if ( ( $max_purchase = $product->get_max_purchase_quantity() ) && ( $max_purchase > 0 ) && ( $max_purchase < $item_max ) ) {
								// get_max_purchase_quantity can return -1
								$item_max = $max_purchase;
							}

							if ( $item_qty < $item_min ) {
								$item_qty = $item_min;
							}

							if ( ( $item_max > $item_min ) && ( $item_qty > $item_max ) ) {
								$item_qty = $item_max;
							}
						}

						$item_class = 'woosb-item-product woosb-product woosb-product-type-' . $product->get_type();

						if ( $optional ) {
							$item_class .= ' woosb-product-optional';
						}

						if ( ! apply_filters( 'woosb_item_visible', true, $product, $global_product_id ) ) {
							$item_class .= ' woosb-product-hidden';
						}

						if ( ( ! $product->is_type( 'variable' ) && ( ! $product->is_in_stock() || ! $product->has_enough_stock( $item_qty ) || ! $product->is_purchasable() ) ) || ( $product->is_type( 'variable' ) && ! $product->child_is_in_stock() && ! $product->child_is_on_backorder() ) ) {
							if ( ! apply_filters( 'woosb_allow_unpurchasable_qty', false ) ) {
								$item_qty = 0;
							}

							$item_class .= ' woosb-product-unpurchasable';
						}

						$item_attrs = apply_filters( 'woosb_item_attrs', [
							'key'          => $key,
							'name'         => $product->get_name(),
							'id'           => $product->is_type( 'variable' ) ? 0 : $item['id'],
							'price'        => WPCleverWoosb_Helper()->get_price_to_display( $product ),
							'o_price'      => WPCleverWoosb_Helper()->get_price_to_display( $product ),
							'price-suffix' => htmlentities( $product->get_price_suffix() ),
							'stock'        => $product->get_max_purchase_quantity(),
							'qty'          => $item_qty,
							'order'        => $order,
						], $product, $global_product, $order );

						do_action( 'woosb_above_item', $product, $global_product, $order );
						echo '<div class="' . esc_attr( apply_filters( 'woosb_item_class', $item_class, $product, $global_product, $order ) ) . '" ' . WPCleverWoosb_Helper()->data_attributes( $item_attrs ) . '>';
						do_action( 'woosb_before_item', $product, $global_product, $order );

						if ( WPCleverWoosb_Helper()->get_setting( 'bundled_thumb', 'yes' ) !== 'no' ) { ?>
                            <div class="woosb-thumb">
								<?php if ( $product->is_visible() && ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
									echo '<a ' . ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-link no-ajaxy" data-id="' . esc_attr( $item['id'] ) . '" data-context="woosb"' : '' ) . ' href="' . esc_url( $product->get_permalink() ) . '" ' . ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
								} ?>
                                <div class="woosb-thumb-ori">
									<?php echo apply_filters( 'woosb_item_thumbnail', $product->get_image( self::$image_size ), $product ); ?>
                                </div>
                                <div class="woosb-thumb-new"></div>
								<?php if ( $product->is_visible() && ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
									echo '</a>';
								} ?>
                            </div>
						<?php } ?>

                        <div class="woosb-title">
							<?php
							do_action( 'woosb_before_item_name', $product );

							echo '<div class="woosb-name">';

							if ( ( WPCleverWoosb_Helper()->get_setting( 'bundled_qty', 'yes' ) === 'yes' ) && ! $optional ) {
								echo apply_filters( 'woosb_item_qty', $item['qty'] . ' × ', $item['qty'], $product );
							}

							$item_name    = '';
							$product_name = apply_filters( 'woosb_item_product_name', $product->get_name(), $product );

							if ( $product->is_visible() && ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
								$item_name .= '<a ' . ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-link no-ajaxy" data-id="' . $item['id'] . '" data-context="woosb"' : '' ) . ' href="' . esc_url( $product->get_permalink() ) . '" ' . ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>';
							}

							if ( $product->is_type( 'variable' ) || ( $product->is_in_stock() && $product->has_enough_stock( $item_qty ) ) ) {
								$item_name .= $product_name;
							} else {
								$item_name .= '<s>' . $product_name . '</s>';
							}

							if ( $product->is_visible() && ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) !== 'no' ) ) {
								$item_name .= '</a>';
							}

							echo apply_filters( 'woosb_item_name', $item_name, $product, $global_product, $order );
							echo '</div>';

							do_action( 'woosb_after_item_name', $product );

							if ( $bundled_price === 'price_under_name' || $bundled_price === 'subtotal_under_name' ) {
								self::show_bundled_price( $bundled_price, $fixed_price, $discount_percentage, $product, $item );
							}

							if ( WPCleverWoosb_Helper()->get_setting( 'bundled_description', 'no' ) === 'yes' ) {
								echo '<div class="woosb-description">' . apply_filters( 'woosb_item_description', $product->is_type( 'variation' ) ? $product->get_description() : $product->get_short_description(), $product ) . '</div>';
							}

							echo '<div class="woosb-availability">' . wp_kses_post( wc_get_stock_html( $product ) ) . '</div>';
							?>
                        </div>

						<?php if ( $optional ) {
							if ( ( $product->is_in_stock() && ( $product->is_type( 'variable' ) || $product->is_purchasable() ) ) || apply_filters( 'woosb_allow_unpurchasable_qty', false ) ) {
								echo '<div class="' . esc_attr( WPCleverWoosb_Helper()->get_setting( 'plus_minus', 'no' ) === 'yes' ? 'woosb-quantity woosb-quantity-plus-minus' : 'woosb-quantity' ) . '">';

								if ( WPCleverWoosb_Helper()->get_setting( 'plus_minus', 'no' ) === 'yes' ) {
									echo '<div class="woosb-quantity-input">';
									echo '<div class="woosb-quantity-input-minus">-</div>';
								}

								$qty_args = [
									'input_value' => $item_qty,
									'min_value'   => $item_min,
									'max_value'   => $item_max,
									'woosb_qty'   => [
										'input_value' => $item_qty,
										'min_value'   => $item_min,
										'max_value'   => $item_max
									],
									'classes'     => apply_filters( 'woosb_qty_classes', [
										'input-text',
										'woosb-qty',
										'woosb_qty',
										'qty',
										'text'
									] ),
									'input_name'  => 'woosb_qty_' . $order
									// compatible with WPC Product Quantity
								];

								if ( apply_filters( 'woosb_use_woocommerce_quantity_input', true ) ) {
									woocommerce_quantity_input( $qty_args, $product );
								} else {
									echo apply_filters( 'woosb_quantity_input', '<input type="number" class="input-text woosb-qty woosb_qty qty text" value="' . esc_attr( $item_qty ) . '" min="' . esc_attr( $item_min ) . '" max="' . esc_attr( $item_max ) . '" name="' . esc_attr( 'woosb_qty_' . $order ) . '" />', $qty_args, $product );
								}

								if ( WPCleverWoosb_Helper()->get_setting( 'plus_minus', 'no' ) === 'yes' ) {
									echo '<div class="woosb-quantity-input-plus">+</div>';
									echo '</div>';
								}

								echo '</div>';
							} else { ?>
                                <div class="woosb-quantity woosb-quantity-disabled">
                                    <div class="quantity">
                                        <label>
                                            <input type="number" class="input-text woosb-qty woosb_qty qty text"
                                                   value="0" disabled/>
                                        </label>
                                    </div>
                                </div>
							<?php }
						}

						if ( $bundled_price === 'price' || $bundled_price === 'subtotal' ) {
							self::show_bundled_price( $bundled_price, $fixed_price, $discount_percentage, $product, $item );
						}

						do_action( 'woosb_after_item', $product, $global_product, $order );
						echo '</div><!-- /woosb-product -->';
						do_action( 'woosb_under_item', $product, $global_product, $order );
					} elseif ( ! empty( $item['text'] ) ) {
						$item_class = 'woosb-item-text';

						if ( ! empty( $item['type'] ) ) {
							$item_class .= ' woosb-item-text-type-' . $item['type'];
						}

						echo '<div class="' . esc_attr( apply_filters( 'woosb_item_text_class', $item_class, $item, $global_product, $order ) ) . '">';

						if ( empty( $item['type'] ) || ( $item['type'] === 'none' ) ) {
							echo wp_kses_post( $item['text'] );
						} else {
							echo '<' . esc_attr( $item['type'] ) . '>' . wp_kses_post( $item['text'] ) . '</' . esc_attr( $item['type'] ) . '>';
						}

						echo '</div>';
					}

					$order ++;
				}

				// restore global $product
				$product = $global_product;
				echo '</div><!-- /woosb-products -->';

				if ( ! $fixed_price && ( $has_variables || $has_optional ) ) {
					echo '<div class="woosb-summary woosb-text"><span class="woosb-total"></span>';

					if ( $has_optional ) {
						echo '<span class="woosb-count"></span>';
					}

					echo '</div>';
				}

				echo '<div class="woosb-alert woosb-text" style="display: none"></div>';

				do_action( 'woosb_after_table', $product );

				if ( $after_text = apply_filters( 'woosb_after_text', get_post_meta( $product_id, 'woosb_after_text', true ), $product_id ) ) {
					echo '<div class="woosb-after-text woosb-text">' . wp_kses_post( do_shortcode( $after_text ) ) . '</div>';
				}

				echo '</div><!-- /woosb-wrap -->';

				do_action( 'woosb_after_wrap', $product );
			}
		}

		function show_bundled_price( $bundled_price, $fixed_price, $discount_percentage, $product, $item ) {
			?>
            <div class="woosb-price">
				<?php do_action( 'woosb_before_item_price', $product ); ?>
                <div class="woosb-price-ori">
					<?php
					$ori_price = (float) WPCleverWoosb_Helper()->round_price( $product->get_price() );
					$get_price = (float) WPCleverWoosb_Helper()->get_price( $product );

					if ( ! $fixed_price && $discount_percentage ) {
						$new_price     = true;
						$product_price = $get_price * ( 100 - (float) $discount_percentage ) / 100;
						$product_price = WPCleverWoosb_Helper()->round_price( $product_price );
						$product_price = apply_filters( 'woosb_item_price_add_to_cart', $product_price, $item );
					} else {
						$new_price     = false;
						$product_price = $get_price;
					}

					switch ( $bundled_price ) {
						case 'price':
						case 'price_under_name':
							if ( $new_price ) {
								$item_price = wc_format_sale_price( wc_get_price_to_display( $product, [ 'price' => $get_price ] ), wc_get_price_to_display( $product, [ 'price' => $product_price ] ) );
							} else {
								if ( $get_price > $ori_price ) {
									$item_price = wc_price( WPCleverWoosb_Helper()->get_price_to_display( $product ) ) . $product->get_price_suffix();
								} else {
									$item_price = $product->get_price_html();
								}
							}

							break;
						case 'subtotal':
						case 'subtotal_under_name':
							if ( $new_price ) {
								$item_price = wc_format_sale_price( wc_get_price_to_display( $product, [
										'price' => $get_price,
										'qty'   => $item['qty']
									] ), wc_get_price_to_display( $product, [
										'price' => $product_price,
										'qty'   => $item['qty']
									] ) ) . $product->get_price_suffix();
							} else {
								$item_price = wc_price( WPCleverWoosb_Helper()->get_price_to_display( $product, $item['qty'] ) ) . $product->get_price_suffix();
							}

							break;
						default:
							$item_price = $product->get_price_html();
					}

					echo apply_filters( 'woosb_item_price', $item_price, $product );
					?>
                </div>
                <div class="woosb-price-new"></div>
				<?php do_action( 'woosb_after_item_price', $product ); ?>
            </div>
			<?php
		}

		function show_bundles( $product = null ) {
			if ( ! $product ) {
				global $product;
			}

			if ( ! $product || $product->is_type( 'woosb' ) ) {
				return;
			}

			$product_id = $product->get_id();
			$bundles    = self::get_bundles( $product_id ) ?: [];

			if ( $product->is_type( 'variable' ) && apply_filters( 'woosb_show_bundles_from_variation', false ) ) {
				$children = $product->get_children();

				if ( is_array( $children ) && count( $children ) > 0 ) {
					foreach ( $children as $child ) {
						if ( $child_bundles = self::get_bundles( $child ) ) {
							foreach ( $child_bundles as $child_bundle ) {
								$bundles[] = $child_bundle;
							}
						}
					}
				}
			}

			if ( ! empty( $bundles ) ) {
				echo '<div class="woosb-bundles">';

				do_action( 'woosb_before_bundles', $product );

				echo '<div class="woosb-products">';

				foreach ( array_unique( $bundles ) as $bundle ) {
					echo '<div class="woosb-product">';
					do_action( 'woosb_before_bundles_item', $bundle, $product );
					echo '<div class="woosb-thumb">' . wp_kses_post( $bundle->get_image( self::$image_size ) ) . '</div>';
					echo '<div class="woosb-title"><a ' . ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) === 'yes_popup' ? 'class="woosq-link no-ajaxy" data-id="' . esc_attr( $bundle->get_id() ) . '" data-context="woosb"' : '' ) . ' href="' . esc_url( $bundle->get_permalink() ) . '" ' . ( WPCleverWoosb_Helper()->get_setting( 'bundled_link', 'yes' ) === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . esc_html( $bundle->get_name() ) . '</a></div>';
					echo '<div class="woosb-price">' . wp_kses_post( $bundle->get_price_html() ) . '</div>';
					do_action( 'woosb_after_bundles_item', $bundle, $product );
					echo '</div><!-- /woosb-product -->';
				}

				echo '</div><!-- /woosb-products -->';
				wp_reset_postdata();

				do_action( 'woosb_after_bundles', $product );

				echo '</div><!-- /woosb-bundles -->';
			}
		}

		function get_bundled( $ids, $product = null ) {
			if ( empty( $ids ) ) {
				return apply_filters( 'woosb_get_bundled', [], $product );
			}

			$bundled    = [];
			$product_id = 0;

			// Use null coalescing operator for cleaner product_id assignment
			if ( is_a( $product, 'WC_Product' ) ) {
				$product_id = $product->get_id();
			} elseif ( is_numeric( $product ) ) {
				$product_id = $product;
			}

			// Cache post meta values to reduce database calls
			$meta_cache = [
				'optional'    => get_post_meta( $product_id, 'woosb_optional_products', true ) === 'on',
				'min'         => get_post_meta( $product_id, 'woosb_limit_each_min', true ),
				'min_default' => get_post_meta( $product_id, 'woosb_limit_each_min_default', true ) === 'on',
				'max'         => get_post_meta( $product_id, 'woosb_limit_each_max', true )
			];

			$helper  = WPCleverWoosb_Helper();
			$use_sku = apply_filters( 'woosb_use_sku', false );

			if ( is_array( $ids ) ) {
				// Process array format
				foreach ( $ids as $key => $item ) {
					$item = array_merge( [
						'id'    => 0,
						'sku'   => '',
						'qty'   => 0,
						'attrs' => []
					], $item );

					if ( $use_sku && ! empty( $item['sku'] ) ) {
						$new_id = $helper->get_product_id_from_sku( $item['sku'] );
						if ( $new_id ) {
							$item['id'] = $new_id;
						}
					}

					if ( ! isset( $item['min'] ) ) {
						if ( $meta_cache['optional'] ) {
							$item['optional'] = '1';
						}

						$item['min'] = $meta_cache['min'];
						if ( $meta_cache['min_default'] ) {
							$item['min'] = (float) $item['qty'];
						}

						$item['max'] = $meta_cache['max'];
					}

					$bundled[ is_numeric( $key ) ? $helper->generate_key() : $key ] = $item;
				}
			} else {
				// Process string format
				$items = array_filter( explode( ',', $ids ) );

				foreach ( $items as $item ) {
					$data = explode( '/', $item );
					$id   = rawurldecode( $data[0] ?? 0 );

					// Determine key and quantity
					if ( isset( $data[1] ) ) {
						if ( is_numeric( $data[1] ) && ! isset( $data[2] ) ) {
							$key = $helper->generate_key();
							$qty = (float) $data[1];
						} else {
							$key = $data[1];
							$qty = (float) ( $data[2] ?? 1 );
						}
					} else {
						$key = $helper->generate_key();
						$qty = 1;
					}

					// Handle SKU or ID
					if ( ! is_numeric( $id ) ) {
						$sku = $id;
						$id  = wc_get_product_id_by_sku( ltrim( $id, 'sku-' ) );
					} else {
						$item_product = wc_get_product( $id );
						$sku          = $item_product ? $item_product->get_sku() : '';
					}

					if ( $id ) {
						$bundled[ $key ] = [
							'id'  => $id,
							'sku' => $sku,
							'qty' => $qty
						];
					}
				}
			}

			return apply_filters( 'woosb_get_bundled', $bundled, $product );
		}

		function get_bundles( $product_id, $per_page = 500, $offset = 0, $context = 'view' ) {
			$bundles = [];
			$sku     = get_post_meta( $product_id, '_sku', true );

			// Prepare search patterns once
			$search_patterns = [];

			// Add product ID patterns
			$id_str            = $product_id . '/';
			$search_patterns[] = [
				'key'     => 'woosb_ids',
				'value'   => '"' . $product_id . '"',
				'compare' => 'LIKE'
			];
			$search_patterns[] = [
				'key'     => 'woosb_ids',
				'value'   => ',' . $id_str,
				'compare' => 'LIKE'
			];
			$search_patterns[] = [
				'key'     => 'woosb_ids',
				'value'   => '^' . $id_str,
				'compare' => 'REGEXP'
			];

			// Add SKU patterns if valid
			if ( ! empty( $sku ) && ! is_numeric( $sku ) ) {
				$sku_str = $sku . '/';
			} else {
				$sku     = 'woosb';
				$sku_str = 'woosb/';
			}

			$search_patterns[] = [
				'key'     => 'woosb_ids',
				'value'   => '"' . $sku . '"',
				'compare' => 'LIKE'
			];
			$search_patterns[] = [
				'key'     => 'woosb_ids',
				'value'   => ',' . $sku_str,
				'compare' => 'LIKE'
			];
			$search_patterns[] = [
				'key'     => 'woosb_ids',
				'value'   => '^' . $sku_str,
				'compare' => 'REGEXP'
			];

			// Build query args
			$query_args = [
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => $per_page,
				'offset'                 => $offset,
				'no_found_rows'          => true, // Skip counting total rows when pagination is not needed
				'update_post_meta_cache' => false, // Skip loading post meta data we don't need
				'update_post_term_cache' => false, // Skip loading term data we don't need
				'tax_query'              => [
					[
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => [ 'woosb' ],
						'operator' => 'IN',
					]
				],
				'meta_query'             => array_merge(
					[ 'relation' => 'OR' ],
					$search_patterns
				)
			];

			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$_product = wc_get_product( get_the_ID() );

					if ( ! $_product || ( ( $context !== 'edit' ) &&
					                      ! apply_filters( 'woosb_bundles_visible', $_product->is_visible(), $_product ) ) ) {
						continue;
					}

					$bundles[] = $_product;
				}

				wp_reset_postdata(); // Use wp_reset_postdata() instead of wp_reset_query()
			}

			return apply_filters( 'woosb_get_bundles', $bundles, $product_id );
		}

		function shortcode_form() {
			ob_start();
			self::add_to_cart_form();

			return ob_get_clean();
		}

		function shortcode_bundled() {
			ob_start();
			self::show_bundled();

			return ob_get_clean();
		}

		function shortcode_bundles() {
			ob_start();
			self::show_bundles();

			return ob_get_clean();
		}

		function display_post_states( $states, $post ) {
			if ( 'product' == get_post_type( $post->ID ) ) {
				if ( ( $product = wc_get_product( $post->ID ) ) && $product->is_type( 'woosb' ) ) {
					$count = 0;

					if ( $ids_str = $product->get_ids_str() ) {
						$ids_arr = explode( ',', $ids_str );
						$count   = count( $ids_arr );
					}

					$states[] = apply_filters( 'woosb_post_states', '<span class="woosb-state">' . /* translators: bundle name */ sprintf( esc_html__( 'Bundle (%s)', 'woo-product-bundle' ), $count ) . '</span>', $count, $product );
				}
			}

			return $states;
		}

		function bulk_actions() {
			if ( current_user_can( 'edit_products' ) ) {
				add_filter( 'bulk_actions-edit-product', [ $this, 'bulk_actions_register' ] );
				add_filter( 'handle_bulk_actions-edit-product', [ $this, 'bulk_actions_handler' ], 10, 3 );
				add_action( 'admin_notices', [ $this, 'bulk_actions_notice' ] );
			}
		}

		function bulk_actions_register( $bulk_actions ) {
			$bulk_actions['woosb_create_bundle'] = esc_html__( 'Create a Smart bundle', 'woo-product-bundle' );

			return $bulk_actions;
		}

		function bulk_actions_handler( $redirect_to, $do_action, $post_ids ) {
			if ( $do_action !== 'woosb_create_bundle' ) {
				return $redirect_to;
			}

			$ids = implode( '.', $post_ids );

			return add_query_arg( 'woosb_ids', $ids, admin_url( 'post-new.php?post_type=product' ) );
		}

		function bulk_actions_notice() {
			if ( ! empty( $_REQUEST['woosb_ids'] ) ) {
				$ids = explode( '.', $_REQUEST['woosb_ids'] );
				echo '<div id="message" class="updated fade">' . /* translators: count */ sprintf( esc_html__( 'Added %s product(s) to this bundle.', 'woo-product-bundle' ), count( $ids ) ) . '</div>';
			}
		}

		function no_stock_notification( $product ) {
			if ( 'no' === get_option( 'woocommerce_notify_no_stock', 'yes' ) ) {
				return;
			}

			$message    = '';
			$subject    = sprintf( '[%s] %s', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), esc_html__( 'Bundle(s) out of stock', 'woo-product-bundle' ) );
			$product_id = $product->get_id();

			if ( $bundles = self::get_bundles( $product_id ) ) {
				foreach ( $bundles as $bundle ) {
					$message .= sprintf( /* translators: product name */ esc_html__( '%s is out of stock.', 'woo-product-bundle' ), html_entity_decode( esc_html( $bundle->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . ' <a href="' . get_edit_post_link( $bundle->get_id() ) . '" target="_blank">#' . $bundle->get_id() . '</a><br/>';
				}

				$message .= sprintf( /* translators: product name */ esc_html__( '%s is out of stock.', 'woo-product-bundle' ), html_entity_decode( esc_html( $product->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . ' <a href="' . get_edit_post_link( $product_id ) . '" target="_blank">#' . $product_id . '</a>';

				wp_mail(
					apply_filters( 'woocommerce_email_recipient_no_stock', get_option( 'woocommerce_stock_email_recipient' ), $product, null ),
					apply_filters( 'woocommerce_email_subject_no_stock', $subject, $product, null ),
					apply_filters( 'woocommerce_email_content_no_stock', $message, $product ),
					apply_filters( 'woocommerce_email_headers', 'Content-Type: text/html; charset=UTF-8', 'no_stock', $product, null ),
					apply_filters( 'woocommerce_email_attachments', [], 'no_stock', $product, null )
				);
			}
		}

		function low_stock_notification( $product ) {
			if ( 'no' === get_option( 'woocommerce_notify_low_stock', 'yes' ) ) {
				return;
			}

			$message = '';
			$subject = sprintf( '[%s] %s', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), esc_html__( 'Bundle(s) low in stock', 'woo-product-bundle' ) );

			$product_id = $product->get_id();
			if ( $bundles = self::get_bundles( $product_id ) ) {
				foreach ( $bundles as $bundle ) {
					$message .= sprintf( /* translators: bundle name */ esc_html__( '%s is low in stock.', 'woo-product-bundle' ), html_entity_decode( esc_html( $bundle->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) . ' <a href="' . get_edit_post_link( $bundle->get_id() ) . '" target="_blank">#' . $bundle->get_id() . '</a><br/>';
				}

				$message .= sprintf( /* translators: product name */ esc_html__( '%1$s is low in stock. There are %2$d left.', 'woo-product-bundle' ), html_entity_decode( esc_html( $product->get_formatted_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ), html_entity_decode( esc_html( $product->get_stock_quantity() ) ) ) . ' <a href="' . get_edit_post_link( $product_id ) . '" target="_blank">#' . $product_id . '</a>';

				wp_mail(
					apply_filters( 'woocommerce_email_recipient_low_stock', get_option( 'woocommerce_stock_email_recipient' ), $product, null ),
					apply_filters( 'woocommerce_email_subject_low_stock', $subject, $product, null ),
					apply_filters( 'woocommerce_email_content_low_stock', $message, $product ),
					apply_filters( 'woocommerce_email_headers', 'Content-Type: text/html; charset=UTF-8', 'low_stock', $product, null ),
					apply_filters( 'woocommerce_email_attachments', [], 'low_stock', $product, null )
				);
			}
		}

		function woovr_default_selector( $selector, $product, $variation, $context ) {
			if ( isset( $context ) && ( $context === 'woosb' ) ) {
				if ( ( $selector_interface = WPCleverWoosb_Helper()->get_setting( 'selector_interface', 'unset' ) ) && ( $selector_interface !== 'unset' ) ) {
					$selector = $selector_interface;
				}
			}

			return $selector;
		}

		function wpcsm_locations( $locations ) {
			$locations['WPC Product Bundles'] = [
				'woosb_before_wrap'       => esc_html__( 'Before bundled products', 'woo-product-bundle' ),
				'woosb_after_wrap'        => esc_html__( 'After bundled products', 'woo-product-bundle' ),
				'woosb_before_table'      => esc_html__( 'Before bundled products table', 'woo-product-bundle' ),
				'woosb_after_table'       => esc_html__( 'After bundled products table', 'woo-product-bundle' ),
				'woosb_before_item'       => esc_html__( 'Before bundled product', 'woo-product-bundle' ),
				'woosb_after_item'        => esc_html__( 'After bundled product', 'woo-product-bundle' ),
				'woosb_before_item_name'  => esc_html__( 'Before bundled product name', 'woo-product-bundle' ),
				'woosb_after_item_name'   => esc_html__( 'After bundled product name', 'woo-product-bundle' ),
				'woosb_before_item_price' => esc_html__( 'Before bundled product price', 'woo-product-bundle' ),
				'woosb_after_item_price'  => esc_html__( 'After bundled product price', 'woo-product-bundle' ),
				'woosb_before_bundles'    => esc_html__( 'Before bundles', 'woo-product-bundle' ),
				'woosb_after_bundles'     => esc_html__( 'After bundles', 'woo-product-bundle' ),
			];

			return $locations;
		}

		function export_process( $value, $meta, $product ) {
			if ( $meta->key === 'woosb_ids' ) {
				$ids = get_post_meta( $product->get_id(), 'woosb_ids', true );

				if ( ! empty( $ids ) && is_array( $ids ) ) {
					return json_encode( $ids );
				}
			}

			return $value;
		}

		function import_process( $object, $data ) {
			if ( isset( $data['meta_data'] ) ) {
				foreach ( $data['meta_data'] as $meta ) {
					if ( $meta['key'] === 'woosb_ids' ) {
						$object->update_meta_data( 'woosb_ids', json_decode( $meta['value'], true ) );
						break;
					}
				}
			}

			return $object;
		}

		function wpml_item_id( $id ) {
			return apply_filters( 'wpml_object_id', $id, 'product', true );
		}
	}

	function WPCleverWoosb() {
		return WPCleverWoosb::instance();
	}
}
