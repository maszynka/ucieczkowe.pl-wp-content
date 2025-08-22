<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverWoosb_Helper' ) ) {
	class WPCleverWoosb_Helper {
		protected static $instance = null;
		protected static $settings = [];
		protected static $localization = [];

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			// settings
			self::$settings = (array) get_option( 'woosb_settings', [] );
			// localization
			self::$localization = (array) get_option( 'woosb_localization', [] );
		}

		public static function round_price( $price ) {
			static $decimals = null;

			// Cast price to float once
			$price         = (float) $price;
			$rounded_price = $price;

			// Cache the filter results to avoid multiple calls
			$should_round = ! apply_filters( 'woosb_ignore_round_price', false );

			if ( apply_filters( 'woosb_round_price', $should_round ) ) {
				// Cache decimals value using static variable
				if ( $decimals === null ) {
					$decimals = (int) apply_filters( 'woosb_price_decimals', wc_get_price_decimals() );
				}

				$rounded_price = round( $price, $decimals );
			}

			return apply_filters( 'woosb_rounded_price', $rounded_price, $price );
		}

		public static function get_price( $product, $min_or_max = 'min', $for_display = false ) {
			if ( self::get_setting( 'bundled_price_from', 'sale_price' ) === 'regular_price' ) {
				if ( $product->is_type( 'variable' ) ) {
					if ( $min_or_max === 'max' ) {
						$price = $product->get_variation_regular_price( 'max', $for_display );
					} else {
						$price = $product->get_variation_regular_price( 'min', $for_display );
					}
				} else {
					$price = $product->get_regular_price();
				}
			} else {
				if ( $product->is_type( 'variable' ) ) {
					if ( $min_or_max === 'max' ) {
						$price = $product->get_variation_price( 'max', $for_display );
					} else {
						$price = $product->get_variation_price( 'min', $for_display );
					}
				} else {
					$price = $product->get_price();
				}
			}

			return apply_filters( 'woosb_get_price', (float) $price, $product, $min_or_max );
		}

		public static function get_price_to_display( $product, $qty = 1, $min_or_max = 'min' ) {
			if ( is_array( $qty ) ) {
				$qty = array_merge( [ 'price' => self::get_price( $product, $min_or_max ), 'qty' => 1 ], $qty );

				return apply_filters( 'woosb_get_price_to_display', (float) wc_get_price_to_display( $product, [
					'price' => $qty['price'],
					'qty'   => $qty['qty']
				] ), $product, $qty, $min_or_max );
			} else {
				return apply_filters( 'woosb_get_price_to_display', (float) wc_get_price_to_display( $product, [
					'price' => self::get_price( $product, $min_or_max ),
					'qty'   => $qty
				] ), $product, $qty, $min_or_max );
			}
		}

		public static function set_price( $product, $price = 0 ) {
			$product->set_regular_price( $price );
			$product->set_sale_price( $price );
			$product->set_price( $price );
		}

		public static function is_in_stock( $product ) {
			if ( $product->is_type( 'variable' ) ) {
				return $product->child_is_in_stock() || $product->child_is_on_backorder();
			} else {
				return $product->is_in_stock();
			}
		}


		public static function has_enough_stock( $product, $qty ) {
			if ( ! is_a( $product, 'WC_Product' ) ) {
				return false;
			}

			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations( 'objects' );

				foreach ( $variations as $variation ) {
					if ( $variation->has_enough_stock( $qty ) ) {
						return true;
					}
				}

				return false;
			} else {
				return $product->has_enough_stock( $qty );
			}
		}

		public static function get_stock_quantity( $product ) {
			if ( ! is_a( $product, 'WC_Product' ) ) {
				return null;
			}

			if ( $product->is_type( 'variable' ) ) {
				$stock_quantity = null;
				$variations     = $product->get_available_variations( 'objects' );

				foreach ( $variations as $variation ) {
					if ( ( $variation->get_stock_quantity() !== null ) && ( $variation->get_stock_quantity() > (float) $stock_quantity ) ) {
						$stock_quantity = $variation->get_stock_quantity();
					}
				}

				return $stock_quantity;
			} else {
				return $product->get_stock_quantity();
			}
		}

		public static function sanitize_array( $arr ) {
			foreach ( (array) $arr as $k => $v ) {
				if ( is_array( $v ) ) {
					$arr[ $k ] = self::sanitize_array( $v );
				} else {
					$arr[ $k ] = sanitize_post_field( 'post_content', $v, 0, 'db' );
				}
			}

			return $arr;
		}

		public static function get_product_id_from_sku( $sku, $old_id = 0 ) {
			if ( $old_id && ( $parent_id = wp_get_post_parent_id( $old_id ) ) && ( $parent = wc_get_product( $parent_id ) ) ) {
				$parent_sku = $parent->get_sku();
			} else {
				$parent_sku = '';
			}

			if ( ! empty( $sku ) && ( $sku !== $parent_sku ) && ( $new_id = wc_get_product_id_by_sku( $sku ) ) ) {
				return $new_id;
			}

			return 0;
		}

		public static function clean_ids( $ids ) {
			return apply_filters( 'woosb_clean_ids', $ids );
		}

		public static function clean( $var ) {
			if ( is_array( $var ) ) {
				return array_map( [ __CLASS__, 'clean' ], $var );
			} else {
				return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
			}
		}

		public static function minify_items( $items ) {
			$minify_items = [];

			foreach ( $items as $item ) {
				if ( ! empty( $item['id'] ) ) {
					if ( empty( $minify_items ) ) {
						$minify_items[] = $item;
					} else {
						$has_item = false;

						foreach ( $minify_items as $key => $minify_item ) {
							if ( ( $minify_item['id'] === $item['id'] ) && ( $minify_item['attrs'] === $item['attrs'] ) ) {
								$minify_items[ $key ]['qty'] += $item['qty'];
								$has_item                    = true;
								break;
							}
						}

						if ( ! $has_item ) {
							$minify_items[] = $item;
						}
					}
				}
			}

			return apply_filters( 'woosb_minify_items', $minify_items, $items );
		}

		public static function get_settings() {
			return apply_filters( 'woosb_get_settings', self::$settings );
		}

		public static function get_setting( $name, $default = false ) {
			if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
				$setting = self::$settings[ $name ];
			} else {
				$setting = get_option( 'woosb_' . $name, $default );
			}

			return apply_filters( 'woosb_get_setting', $setting, $name, $default );
		}

		public static function localization( $key = '', $default = '' ) {
			$str = '';

			if ( ! empty( $key ) && ! empty( self::$localization[ $key ] ) ) {
				$str = self::$localization[ $key ];
			} elseif ( ! empty( $default ) ) {
				$str = $default;
			}

			return apply_filters( 'woosb_localization_' . $key, $str );
		}

		public static function generate_key() {
			$key         = '';
			$key_str     = apply_filters( 'woosb_key_characters', 'abcdefghijklmnopqrstuvwxyz0123456789' );
			$key_str_len = strlen( $key_str );

			for ( $i = 0; $i < apply_filters( 'woosb_key_length', 4 ); $i ++ ) {
				$key .= $key_str[ random_int( 0, $key_str_len - 1 ) ];
			}

			if ( is_numeric( $key ) ) {
				$key = self::generate_key();
			}

			return apply_filters( 'woosb_generate_key', $key );
		}

		public static function data_attributes( $attrs ) {
			$attrs_arr = [];

			foreach ( $attrs as $key => $attr ) {
				$attrs_arr[] = 'data-' . sanitize_title( str_replace( 'data-', '', $key ) ) . '="' . esc_attr( $attr ) . '"';
			}

			return implode( ' ', $attrs_arr );
		}
	}

	function WPCleverWoosb_Helper() {
		return WPCleverWoosb_Helper::instance();
	}
}