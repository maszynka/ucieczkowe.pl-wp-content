<?php
/**
 * Compatibility functions
 *
 * @package Password Protected
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'password_protected_cookie' ) ) {
	function password_protected_cookie( $case, $args = array() ) {
		$type = get_option( 'password_protected_use_transient', 'default' );
		$k    = false;
		switch ( $case ) {
			case 'set':
				switch ( $type ) {
					case 'something-else':
						
						do_action(
							'password_protected_setting_set_cookie',
							$args['name'],
							$args['data'],
							$args['secure'],
							$args['expire']
						);
						$k = true;
						break;
					case 'transient':
						
						pp_set_transient( $args['name'], $args['data'], $args['expire'] );
						$k = true;
						break;
					case 'default':
					default:
						setcookie( $args['name'], $args['data'], $args['expire'], COOKIEPATH, COOKIE_DOMAIN, $args['secure'], true );
						if ( COOKIEPATH != SITECOOKIEPATH ) {
							setcookie( $args['name'], $args['data'], $args['expire'], SITECOOKIEPATH, COOKIE_DOMAIN, $args['secure'], true );
						}
						$k = true;
						break;
				}
				break;
			case 'delete':
				switch ( $type ) {
					case 'something-else':
						do_action( 'password_protected_setting_delete_cookie', $args['name'] );
						$k = true;
						break;
					
					case 'transient':
						pp_delete_transient( $args['name'] );
						$k = true;
						break;
					
					case 'default':
					default:
						setcookie( $args['name'], ' ', current_time( 'timestamp' ) - 31536000, COOKIEPATH, COOKIE_DOMAIN );
						setcookie( $args['name'], ' ', current_time( 'timestamp' ) - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN );
						$k = true;
						break;
				}
				break;
			case 'get':
			default:
				switch ( $type ) {
					case 'transient':
						$k = pp_get_transient( $args['name'] );
						break;
					case 'something-else':
						$k = apply_filters( 'password_protected_setting_get_cookie', null, $args['name'] );
						break;
					case 'default':
					default:
						if ( isset( $_COOKIE[ $args['name'] ] ) && ! empty( $_COOKIE[ $args['name'] ] ) ) {
							$k = $_COOKIE[ $args['name'] ];
						}
						break;
				}
				break;
		}
		
		return $k;
	}
}
