<?php
define( 'DISABLE_JETPACK_WAF', false );
if ( defined( 'DISABLE_JETPACK_WAF' ) && DISABLE_JETPACK_WAF ) return;
define( 'JETPACK_WAF_MODE', 'normal' );
define( 'JETPACK_WAF_SHARE_DATA', true );
define( 'JETPACK_WAF_SHARE_DEBUG_DATA', false );
define( 'JETPACK_WAF_DIR', '/home/modium/domains/ucieczkowe.pl/public_html/wp-content/jetpack-waf' );
define( 'JETPACK_WAF_WPCONFIG', '/home/modium/domains/ucieczkowe.pl/public_html/wp-content/../wp-config.php' );
require_once '/home/modium/domains/ucieczkowe.pl/public_html/wp-content/plugins/jetpack-protect/vendor/autoload.php';
Automattic\Jetpack\Waf\Waf_Runner::initialize();
