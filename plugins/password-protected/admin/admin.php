<?php

class Password_Protected_Admin {

	var $settings_page_id;
	var $options_group = 'password-protected';
	var $setting_tabs = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wp_version;
		add_action( 'admin_init', array( $this, 'password_protected_register_setting_tabs' ) );
		add_action( 'admin_init', array( $this, 'password_protected_settings' ), 15 );
		add_action( 'admin_init', array( $this, 'add_privacy_policy' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'password_protected_subtab_password-protected-page-description_content', array( $this, 'password_protected_page_description_tab' ) );
		add_action( 'password_protected_help_tabs', array( $this, 'help_tabs' ), 5 );
		add_action( 'admin_notices', array( $this, 'password_protected_admin_notices' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
		add_filter( 'plugin_action_links_password-protected/password-protected.php', array( $this, 'plugin_action_links' ) );
		add_filter( 'pre_update_option_password_protected_password', array( $this, 'pre_update_option_password_protected_password' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'init', array( $this, 'init' ) );

		add_action( 'password_protected_subtab_cache-issue_content', array( $this, 'cache_related_issue' ) );
		add_action( 'admin_footer', array( $this, 'add_script_in_footer' ), 9999 );
	}

	public function add_script_in_footer() {
		?>
        <script type="text/javascript">
            jQuery( document ).ready( function( $ ) {
                $( '.toplevel_page_password-protected a' ).each( function( index,element ) {
                    if ( 'admin.php?page=password-protected-get-pro' === $( element ).attr( 'href' ) ) {
                        $( element ).css( { 'background-color': '#8076ff', 'color': '#ffffff', 'padding': '15px auto' } );
                    }
                } );
            } );
        </script>
		<?php
	}

	/**
	 * Password protected setting tabs
	 * customizable using filter hook
	 */
	public function password_protected_register_setting_tabs() {
		$this->setting_tabs = array(
			'general'  => array(
				'title' => __( 'General', 'password-protected' ),
				'slug'  => 'general',
				'icon'  => 'dashicons-migrate',
			),

			'advanced' => array(
				'title'    => __( 'Advanced', 'password-protected' ),
				'slug'     => 'advanced',
				'icon'     => 'dashicons-admin-settings',
				'sub-tabs' => array(
					'exclude-from-protection' => array(
						'title' => __( 'Exclude From Protection', 'password-protected' ),
						'slug'  => 'exclude-from-protection',
					),

					'password-protected-page-description' => array(
						'title' => __( 'Protected Page Content', 'password-protected' ),
						'slug'  => 'password-protected-page-description',
					),

					'bypass-url' => array(
						'title' => __( 'Bypass URL', 'password-protected' ),
						'slug'  => 'bypass-url',
					),

					'cache-issue' => array(
						'title' => __( 'Cache Issue', 'password-protected' ),
						'slug'  => 'cache-issue',
					),
				),
			),

			'manage_passwords' => array(
				'title' => __( 'Multiple Passwords', 'password-protected' ),
				'slug'  => 'manage_passwords',
				'icon'  => 'dashicons-shield',
			),

			'content-protection' => array(
				'title'    => __( 'Content Protection', 'password-protected' ),
				'slug'     => 'content-protection',
				'icon'     => 'dashicons-superhero',
				'sub-tabs' => array(
					'post-type-protection' => array(
						'title' => __( 'Post Type Protection', 'password-protected' ),
						'slug'  => 'post-type-protection',
					),

					'taxonomy-protection' => array(
						'title' => __( 'Taxonomy Protection', 'password-protected' ),
						'slug'  => 'taxonomy-protection',
					),
				),
			),

			'security' => array(
				'title'    => __( 'Security', 'password-protected' ),
				'slug'     => 'security',
				'icon'     => 'dashicons-shield-alt',
				'sub-tabs' => array(
					'whitelist-user-role' => array(
						'title' => __( 'Whitelist User Role', 'password-protected' ),
						'slug'  => 'whitelist-user-role',
					),

					'google-recaptcha' => array(
						'title' => __( 'Google ReCaptcha', 'password-protected' ),
						'slug'  => 'google-recaptcha',
					),

					'wp-admin-protection' => array(
						'title' => __( 'WP-Admin Protection', 'password-protected' ),
						'slug'  => 'wp-admin-protection',
					),

					'attempt-limitation' => array(
						'title' => __( 'Attempt Limitation', 'password-protected' ),
						'slug'  => 'attempt-limitation',
					),
				),
			),

			'logs' => array(
				'title' => __( 'Logs', 'password-protected' ),
				'slug'  => 'logs',
				'icon'  => 'dashicons-media-text',
				'sub-tabs' => array(
					'activity_logs' => array(
						'title' => __( 'Activity Logs', 'password-protected' ),
						'slug'  => 'activity_logs',
					),

					'activity-report' => array(
						'title' => __( 'Activity Report', 'password-protected' ),
						'slug'  => 'activity-report',
					),
				),
			),

			'protected-screen' => array(
				'title'    => __( 'Customization', 'password-protected' ),
				'slug'     => 'protected-screen',
				'icon'     => 'dashicons-admin-customizer',
				'sub-tabs' => array(
					'logo-styles'        => array(
						'title' => __( 'Logo', 'password-protected' ),
						'slug'  => 'logo-styles',
					),
					'label-styles'       => array(
						'title' => __( 'Labels', 'password-protected' ),
						'slug'  => 'label-styles',
					),
					'field-styles'       => array(
						'title' => __( 'Fields', 'password-protected' ),
						'slug'  => 'field-styles',
					),
					'button-styles'      => array(
						'title' => __( 'Button', 'password-protected' ),
						'slug'  => 'button-styles',
					),
					'remember-me-styles' => array(
						'title' => __( 'Remember Me', 'password-protected' ),
						'slug'  => 'remember-me-styles',
					),
					'form-background'    => array(
						'title' => __( 'Form Background', 'password-protected' ),
						'slug'  => 'form-background',
					),
					'body-background'    => array(
						'title' => __( 'Body Background', 'password-protected' ),
						'slug'  => 'body-background',
					),
					'below-form'         => array(
						'title' => __( 'Form Content', 'password-protected' ),
						'slug'  => 'below-form',
					),
					'below-page'         => array(
						'title' => __( 'Page Content', 'password-protected' ),
						'slug'  => 'below-page',
					),
					'custom-css'         => array(
						'title' => __( 'Custom CSS', 'password-protected' ),
						'slug'  => 'custom-css',
					),
				),
			),

            'request-password' => array(
                'title'    => __( 'Password Request', 'password-protected' ),
                'slug'     => 'request-password',
                'icon'     => 'dashicons-email-alt',
                'sub-tabs' => array(
                    'password-request' => array(
                        'title' => __( 'Request Password', 'password-protected' ),
                        'slug'  => 'password-request',
                    ),
                    'requests'         => array(
                        'title' => __( 'Requests', 'password-protected' ),
                        'slug'  => 'requests',
                    ),
                    'email-templates'  => array(
                        'title' => __( 'Email Templates', 'password-protected' ),
                        'slug'  => 'email-templates',
                    ),
                ),
            ),
		);

		$this->setting_tabs = apply_filters( 'password_protected_setting_tabs', $this->setting_tabs );

		$this->setting_tabs['help']   = array(
			'title' => __( 'Help', 'password-protected' ),
			'slug'  => 'help',
			'icon'  => 'dashicons-editor-help',
		);
		$this->setting_tabs['getpro'] = array(
			'title' => __( 'Get Pro', 'password-protected' ),
			'slug'  => 'getpro',
			'icon'  => 'dashicons-superhero-alt',
		);

		if ( class_exists( 'Password_Protected_Pro' ) ) {
			unset( $this->setting_tabs['getpro'] );
		}
	}

	/**
	 * Admin enqueue scripts.
	 *
	 * @param string $hooks Page Hook.
	 */
	public function admin_enqueue_scripts( $hooks ) {

		if ( 'settings_page_password-protected' === $hooks || 'toplevel_page_password-protected' === $hooks ) {
			global $Password_Protected;
			wp_enqueue_style( 'password-protected-page-script', PASSWORD_PROTECTED_URL . 'assets/css/admin.css', array(), $Password_Protected->version );
			wp_enqueue_script( 'password-protected-admin-script', PASSWORD_PROTECTED_URL . 'assets/js/admin.js', array('jquery'), $Password_Protected->version );
			wp_localize_script(
				'password-protected-admin-script',
				'passwordProtectedAdminObject',
				array(
					'imageURL'       => PASSWORD_PROTECTED_URL . 'assets/images/',
					'description'    => __( 'Unlock unmatched website protection with<br>advanced security features', 'password-protected' ),
					'buttonText'     => __( 'Get Password Protected Pro', 'password-protected' ),
					'buttonRedirect' => add_query_arg(
						array(
							'page' => 'password-protected',
							'tab'  => 'getpro',
						),
						admin_url( 'admin.php' )
					),
				)
			);
		}
	}

	public function init() {

		if ( ! class_exists( 'Password_Protected_Pro' ) ) {
			
			add_action( 'password_protected_subtab_exclude-from-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'text_before_after_login_form', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_attempt-limitation_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_bypass-url_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_tab_manage_passwords_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_post-type-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_taxonomy-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_whitelist-user-role_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_wp-admin-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_activity_logs_content', array( $this, 'dummy_content' ) );

			add_action( 'password_protected_subtab_logo-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_label-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_field-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_button-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_remember-me-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_form-background_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_body-background_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_below-form_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_below-page_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_custom-css_content', array( $this, 'dummy_content' ) );
            add_action( 'password_protected_subtab_password-request_content', array( $this, 'dummy_content' ) );
            add_action( 'password_protected_subtab_requests_content', array( $this, 'dummy_content' ) );
            add_action( 'password_protected_subtab_email-templates_content', array( $this, 'dummy_content' ) );
		}

		if ( isset( $_GET['page'] ) && 'password-protected-get-pro' === $_GET['page'] ) {
			wp_redirect( 'https://passwordprotectedwp.com/pricing/?utm_source=Plugin&utm_medium=Submenu' );
			exit;
		}
	}

	/**
	 * Add Privacy Policy
	 */
	public function add_privacy_policy() {

		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return 1;
		}

		$content = _x( 'The Password Protected plugin stores a cookie on successful password login containing a hashed version of the entered password. It does not store any information about the user. The cookie stored is named <code>bid_n_password_protected_auth</code> where <code>n</code> is the blog ID in a multisite network', 'privacy policy content', 'password-protected' );

		wp_add_privacy_policy_content( __( 'Password Protected Plugin', 'password-protected' ), wp_kses_post( wpautop( $content, false ) ) );

	}

	/**
	 * Admin Menu
	 */
	public function admin_menu() {

		$capability             = apply_filters( 'password_protected_options_page_capability', 'manage_options' );
		$this->settings_page_id = add_options_page(
			__( 'Password Protected', 'password-protected' ),
			__( 'Password Protected', 'password-protected' ),
			$capability,
			'password-protected',
			array(
				$this,
				'settings_page'
			)
		);
		add_menu_page(
			'Password Protected',
			'Password Protected',
			'manage_options',
			'password-protected',
			array( $this, 'pp_admin_menu_page_callback' ),
			'dashicons-lock',
			99
		);
		add_action( 'load-' . $this->settings_page_id, array( $this, 'add_help_tabs' ), 20 );


		if ( ! class_exists( 'Password_Protected_Pro' ) ) {
			add_submenu_page(
				'password-protected',
				__( 'Get Pro Now', 'password-protected' ),
				__( '⭐ Get Pro Now', 'password-protected' ),
				'manage_options',
				'password-protected-get-pro',
				array( $this, 'password_protected_get_pro_features' )
			);
		}
	}

	/**
	 * Settings Page
	 */
	public function settings_page() {
		?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>
            <h2><?php _e( 'Password Protected Settings', 'password-protected' ) ?></h2>
            <form method="post" action="options.php">
				<?php
				settings_fields( 'password-protected' );
				do_settings_sections( 'password-protected' );
				?>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes' ) ?>"></p>
            </form>
			<?php
			// do_settings_sections( 'password-protected-login-designer' );
			?>

            <div id="help-notice">
				<?php do_settings_sections( 'password-protected-compat' ); ?>
            </div>
        </div>

		<?php
	}

	/** @since 2.6
	 * Admin Menu Settings Page
	 */
	public function pp_admin_menu_page_callback() {
		$tab    = ( isset( $_GET['tab'] ) && sanitize_text_field( $_GET['page'] ) == 'password-protected' ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		$subtab = ( isset( $_GET['sub-tab'] ) && sanitize_text_field( $_GET['page'] ) == 'password-protected' ) ? sanitize_text_field( $_GET['sub-tab'] ) : '';

		// for backward compatibility.
		$this->setting_tabs = array_filter(
			$this->setting_tabs,
			function( $tab ) {
				return isset( $tab['title'] ) && isset( $tab['slug'] ) && isset( $tab['icon'] );
			}
		);
		if ( isset( $this->setting_tabs[ $tab ]['sub-tabs'] ) && ! empty( $this->setting_tabs[ $tab ]['sub-tabs'] ) ) {
			$this->setting_tabs[ $tab ]['sub-tabs'] = array_filter(
				$this->setting_tabs[ $tab ]['sub-tabs'],
				function ( $subtab ) {
					return isset( $subtab['title'] ) && isset( $subtab['slug'] );
				}
			);
		}
		?>
        <div class="wrap">
            <div class="wrap-row" style="display: <?php echo class_exists( 'Password_Protected_Pro' ) ? 'block' : 'flex'; ?>;">
                <?php $attributes = class_exists( 'Password_Protected_Pro' ) ? '' : 'class="wrap-col-70"'; ?>
                <div <?php echo $attributes; ?>>
					<?php settings_errors(); ?>

                    <div class="pp-wrapper">

                        <div class="pp-nav-wrapper">
							<?php foreach( $this->setting_tabs as $index => $setting_tab ) : ?>
                                <div class="pp-nav-tab <?php echo ( $tab === $setting_tab['slug'] ) ? 'pp-nav-tab-active' : ''; ?> <?php echo ( 'getpro' === $setting_tab['slug'] ) ? 'pp-pro-tab' : ''; ?>">
                                    <a href="<?php echo admin_url( 'admin.php?page=password-protected&tab=' . $setting_tab['slug'] ); ?>" class=" ">
										<?php if ( filter_var( $setting_tab['icon'], FILTER_VALIDATE_URL ) ) : ?>
                                            <span>
                                                <img src="<?php echo esc_url( $setting_tab['icon'] ); ?>" alt="">
                                            </span>
										<?php else : ?>
                                            <span class="dashicons <?php echo $setting_tab['icon']; ?>"></span>
										<?php endif; ?>
										<?php echo $setting_tab['title']; ?>
                                    </a>
                                </div>
							<?php endforeach; ?>
                        </div>

                        <div class="pp-content-wrapper">
							<?php if ( isset( $this->setting_tabs[ $tab ] ) && isset( $this->setting_tabs[ $tab ]['sub-tabs'] ) && ! empty( $this->setting_tabs[ $tab ]['sub-tabs'] ) ) : ?>
                                <div class="pp-sub-tabs-wrapper">
                                    <div class="pp-subtabs-links">
										<?php if ( empty( $subtab ) ) { ?>
											<?php
											$subtab = array_keys( $this->setting_tabs[ $tab ]['sub-tabs'] );
											$subtab = $subtab[0];
											?>
										<?php } ?>
										<?php foreach ( $this->setting_tabs[ $tab ]['sub-tabs'] as $sub_tab ) : ?>
                                            <a class="<?php echo $subtab === $sub_tab['slug'] ? 'active' : '' ?>" href="<?php echo admin_url( 'admin.php?page=password-protected&tab=' . $tab . '&sub-tab=' . $sub_tab['slug'] ); ?>"><?php echo $sub_tab['title']; ?></a>
										<?php endforeach; ?>
                                    </div>
                                </div>
							<?php endif; ?>

                            <div class="pp-settings-wrapper">
								<?php $this->password_protected_render_tab_content( $tab, $subtab ); ?>
                            </div>
                        </div>
                    </div>
                </div>

	            <?php $attributes = class_exists( 'Password_Protected_Pro' ) ? 'style="display:none;"' : 'id="pp-sidebar" class="wrap-col-25"'; ?>
                <div <?php echo $attributes; ?>>
					<?php
					$_tab = '';
					if ( isset( $_GET['tab'] ) ) {
						$_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
					}
					if ( 'getpro' !== $_tab ) :
						do_settings_sections( 'password-protected-try-pro' );
						// do_settings_sections( 'password-protected-login-designer' );
						do_action('password_protected_sidebar');
					endif;
					?>
                </div>
            </div>
        </div>
		<?php
	}

	public function password_protected_page_description_tab() {
		echo '<form action="options.php" method="post" enctype="multipart/form-data">';
		settings_fields( 'password-protected-advanced-protected-page-content' );
		do_settings_sections( 'password-protected&tab=advanced&sub-tab=password-protected-page-description' );
		do_action('text_before_after_login_form', $_GET);
		submit_button();
		echo '</form>';
	}

	/**
	 * password protected render settings page in menu
	 */
	public function password_protected_render_tab_content( $tab, $sub_tab ) {
		
		switch ( $tab ) {
			case 'general':
				do_settings_sections( 'password-protected-help' );
				echo '<form method="post" action="options.php">';
				settings_fields( 'password-protected' );
				do_settings_sections( 'password-protected' );
				submit_button();
				echo '</form>';
				break;

			case 'help':
				?>
                <div id="help-notice">
					<?php do_settings_sections( 'password-protected-compat' ); ?>
                </div>
				<?php
				break;

			case 'getpro':
				$this->password_protected_get_pro_features();
				break;

			case $tab:
			
				if ( ! empty( $sub_tab ) ) {
					do_action(
						'password_protected_subtab_' . $sub_tab . '_content',
						$this->setting_tabs[ $tab ]['sub-tabs'][ $sub_tab ]
					);
				} else {
					do_action(
						'password_protected_tab_' . $tab . '_content',
						$this->setting_tabs[ $tab ]
					);
				}
				break;
		}
	}

	/**
	 * Add Help Tabs
	 */
	public function add_help_tabs() {

		global $wp_version;

		if ( version_compare( $wp_version, '3.3', '<' ) ) {
			return 1;
		}

		do_action( 'password_protected_help_tabs', get_current_screen() );

	}

	/**
	 * Help Tabs
	 *
	 * @param  object  $current_screen  Screen object.
	 */
	public function help_tabs( $current_screen ) {

		$current_screen->add_help_tab( array(
			'id'      => 'PASSWORD_PROTECTED_SETTINGS',
			'title'   => __( 'Password Protected', 'password-protected' ),
			'content' => __( '<p><strong>Password Protected Status</strong><br />Turn on/off password protection.</p>', 'password-protected' )
			             . __( '<p><strong>Protected Permissions</strong><br />Allow access for logged in users and administrators without needing to enter a password. You will need to enable this option if you want administrators to be able to preview the site in the Theme Customizer. Also allow RSS Feeds to be accessed when the site is password protected.</p>', 'password-protected' )
			             . __( '<p><strong>Password Fields</strong><br />To set a new password, enter it into both fields. You cannot set an `empty` password. To disable password protection uncheck the Enabled checkbox.</p>', 'password-protected' )
		) );

	}

	/**
	 * Settings API
	 */
	public function password_protected_settings() {
		// general tab
		add_settings_section(
			'password_protected',
			__( 'Password Protected Configuration', 'password-protected' ),
			array( $this, 'password_protected_settings_section' ),
			$this->options_group
		);

		add_settings_field(
			'password_protected_status',
			__( 'Password Protected Status', 'password-protected' ),
			array( $this, 'password_protected_status_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_permissions',
			__( 'Protected Permissions', 'password-protected' ),
			array( $this, 'password_protected_permissions_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_password',
			__( 'New Password', 'password-protected' ),
			array( $this, 'password_protected_password_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_allowed_ip_addresses',
			__( 'Allow IP Addresses', 'password-protected' ),
			array( $this, 'password_protected_allowed_ip_addresses_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_remember_me',
			__( 'Allow Remember me', 'password-protected' ),
			array( $this, 'password_protected_remember_me_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_remember_me_lifetime',
			__( 'Remember for this many days', 'password-protected' ),
			array( $this, 'password_protected_remember_me_lifetime_field' ),
			$this->options_group,
			'password_protected'
		);

		// password protected advanced tab
		add_settings_section(
			'password-protected-advanced-tab',
			'Password Protected Page description',
			array( $this, 'password_protected_page_description' ),
			'password-protected&tab=advanced&sub-tab=password-protected-page-description'
		);

		add_settings_field(
			'text-above-password',
			__( 'Text Above Password Field', 'password-protected' ),
			array( $this, 'password_protected_text_above_password' ),
			'password-protected&tab=advanced&sub-tab=password-protected-page-description',
			'password-protected-advanced-tab'
		);

		add_settings_field(
			'text-below-password',
			__( 'Text Below Password Field ', 'password-protected' ),
			array( $this, 'password_protected_text_below_password' ),
			'password-protected&tab=advanced&sub-tab=password-protected-page-description',
			'password-protected-advanced-tab'
		);

		add_settings_section(
			'password-protected-advanced-tab-cache-issue',
			'Cache Issue',
			'__return_null',
			'password-protected&tab=advanced&sub-tab=cache-issue'
		);

		add_settings_field(
			'password-protected-advance-cache',
			__( 'Advance Cache Fix', 'password-protected' ),
			array( $this, 'password_protected_use_transient' ),
			'password-protected&tab=advanced&sub-tab=cache-issue',
			'password-protected-advanced-tab-cache-issue',
			array(
				'label_for' => 'password-protected-use-transient',
			)
		);

		// password protected help tab
		add_settings_section(
			'password-protected-help',
			'',
			array( $this, 'password_protected_help_tab' ),
			'password-protected-help'
		);

		if( !$this->password_protected_pro_is_installed_and_activated() ) {
			add_settings_section(
				'password-protected-try-pro',
				'',
				array( $this, 'password_protected_try_pro' ),
				'password-protected-try-pro'
			);
		}

		if ( ! $this->login_designer_is_installed_and_activated() ) {
			/* add_settings_section(
				'password-protected-login-designer',
				'',
				array( $this, 'password_protected_login_designer' ),
				'password-protected-login-designer'
			); */
		}

		// registering settings
		register_setting( $this->options_group, 'password_protected_status', 'intval' );
		register_setting( $this->options_group, 'password_protected_feeds', 'intval' );
		register_setting( $this->options_group, 'password_protected_rest', 'intval' );
		register_setting( $this->options_group, 'password_protected_administrators', 'intval' );
		register_setting( $this->options_group, 'password_protected_users', 'intval' );
		register_setting( $this->options_group, 'password_protected_password', array( $this, 'sanitize_password_protected_password' ) );
		register_setting( $this->options_group, 'password_protected_allowed_ip_addresses', array( $this, 'sanitize_ip_addresses' ) );
		register_setting( $this->options_group, 'password_protected_remember_me', 'boolval' );
		register_setting( $this->options_group, 'password_protected_remember_me_lifetime', 'intval' );

		register_setting( 'password-protected-advanced-protected-page-content', 'password_protected_text_above_password', array( 'type' => 'string' ) );
		register_setting( 'password-protected-advanced-protected-page-content', 'password_protected_text_below_password', array( 'type' => 'string' ) );

		register_setting( 'password_protected_cache_issue', 'password_protected_use_transient' );
	}

	/**
	 * Sanitize Password Field Input
	 *
	 * @param   string  $val  Password.
	 * @return  string        Sanitized password.
	 */
	public function sanitize_password_protected_password( $val ) {

		$old_val = get_option( 'password_protected_password' );

		if ( is_array( $val ) ) {
			if ( empty( $val['new'] ) ) {
				return $old_val;
			} elseif ( empty( $val['confirm'] ) ) {
				add_settings_error( 'password_protected_password', 'password_protected_password', __( 'New password not saved. When setting a new password please enter it in both fields.', 'password-protected' ) );
				return $old_val;
			} elseif ( $val['new'] != $val['confirm'] ) {
				add_settings_error( 'password_protected_password', 'password_protected_password', __( 'New password not saved. Password fields did not match.', 'password-protected' ) );
				return $old_val;
			} elseif ( $val['new'] == $val['confirm'] ) {
				add_settings_error( 'password_protected_password', 'password_protected_password', __( 'New password saved.', 'password-protected' ), 'updated' );
				return $val['new'];
			}
			return get_option( 'password_protected_password' );
		}


		return $val;

	}

	/**
	 * Sanitize IP Addresses
	 *
	 * @param   string  $val  IP addresses.
	 * @return  string        Sanitized IP addresses.
	 */
	public function sanitize_ip_addresses( $val ) {
		$un_sanitized_value = $val;

		$ip_addresses = explode( "\n", $val );
		$ip_addresses = array_map( 'sanitize_text_field', $ip_addresses );
		$ip_addresses = array_map( 'trim', $ip_addresses );
		$ip_addresses = array_map( array( $this, 'validate_ip_address' ), $ip_addresses );
		$ip_addresses = array_filter( $ip_addresses );

		$val = implode( "\n", $ip_addresses );

		return apply_filters( 'password_protected__sanitize_ip_addresses', $val, $un_sanitized_value );

	}

	/**
	 * Validate IP Address
	 *
	 * @param   string  $ip_address  IP Address.
	 * @return  string               Validated IP Address.
	 */
	private function validate_ip_address( $ip_address ) {

		return filter_var( $ip_address, FILTER_VALIDATE_IP );

	}

	/**
	 * Password Protected Section
	 */
	public function password_protected_settings_section() {

		return 1;

	}

	/**
	 * Password Protection Status Field
	 */
	public function password_protected_status_field() {

		echo '
            <div class="pp-toggle-wrapper">
                <input type="checkbox" name="password_protected_status" id="password_protected_status" value="1" ' . checked( 1, get_option( 'password_protected_status' ), false ) . ' />
                <label class="pp-toggle" for="password_protected_status">
                    <span class="pp-toggle-slider"></span>
                </label>
            </div>
        <p>
            <label for="password_protected_status">' . __( 'Do you want to enable password protection for whole site?', 'password-protected' ) . '</label>
        </p>
        ';

	}

	/**
	 * Password Protection Permissions Field
	 */
	public function password_protected_permissions_field() {

		echo '<p>
            <label for="password_protected_administrators">
                <input type="checkbox" name="password_protected_administrators" id="password_protected_administrators" value="1" ' . checked( 1, get_option( 'password_protected_administrators' ), false ) . ' />'
		     . __( 'Allow Administrators', 'password-protected' )
		     . '</label>
        </p>
        <p>
            <label for="password_protected_users">
                <input type="checkbox" name="password_protected_users" id="password_protected_users" value="1" ' . checked( 1, get_option( 'password_protected_users' ), false ) . ' />'
		     . __( 'Allow Logged In Users', 'password-protected' )
		     . '</label>
        </p>
        <p>
            <label for="password_protected_feeds">
                <input type="checkbox" name="password_protected_feeds" id="password_protected_feeds" value="1" ' . checked( 1, get_option( 'password_protected_feeds' ), false ) . ' />'
		     . __( 'Allow RSS Feeds', 'password-protected' )
		     . '</label>
        </p>
        <p>
            <label for="password_protected_rest">
                <input type="checkbox" name="password_protected_rest" id="password_protected_rest" value="1" ' . checked( 1, get_option( 'password_protected_rest' ), false ) . ' />'
		     . __( 'Allow REST API', 'password-protected' )
		     . '</label>
        </p>';

	}

	/**
	 * Password Field
	 */
	public function password_protected_password_field() {

		echo '<input type="password" name="password_protected_password[new]" id="password_protected_password_new" size="16" value="" autocomplete="off"> <p><span class="description">' . __( 'If you would like to change the password, type a new one. Otherwise, leave this blank.', 'password-protected' ) . '</span></p><br>
			<input type="password" name="password_protected_password[confirm]" id="password_protected_password_confirm" size="16" value="" autocomplete="off"> <p><span class="description">' . __( 'Type your new password again.', 'password-protected' ) . '</span></p>';

	}

	/**
	 * Allowed IP Addresses Field
	 */
	public function password_protected_allowed_ip_addresses_field() {
		echo '<textarea name="password_protected_allowed_ip_addresses" id="password_protected_allowed_ip_addresses" rows="3" />' . esc_html( get_option( 'password_protected_allowed_ip_addresses' ) ) . '</textarea>';

		echo '<p class="description">' . esc_html__( 'Enter one IP address per line.', 'password-protected' );
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			echo ' ' . esc_html( sprintf( __( 'Your IP address is %s.', 'password-protected' ), $_SERVER['REMOTE_ADDR'] ) );
		}
		echo '</p>';

	}

	/**
	 * Remember Me Field
	 */
	public function password_protected_remember_me_field() {

		echo '<div class="pp-toggle-wrapper">
            <input type="checkbox" name="password_protected_remember_me" id="password_protected_remember_me" value="1" ' . checked( 1, get_option( 'password_protected_remember_me' ), false ) . ' />
            <label class="pp-toggle" for="password_protected_remember_me">
                <span class="pp-toggle-slider"></span>
            </label>
        </div>
        <p>
            <label for="password_protected_remember_me">' . __( 'Allow Remember me', 'password-protected' ) . '</label>
        </p>';

	}

	/**
	 * Remember Me lifetime field
	 */
	public function password_protected_remember_me_lifetime_field() {

		echo '<label><input name="password_protected_remember_me_lifetime" id="password_protected_remember_me_lifetime" min="1" type="number" value="' . get_option( 'password_protected_remember_me_lifetime', 14 ) . '" /></label>';

	}

	/**
	 * Password Protected Page description
	 */
	public function password_protected_page_description() {
		return 1;
	}

	/**
	 * Password Protected text above passsword
	 */
	public function password_protected_text_above_password() {
		echo '<label><textarea id="password_protected_text_above_password" name="password_protected_text_above_password" rows="4" cols="50" class="regular-text">' . esc_attr( get_option('password_protected_text_above_password') ) . '</textarea></label>';
	}

	/**
	 * Password Protected below above passsword
	 */
	public function password_protected_text_below_password() {
		echo '<label><textarea id="password_protected_text_below_password" name="password_protected_text_below_password" rows="4" cols="50" class="regular-text">' . esc_attr( get_option('password_protected_text_below_password') ) . '</textarea></label>';
	}

	public function password_protected_use_transient() {
		$use_transient = get_option( 'password_protected_use_transient', 'default' );

		$cache_issue = array(
			array(
				'name' => 'default',
				'title' => __( 'Use default settings', 'password-protected' ),
			),
			array(
				'name' => 'transient',
				'title' => __( 'You can enable this option if you are having trouble with cookies due to cache or server restrictions.', 'password-protected' ),
                'description' => __( 'Note: It uses transients,  which are saved based on the user\'s IP address, unlike cookies that are tied to the specific browser. This means that once a user logs in using any browser, they can access the page from any other browser as long as they are on the same IP address.', 'password-protected' ),
			),
		);

		foreach ( $cache_issue as $issue ) :
			echo '<p>
                <label>
                    <input type="radio" name="password_protected_use_transient" value="' . esc_attr( $issue['name'] ) . '" ' . checked( $use_transient, $issue['name'], false ) . ' />' . esc_html( $issue['title'] ) . '
                </label>
            </p>';

            if ( isset( $issue['description'] ) ) :
                echo '<p class="desc"><strong>' . esc_attr( $issue['description'] ) . '</strong></p>';
            endif;
		endforeach;
	}

	/**
	 * Help Tab text field
	 */
	public function password_protected_help_tab() {
		echo '<div class="pp-help-notice">
            <p>'
		     . __( 'Password protect your web site. Users will be asked to enter a password to view the site.', 'password-protected' )
		     . '<br />'
		     . __( 'For more information about Password Protected settings, view the "Help" tab at the top of this page.', 'password-protected' )
		     . '</p>
        </div>';
	}

	/**
	 * Try pro sideabr
	 */
	public function password_protected_try_pro() {
		$image_url = PASSWORD_PROTECTED_URL . 'assets/images/';
		echo '<div class="pp-sidebar-widget">
            <div class="pp-container">
            
                <div class="pp-sidebar-header">
                    <p class="heading-1">Level up your WordPress protection with</p>
                    <p class="heading-2">Password <img src="' . $image_url . 'crown.png" /> Protected <span>Pro</span></p>
                </div>

                <div class="pp-sidebar-body">
                    <ul>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Protect Specific Post Types</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Whitelist Specific User Role</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Password Protect WP-Admin</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Password Attempt Activity Report</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Specific Post/Page Protection</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Certain Page/Posts Exclusions</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Password Attempts Restriction</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Password Expiration and Usage Limit</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Bypass URL (Post, Page, Category, etc.)</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Activity Log For Each Password Attempt</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Multiple Password Management</span>
                        </li>
                    </ul>
                </div>
                <div class="pp-sidebar-footer">
                    <a target="_blank" href="https://passwordprotectedwp.com/pricing/?utm_source=plugin&utm_medium=side_banner&utm_campaign=plugin">' . esc_html__( 'Get Password Protected Pro', 'password-protected' ) . '</a>
                </div>
            </div>
        </div>';
	}

	public function password_protected_login_designer() {
		$search_login_designer = add_query_arg(
			array(
				's'    => 'login designer',
				'tab'  => 'search',
				'type' => 'term',
			),
			admin_url( 'plugin-install.php' )
		);
		echo '<div class="pp-sidebar-widget">
            <div id="pp-sidebar-box">
                <h3>' .
		     sprintf(
			     __( '%1$s Now you can customize your Password Protected screen with the %3$s %2$s', 'password-protected' ),
			     '🎨',
			     '🌈',
			     '<a href="' . $search_login_designer . '">' . __( 'Login Designer Plugin', 'password-protected' ) . '</a>'
		     )
		     . '</h3>
                
                <img width="100%" src="'. PASSWORD_PROTECTED_URL .'assets/images/login-designer-demo.gif" alt="Login Designer Demo GIF">
                
                <h3>
                    <a class="pp-try button-primary" href="' . $search_login_designer . '">
                        👉 ' . __( 'Try it now! It\'s Free', 'password-protected' ) . '
                    </a>
                </h3>
            </div>
        </div>';
	}

	/**
	 * Pre-update 'password_protected_password' Option
	 *
	 * Before the password is saved, MD5 it!
	 * Doing it in this way allows developers to intercept with an earlier filter if they
	 * need to do something with the plaintext password.
	 *
	 * @param   string  $newvalue  New Value.
	 * @param   string  $oldvalue  Old Value.
	 * @return  string             Filtered new value.
	 */
	public function pre_update_option_password_protected_password( $newvalue, $oldvalue ) {

		global $Password_Protected;

		if ( $newvalue != $oldvalue ) {
			$newvalue = $Password_Protected->encrypt_password( $newvalue );
		}

		return $newvalue;

	}

	/**
	 * Plugin Row Meta
	 *
	 * Adds GitHub and translate links below the plugin description on the plugins page.
	 *
	 * @param   array   $plugin_meta  Plugin meta display array.
	 * @param   string  $plugin_file  Plugin reference.
	 * @param   array   $plugin_data  Plugin data.
	 * @param   string  $status       Plugin status.
	 * @return  array                 Plugin meta array.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

		if ( 'password-protected/password-protected.php' == $plugin_file ) {
			$plugin_meta[] = sprintf( '<a href="%s">%s</a>', __( 'http://github.com/benhuson/password-protected', 'password-protected' ), __( 'GitHub', 'password-protected' ) );
			$plugin_meta[] = sprintf( '<a href="%s">%s</a>', __( 'https://translate.wordpress.org/projects/wp-plugins/password-protected', 'password-protected' ), __( 'Translate', 'password-protected' ) );
		}

		return $plugin_meta;

	}

	/**
	 * Plugin Action Links
	 *
	 * Adds settings link on the plugins page.
	 *
	 * @param   array  $actions  Plugin action links array.
	 * @return  array            Plugin action links array.
	 */
	public function plugin_action_links( $actions ) {

		$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=password-protected' ), __( 'Settings', 'password-protected' ) );
		return $actions;

	}

	/**
	 * Password Admin Notice
	 * Warns the user if they have enabled password protection but not entered a password
	 */
	public function password_protected_admin_notices() {
		global $Password_Protected;

		// Check Support
		$screens = $this->plugin_screen_ids( array( 'dashboard', 'plugins' ) );
		if ( $this->is_current_screen( $screens ) ) {
			$supported = $Password_Protected->is_plugin_supported();

			if ( is_wp_error( $supported ) ) {
				echo $this->admin_error_display( $supported->get_error_message( $supported->get_error_code() ) );
			}
		}

		// Settings
		if ( $this->is_current_screen( $this->plugin_screen_ids() ) ) {
			$status = get_option( 'password_protected_status' );
			$pwd = get_option( 'password_protected_password' );

			if ( (bool) $status && empty( $pwd ) ) {
				$error_message = __( 'You have enabled password protection but not yet set a password. Please set one below.', 'password-protected' );
				$error = apply_filters( 'password_protected_password_status_activation', $error_message );
				if( !empty( $error ) ) {
					echo $this->admin_error_display( $error );
				}
			}

			if ( current_user_can( 'manage_options' ) && ( (bool) get_option( 'password_protected_administrators' ) || (bool) get_option( 'password_protected_users' ) ) ) {
				if ( (bool) get_option( 'password_protected_administrators' ) && (bool) get_option( 'password_protected_users' ) ) {
					echo $this->admin_error_display( __( 'You have enabled password protection and allowed administrators and logged in users - other users will still need to enter a password to view the site.', 'password-protected' ) );
				} elseif ( (bool) get_option( 'password_protected_administrators' ) ) {
					if ( (bool) get_option( 'password_protected_status' ) ) {
						echo $this->admin_error_display( __( 'You have enabled password protection and allowed administrators - other users will still need to enter a password to view the site.', 'password-protected' ) );
					}
				} elseif ( (bool) get_option( 'password_protected_users' ) ) {
					if ( (bool) get_option( 'password_protected_status' ) ) {
						echo $this->admin_error_display( __( 'You have enabled password protection and allowed logged in users - other users will still need to enter a password to view the site.', 'password-protected' ) );
					}
				}
			}

		}

	}

	/**
	 * Admin Error Display
	 *
	 * Returns a string wrapped in HTML to display an admin error.
	 *
	 * @param   string  $string  Error string.
	 * @return  string           HTML error.
	 */
	private function admin_error_display( $string ) {

		return '<div class="error"><p>' .  $string . '</p></div>';

	}

	/**
	 * Is Current Screen
	 *
	 * Checks wether the admin is displaying a specific screen.
	 *
	 * @param   string|array  $screen_id  Admin screen ID(s).
	 * @return  boolean
	 */
	public function is_current_screen( $screen_id ) {

		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			if ( ! is_array( $screen_id ) ) {
				$screen_id = array( $screen_id );
			}
			if ( in_array( $current_screen->id, $screen_id ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Plugin Screen IDs
	 *
	 * @param   string|array  $screen_id  Additional screen IDs to add to the returned array.
	 * @return  array                     Screen IDs.
	 */
	public function plugin_screen_ids( $screen_id = '' ) {

		$screen_ids = array( 'options-' . $this->options_group, 'settings_page_' . $this->options_group );
		array_push( $screen_ids, 'toplevel_page_'.$this->options_group );
		if ( ! empty( $screen_id ) ) {
			if ( is_array( $screen_id ) ) {
				$screen_ids = array_merge( $screen_ids, $screen_id );
			} else {
				$screen_ids[] = $screen_id;
			}
		}
		// toplevel_page_password-protected
		return $screen_ids;

	}

	/**
	 * @return  bool
	 * true if password protected pro is installed and activated otherwise false
	 */
	public function password_protected_pro_is_installed_and_activated(): bool {
		return class_exists( 'Password_Protected_Pro' );
	}

	public function login_designer_is_installed_and_activated() {
		return class_exists( 'Login_designer' );
	}

	/**
	 * @return  void
	 * Display Pro Features
	 */
	public function password_protected_get_pro_features() {
		$image_url = PASSWORD_PROTECTED_URL . 'assets/images/';
		echo '<div class="pp-pro-banner">
            <div class="pp-container">
                <div class="pp-banner-header">
                    <p class="heading-1">Level up your WordPress protection with</p>
                    <p class="heading-2">Password Protected
                        <img src="' . $image_url . 'crown.png" alt="">
                        <span>Pro</span>
                    </p>
                </div>
                
                <div class="pp-banner-body">
                    <div class="pp-cols">
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Protect Specific Post Types
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/post-and-page-protection/how-to-secure-all-posts-and-pages/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>

                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Whitelist Specific User Role
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/pro/whitelist-specific-user-role/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Password Protect WP-Admin
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/pro/password-protect-wp-admin/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Password Attempt Activity Report
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/logs/password-attempt-activity-report?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>

                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Specific Post/Page Protection
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/post-and-page-protection/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Certain Page/Posts Exclusions
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/pro/exclude-pages-posts-and-post-types/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div> 
                        
                    </div>
                    <div class="pp-cols pp-cols-section-2">
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Password Attempts Restriction
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/pro/limit-password-attempts-and-lockdown-time/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Password Expiration and Usage Limit
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/pro/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Bypass URL (Post, Page, Category, etc.)
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/pro/bypass-password-protection-for-specific-urls/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Activity Log For Each Password Attempt
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/logs/password-activity-logs/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                        <div>
                            <img src="' . $image_url . 'lock-2.png">
                            Multiple Password Management
                            <span class="pp-docs-link">
                                <a target="_blank" href="https://passwordprotectedwp.com/documentation/pro/manage-multiple-websites/?utm_source=plugin&utm_medium=pro_tab">Docs</a>
                            </span>
                        </div>
                        
                    </div>
                    
                    <div class="pp-clearfix"></div>
                </div>
                
                <div class="pp-banner-footer">
                    <a target="_blank" href="https://passwordprotectedwp.com/pricing/?utm_source=plugin&utm_medium=pro_tab&utm_campaign=plugin">' . esc_html__( 'Get Password Protected Pro', 'password-protected' ) . '</a>
                </div>
            </div>
        </div>';
	}

	public function dummy_content( $k ) {

        echo '<div class="disabled-content click-to-display-popup">
            <div class="pp-wrap-content"></div>
            <div class="pp-pro-branding" style="margin-top: 10px" >';
		if ( isset( $k['sub-tab'] ) ) {
			switch ( $k['sub-tab'] ) {
				case 'password-protected-page-description':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'page_description'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Password Protected Outside Form description <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table">
							<tr>
								<th><label for="">Text Above Form</label></th>
								<td>
									<textarea id="password_protected_text_below_form" name="password_protected_text_below_form" rows="4" cols="50" class="regular-text"></textarea>
								</td>
							</tr>
							
							<tr>
								<th><label for="">Text Below Form</label></th>
								<td>
									<textarea id="password_protected_text_below_form" name="password_protected_text_below_form" rows="4" cols="50" class="regular-text"></textarea>
								</td>
							</tr>
						</table>
					</div>';
					break;
			}
		}
		if ( isset( $k['slug'] ) ) {
			switch ( $k['slug'] ) {
				case 'exclude-from-protection':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'exclude_protection'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Exclude From Password Protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table">
							<tr>
								<th><label for="">Exclude Pages</label></th>
								<td>
									<input disabled placeholder="Select pages to exclude" type="text" class="regular-text" />
								</td>
							</tr>
							
							<tr>
								<th><label for="">Exclude Posts</label></th>
								<td>
									<input disabled placeholder="Select posts to exclude" type="text" class="regular-text" />
								</td>
							</tr>
							
							<tr>
								<th><label for="">Exclude post Types</label></th>
								<td>
									<input disabled placeholder="Select post types to exclude" type="text" class="regular-text" />
								</td>
							</tr>
						</table>
					</div>';
					break;
				case 'attempt-limitation':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'attempt_limitation'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Limit Password Attempts <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table">
							<tr>
								<th><label for="">No of Attempts</label></th>
								<td>
									<input disabled placeholder="Limit Password Attempts" type="text" class="regular-text" />
								</td>
							</tr>
							
							<tr>
								<th><label for="">Lockdown Time In Minutes:	</label></th>
								<td>
									<input disabled placeholder="Lockdown Time" type="text" class="regular-text" />
								</td>
							</tr>
						</table>
					</div>';
					break;
				case 'bypass-url':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'bypass_url'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Bypass URL <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table">
							<tr>
								<th>
									<label for="">Enable Bypass URL</label>
								</th>
								<td>
									<div class="pp-toggle-wrapper">
										<input disabled type="checkbox" >
										<label class="pp-toggle">
											<span class="pp-toggle-slider"></span>
										</label>
									</div>
								</td>
							</tr>

							<tr>
								<th>
									<label for="">Set Bypass key</label>
								</th>
								<td>
									<input disabled type="text" class="regular-text">
								</td>
							</tr>
							
							<tr>
								<th>
									<label for="">Redirect To</label>
								</th>
								<td>
									<input disabled type="text" class="regular-text">
								</td>
							</tr>
						</table>
					</div>';
					break;
				case 'manage_passwords':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'multiple_passwords'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Manage Passwords <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<button disabled class="button button-secondary">Add New Password</button>
						<br><br>
						
						<table class="wp-list-table widefat fixed striped table-view-list toplevel_page_password-protected">
							<thead>
								<tr>
									<th>Password</th>
									<th>Uses Remaining</th>
									<th>Expiry</th>
									<th>Status</th>
									<th>Actions</th>
									<th>Bypass URL</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="6">
										Manage passwords are only available in Password Protected Pro version.
									</td>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<th>Password</th>
									<th>Uses Remaining</th>
									<th>Expiry</th>
									<th>Status</th>
									<th>Actions</th>
									<th>Bypass URL</th>
								</tr>
							</tfoot>
						</table>
					</div>';
					break;
				case 'activity_logs':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'activity_logs'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Activity Logs <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="wp-list-table widefat fixed striped table-view-list toplevel_page_password-protected">
							<thead>
								<tr>
									<th>IP</th>
									<th>Browser</th>
									<th>Status</th>
									<th>Password</th>
									<th>Date Time</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="5">
										Activity logs are only available in Password Protected Pro version.
									</td>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<th>IP</th>
									<th>Browser</th>
									<th>Status</th>
									<th>Password</th>
									<th>Date Time</th>
								</tr>
							</tfoot>
						</table>
					</div>';
					break;
				case 'post-type-protection':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'post_protection'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Post type protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table">
							<tr>
								<th>Post Type</th>
								<th>Global</th>
								<th>Individual</th>
							</tr>

							<tr>
								<th>Post</th>
								<td><input disabled type="checkbox"></td>
								<td><input disabled type="checkbox"></td>
							</tr>
							<tr>
								<th>Page</th>
								<td><input disabled type="checkbox"></td>
								<td><input disabled type="checkbox"></td>
							</tr>
						</table>
					</div>';
					break;
				case 'taxonomy-protection':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'taxonomy_protection'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Category/Taxonomy protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>

						<table class="form-table">
							<tr>
								<th>Category</th>
								<td><input disabled type="checkbox"></td>
							</tr>
							<tr>
								<th>Post_tag</th>
								<td><input disabled type="checkbox"></td>
							</tr>
						</table>
					</div>';
					break;
				case 'whitelist-user-role':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'whitelist_user_role'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>White List User Roles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table">
							<tr>
								<th>Enable Whitelist User Roles</th>
								<td>
									<div class="pp-toggle-wrapper">
										<input disabled type="checkbox" >
										<label class="pp-toggle">
											<span class="pp-toggle-slider"></span>
										</label>
									</div>
								</td>
							</tr>
							<tr>
								<th>Whitelist User Roles</th>
								<td>
									<input disabled type="text" class="regular-text">
								</td>
							</tr>
						</table>
						<h2>WP Login Screen Redirect</h2>
						<table class="form-table">
							<tr>
								<th>Enable WP Login Screen Redirection</th>
								<td>
									<div class="pp-toggle-wrapper">
											<input disabled type="checkbox" >
											<label class="pp-toggle">
												<span class="pp-toggle-slider"></span>
											</label>
									</div>
								</td>
							</tr>
							<tr>
								<th>Add Text for Redirection Link</th>
								<td><textarea disabled class="regular-text"></textarea></td>
							</tr>
						</table>
					</div>';
					break;
				case 'wp-admin-protection':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'wpadmin_protection'
						),
						'https://passwordprotectedwp.com/pricing/'
					);
					echo '<div>
						<h2>Enable Admin Protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table">
							<tr>
								<th>Enable</th>
								<td><div class="pp-toggle-wrapper">
										<input disabled type="checkbox" >
										<label class="pp-toggle">
											<span class="pp-toggle-slider"></span>
										</label>
									</div></td>
							</tr>
						</table>
						<h2>Password</h2>
						<table class="form-table">
							<tr>
								<th>Password</th>
								<td>
									<input disabled type="text" class="regular-text" />
									<br><br>
									<input disabled type="text" class="regular-text" />
								</td>
							</tr>
						</table>
						<h2>Remember Me</h2>
						<table class="form-table">
							<tr>
								<th>Remember Me</th>
								<td><div class="pp-toggle-wrapper">
										<input disabled type="checkbox" >
										<label class="pp-toggle">
											<span class="pp-toggle-slider"></span>
										</label>
									</div></td>
							</tr>
							<tr>
								<th>Remember Me Many Days</th>
								<td>
									<input disabled type="text" class="regular-text" />
								</td>
							</tr>
						</table>
						<h2>Forgot Password</h2>
						<table class="form-table">
							<tr>
								<th>Forgot Password</th>
								<td><div class="pp-toggle-wrapper">
										<input disabled type="checkbox" >
										<label class="pp-toggle">
											<span class="pp-toggle-slider"></span>
										</label>
									</div></td>
							</tr>
						</table>
					</div>';
					break;

				case 'logo-styles':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'logo_styles'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

                    $default_logo_url = admin_url( 'images/wordpress-logo.svg' );

					echo '<div>
						<h2>Logo Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>

						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="logo">logo</label></th><td><div class="pp-media-wrapper">
						<input type="hidden" value="0" id="logo" name="password_protected_logo_styles[logo]">
						<div class="pp-media-preview"><img src="' . $default_logo_url . '" alt="' . $default_logo_url . '"></div>
						<button class="button pp-media-upload">Upload</button>
						<button class="button pp-media-remove">Remove</button>
					</div></td></tr><tr><th scope="row"><label for="logo_width">Logo Width</label></th><td><div class="range-slider-wrapper"><label for="logo_width" pp-customizer-placeholder="px"><strong>84px</strong></label><input pp-default-value="84" id="logo_width" class="regular-text range-slider-input" name="password_protected_logo_styles[logo_width]" min="30" max="400" step="1" value="84" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="84">Reset</button></div></td></tr><tr><th scope="row"><label for="logo_height">Logo Height</label></th><td><div class="range-slider-wrapper"><label for="logo_height" pp-customizer-placeholder="px"><strong>84px</strong></label><input pp-default-value="84" id="logo_height" class="regular-text range-slider-input" name="password_protected_logo_styles[logo_height]" min="30" max="400" step="1" value="84" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="84">Reset</button></div></td></tr><tr><th scope="row"><label for="redirect_url">Redirect URL</label></th><td><select id="redirect_url" name="password_protected_logo_styles[redirect_url]" class="regular-text">
						<option value="2" selected="selected">Sample Page</option>
					</select></td></tr><tr><th scope="row"><label for="disable_logo">Disable Logo</label></th><td><div class="pp-toggle-wrapper">
						<input type="checkbox" value="yes" id="disable_logo" name="password_protected_logo_styles[disable_logo]">
						<label class="pp-toggle" for="disable_logo">
							<span class="pp-toggle-slider"></span>
						</label>
					</div></td></tr></tbody></table>
					</div>';
					break;
				case 'label-styles':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'label_styles'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
						<h2>Label Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="label">Label</label></th><td><input type="text" value="Password" id="label" name="password_protected_labels_styles[label]" class="regular-text"></td></tr><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_labels_styles[font]" class="regular-text">
						<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
					</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>14px</strong></label><input pp-default-value="14" id="font-size" class="regular-text range-slider-input" name="password_protected_labels_styles[font-size]" min="13" max="40" step="1" value="14" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="14">Reset</button></div></td></tr><tr><th scope="row"><label for="position">Position</label></th><td><div class="range-slider-wrapper"><label for="position" pp-customizer-placeholder="px"><strong>2px</strong></label><input pp-default-value="2" id="position" class="regular-text range-slider-input" name="password_protected_labels_styles[position]" min="0" max="20" step="1" value="2" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="2">Reset</button></div></td></tr><tr><th scope="row"><label for="color">Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(114, 119, 124);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#72777c" id="color" name="password_protected_labels_styles[color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 92.8838px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 235, 235), rgb(255, 245, 235), rgb(255, 255, 235), rgb(245, 255, 235), rgb(235, 255, 235), rgb(235, 255, 245), rgb(235, 255, 255), rgb(235, 245, 255), rgb(235, 235, 255), rgb(245, 235, 255), rgb(255, 235, 255), rgb(255, 235, 245), rgb(255, 235, 235));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 61, 122), rgb(125, 125, 125));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 8%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>
					
					</div>';
					break;
				case 'field-styles':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'field_styles'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
						<h2>Field Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(251, 251, 251);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#fbfbfb" id="bg-color" name="password_protected_fields_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 3.6425px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(250, 0, 0), rgb(250, 250, 250));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border">Border</label></th><td><div class="range-slider-wrapper"><label for="border" pp-customizer-placeholder="px"><strong>1px</strong></label><input pp-default-value="1" id="border" class="regular-text range-slider-input" name="password_protected_fields_styles[border]" min="0" max="10" step="1" value="1" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="1">Reset</button></div></td></tr><tr><th scope="row"><label for="border-color">Border Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(221, 221, 221);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#dddddd" id="border-color" name="password_protected_fields_styles[border-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 23.6762px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(219, 0, 0), rgb(222, 222, 222));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="margin-bottom">Margin Bottom</label></th><td><div class="range-slider-wrapper"><label for="margin-bottom" pp-customizer-placeholder="px"><strong>16px</strong></label><input pp-default-value="16" id="margin-bottom" class="regular-text range-slider-input" name="password_protected_fields_styles[margin-bottom]" min="1" max="60" step="1" value="16" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="16">Reset</button></div></td></tr><tr><th scope="row"><label for="padding">Padding</label></th><td><div class="range-slider-wrapper"><label for="padding" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="padding" class="regular-text range-slider-input" name="password_protected_fields_styles[padding]" min="0" max="40" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-top">Padding Top</label></th><td><div class="range-slider-wrapper"><label for="padding-top" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="padding-top" class="regular-text range-slider-input" name="password_protected_fields_styles[padding-top]" min="0" max="40" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-bottom">Padding Bottom</label></th><td><div class="range-slider-wrapper"><label for="padding-bottom" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="padding-bottom" class="regular-text range-slider-input" name="password_protected_fields_styles[padding-bottom]" min="0" max="40" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border-radius" class="regular-text range-slider-input" name="password_protected_fields_styles[border-radius]" min="0" max="60" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow">Shadow</label></th><td><div class="range-slider-wrapper"><label for="shadow" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="shadow" class="regular-text range-slider-input" name="password_protected_fields_styles[shadow]" min="0" max="30" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-opacity">Shadow Opacity</label></th><td><div class="range-slider-wrapper"><label for="shadow-opacity" pp-customizer-placeholder="%"><strong>7%</strong></label><input pp-default-value="7" id="shadow-opacity" class="regular-text range-slider-input" name="password_protected_fields_styles[shadow-opacity]" min="0" max="100" step="1" value="7" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="7">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-inset">Shadow Inset</label></th><td><div class="pp-toggle-wrapper">
						<input type="checkbox" value="yes" id="shadow-inset" name="password_protected_fields_styles[shadow-inset]">
						<label class="pp-toggle" for="shadow-inset">
							<span class="pp-toggle-slider"></span>
						</label>
					</div></td></tr></tbody></table>
					
					<h2>Text Styles</h2>
					
					<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_fields_styles[font]" class="regular-text">
						<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
					</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>24px</strong></label><input pp-default-value="24" id="font-size" class="regular-text range-slider-input" name="password_protected_fields_styles[font-size]" min="13" max="40" step="1" value="24" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="24">Reset</button></div></td></tr><tr><th scope="row"><label for="text-color">Text Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(130, 36, 227);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#8224e3" id="text-color" name="password_protected_fields_styles[text-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 136.594px; top: 20.0337px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 41, 41), rgb(255, 148, 41), rgb(255, 255, 41), rgb(148, 255, 41), rgb(41, 255, 41), rgb(41, 255, 148), rgb(41, 255, 255), rgb(41, 148, 255), rgb(41, 41, 255), rgb(148, 41, 255), rgb(255, 41, 255), rgb(255, 41, 148), rgb(255, 41, 41));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(112, 0, 224), rgb(227, 227, 227));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 84%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>
					</div>';
					break;
				case 'button-styles':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'button_styles'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
						<h2>Button Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(0, 133, 186);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#0085ba" id="bg-color" name="password_protected_button_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 99.6628px; top: 49.1737px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 0, 0), rgb(255, 128, 0), rgb(255, 255, 0), rgb(128, 255, 0), rgb(0, 255, 0), rgb(0, 255, 128), rgb(0, 255, 255), rgb(0, 128, 255), rgb(0, 0, 255), rgb(128, 0, 255), rgb(255, 0, 255), rgb(255, 0, 128), rgb(255, 0, 0));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 132, 184), rgb(186, 186, 186));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 100%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border">Border</label></th><td><div class="range-slider-wrapper"><label for="border" pp-customizer-placeholder="px"><strong>1px</strong></label><input pp-default-value="1" id="border" class="regular-text range-slider-input" name="password_protected_button_styles[border]" min="0" max="10" step="1" value="1" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="1">Reset</button></div></td></tr><tr><th scope="row"><label for="border-color">Border Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(0, 115, 170);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#0073aa" id="border-color" name="password_protected_button_styles[border-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 100.675px; top: 60.1012px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 0, 0), rgb(255, 128, 0), rgb(255, 255, 0), rgb(128, 255, 0), rgb(0, 255, 0), rgb(0, 255, 128), rgb(0, 255, 255), rgb(0, 128, 255), rgb(0, 0, 255), rgb(128, 0, 255), rgb(255, 0, 255), rgb(255, 0, 128), rgb(255, 0, 0));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 115, 168), rgb(171, 171, 171));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 100%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="padding">Padding</label></th><td><div class="range-slider-wrapper"><label for="padding" pp-customizer-placeholder="px"><strong>12px</strong></label><input pp-default-value="12" id="padding" class="regular-text range-slider-input" name="password_protected_button_styles[padding]" min="0" max="60" step="1" value="12" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="12">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-top">Padding Top</label></th><td><div class="range-slider-wrapper"><label for="padding-top" pp-customizer-placeholder="px"><strong>4px</strong></label><input pp-default-value="4" id="padding-top" class="regular-text range-slider-input" name="password_protected_button_styles[padding-top]" min="1" max="20" step="1" value="4" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="4">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-bottom">Padding Bottom</label></th><td><div class="range-slider-wrapper"><label for="padding-bottom" pp-customizer-placeholder="px"><strong>4px</strong></label><input pp-default-value="4" id="padding-bottom" class="regular-text range-slider-input" name="password_protected_button_styles[padding-bottom]" min="1" max="20" step="1" value="4" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="4">Reset</button></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="border-radius" class="regular-text range-slider-input" name="password_protected_button_styles[border-radius]" min="0" max="60" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow">Shadow</label></th><td><div class="range-slider-wrapper"><label for="shadow" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="shadow" class="regular-text range-slider-input" name="password_protected_button_styles[shadow]" min="0" max="30" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-opacity">Shadow Opacity</label></th><td><div class="range-slider-wrapper"><label for="shadow-opacity" pp-customizer-placeholder="%"><strong>0%</strong></label><input pp-default-value="0" id="shadow-opacity" class="regular-text range-slider-input" name="password_protected_button_styles[shadow-opacity]" min="0" max="100" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr></tbody></table>
						<h2>Text Styles</h2>
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_button_styles[font]" class="regular-text">
						<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
					</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>13px</strong></label><input pp-default-value="13" id="font-size" class="regular-text range-slider-input" name="password_protected_button_styles[font-size]" min="13" max="40" step="1" value="13" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="13">Reset</button></div></td></tr><tr><th scope="row"><label for="text-color">Text Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(5, 5, 5);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#050505" id="text-color" name="password_protected_button_styles[text-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 178.482px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(5, 0, 0), rgb(5, 5, 5));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>

					</div>';
					break;
				case 'remember-me-styles':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'remember_me_styles'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
						<h2>Checkbox Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="size">Size</label></th><td><div class="range-slider-wrapper"><label for="size" pp-customizer-placeholder="px"><strong>16px</strong></label><input pp-default-value="16" id="size" class="regular-text range-slider-input" name="password_protected_rememberme_styles[size]" min="16" max="20" step="1" value="16" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="16">Reset</button></div></td></tr><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(251, 251, 251);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#fbfbfb" id="bg-color" name="password_protected_rememberme_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 3.6425px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(250, 0, 0), rgb(250, 250, 250));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border">Border</label></th><td><div class="range-slider-wrapper"><label for="border" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border" class="regular-text range-slider-input" name="password_protected_rememberme_styles[border]" min="0" max="3" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="border-color">Border Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(180, 185, 190);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#b4b9be" id="border-color" name="password_protected_rememberme_styles[border-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 45.5312px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 240, 240), rgb(255, 247, 240), rgb(255, 255, 240), rgb(247, 255, 240), rgb(240, 255, 240), rgb(240, 255, 247), rgb(240, 255, 255), rgb(240, 247, 255), rgb(240, 240, 255), rgb(247, 240, 255), rgb(255, 240, 255), rgb(255, 240, 247), rgb(255, 240, 240));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 94, 189), rgb(191, 191, 191));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 5%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border-radius" class="regular-text range-slider-input" name="password_protected_rememberme_styles[border-radius]" min="0" max="30" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr></tbody></table>
						
						<h2>Label Styles</h2>
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_rememberme_styles[font]" class="regular-text">
						<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
					</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>12px</strong></label><input pp-default-value="12" id="font-size" class="regular-text range-slider-input" name="password_protected_rememberme_styles[font-size]" min="8" max="20" step="1" value="12" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="12">Reset</button></div></td></tr><tr><th scope="row"><label for="position">Position</label></th><td><div class="range-slider-wrapper"><label for="position" pp-customizer-placeholder="px"><strong>5px</strong></label><input pp-default-value="5" id="position" class="regular-text range-slider-input" name="password_protected_rememberme_styles[position]" min="0" max="20" step="1" value="5" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="5">Reset</button></div></td></tr><tr><th scope="row"><label for="color">Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(114, 119, 124);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#72777c" id="color" name="password_protected_rememberme_styles[color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 92.8838px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 235, 235), rgb(255, 245, 235), rgb(255, 255, 235), rgb(245, 255, 235), rgb(235, 255, 235), rgb(235, 255, 245), rgb(235, 255, 255), rgb(235, 245, 255), rgb(235, 235, 255), rgb(245, 235, 255), rgb(255, 235, 255), rgb(255, 235, 245), rgb(255, 235, 235));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 61, 122), rgb(125, 125, 125));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 8%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>

					</div>';
					break;
				case 'form-background':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'form_background'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
						<h2>Form Background <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>

						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(255, 255, 255);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#ffffff" id="bg-color" name="password_protected_form_bg_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 0px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(255, 0, 0), rgb(255, 255, 255));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border-radius" class="regular-text range-slider-input" name="password_protected_form_bg_styles[border-radius]" min="0" max="50" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow">Shadow</label></th><td><div class="range-slider-wrapper"><label for="shadow" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="shadow" class="regular-text range-slider-input" name="password_protected_form_bg_styles[shadow]" min="0" max="70" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-opacity">Shadow Opacity</label></th><td><div class="range-slider-wrapper"><label for="shadow-opacity" pp-customizer-placeholder="%"><strong>13%</strong></label><input pp-default-value="13" id="shadow-opacity" class="regular-text range-slider-input" name="password_protected_form_bg_styles[shadow-opacity]" min="0" max="100" step="1" value="13" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="13">Reset</button></div></td></tr><tr><th scope="row"><label for="side-padding">Side Padding</label></th><td><div class="range-slider-wrapper"><label for="side-padding" pp-customizer-placeholder="px"><strong>24px</strong></label><input pp-default-value="24" id="side-padding" class="regular-text range-slider-input" name="password_protected_form_bg_styles[side-padding]" min="0" max="100" step="1" value="24" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="24">Reset</button></div></td></tr><tr><th scope="row"><label for="transparent">Transparent</label></th><td><div class="pp-toggle-wrapper">
						<input type="checkbox" value="yes" id="transparent" name="password_protected_form_bg_styles[transparent]">
						<label class="pp-toggle" for="transparent">
							<span class="pp-toggle-slider"></span>
						</label>
					</div></td></tr><tr><th scope="row"><label for="vertical-padding">Vertical Padding</label></th><td><div class="range-slider-wrapper"><label for="vertical-padding" pp-customizer-placeholder="px"><strong>26px</strong></label><input pp-default-value="26" id="vertical-padding" class="regular-text range-slider-input" name="password_protected_form_bg_styles[vertical-padding]" min="0" max="100" step="1" value="26" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="26">Reset</button></div></td></tr><tr><th scope="row"><label for="width">Width</label></th><td><div class="range-slider-wrapper"><label for="width" pp-customizer-placeholder="px"><strong>320px</strong></label><input pp-default-value="320" id="width" class="regular-text range-slider-input" name="password_protected_form_bg_styles[width]" min="300" max="800" step="1" value="320" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="320">Reset</button></div></td></tr></tbody></table>

					</div>';
					break;
				case 'body-background':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'body_background'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
						<h2>Body Background <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(241, 241, 241);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#f1f1f1" id="bg-color" name="password_protected_body_bg_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 9.10625px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(240, 0, 0), rgb(242, 242, 242));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="bg-image">Background Image</label></th><td><div class="pp-media-wrapper">
						<button class="button pp-media-upload">Upload</button>
					</div></td></tr><tr><th scope="row"><label for="bg-repeat">Background Repeat</label></th><td><select id="bg-repeat" name="password_protected_body_bg_styles[bg-repeat]" class="regular-text">
						<option value="no-repeat" selected="selected">No Repeat</option><option value="repeat">Repeat</option><option value="repeat-x">Repeat Horizontally</option><option value="repeat-y">Repeat Vertically</option>
					</select></td></tr><tr><th scope="row"><label for="bg-size">Background Size</label></th><td><select id="bg-size" name="password_protected_body_bg_styles[bg-size]" class="regular-text">
						<option value="auto">Auto</option><option value="cover" selected="selected">Cover</option><option value="contain">Contain</option>
					</select></td></tr><tr><th scope="row"><label for="bg-position">Background Position</label></th><td><select id="bg-position" name="password_protected_body_bg_styles[bg-position]" class="regular-text">
						<option value="left top">Left Top</option><option value="left center">Left Center</option><option value="left bottom">Left Bottom</option><option value="right top">Right Top</option><option value="right center">Right Center</option><option value="right bottom">Right Bottom</option><option value="center top">Center Top</option><option value="center center" selected="selected">Center Center</option><option value="center bottom">Center Bottom</option>
					</select></td></tr></tbody></table>
						
					</div>';
					break;
				case 'below-form':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'form_content'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
						<h2>Form Content <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
						
						<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_below_form_styles[font]" class="regular-text">
						<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
					</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>14px</strong></label><input pp-default-value="14" id="font-size" class="regular-text range-slider-input" name="password_protected_below_form_styles[font-size]" min="0" max="100" step="1" value="14" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="14">Reset</button></div></td></tr><tr><th scope="row"><label for="position">Position</label></th><td><div class="range-slider-wrapper"><label for="position" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="position" class="regular-text range-slider-input" name="password_protected_below_form_styles[position]" min="0" max="100" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="color">Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(114, 119, 124);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#72777c" id="color" name="password_protected_below_form_styles[color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 92.8838px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 235, 235), rgb(255, 245, 235), rgb(255, 255, 235), rgb(245, 255, 235), rgb(235, 255, 235), rgb(235, 255, 245), rgb(235, 255, 255), rgb(235, 245, 255), rgb(235, 235, 255), rgb(245, 235, 255), rgb(255, 235, 255), rgb(255, 235, 245), rgb(255, 235, 235));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 61, 122), rgb(125, 125, 125));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 8%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="text-alignment">Text Alignment</label></th><td><select id="text-alignment" name="password_protected_below_form_styles[text-alignment]" class="regular-text">
						<option value="left">Left</option><option value="center" selected="selected">Center</option><option value="right">Right</option>
					</select></td></tr></tbody></table>
						
					</div>';
					break;
                case 'below-page':
                    $url = add_query_arg(
                        array(
                            'utm_source'   => 'plugin',
                            'utm_medium'   => 'pop_up',
                            'utm_campaign' => 'plugin',
                            'utm_content'  => 'page_content'
                        ),
                        'https://passwordprotectedwp.com/pricing/'
                    );

                    echo '<div>
                    <h2>Page Content <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_below_page_styles[font]" class="regular-text">
					<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
				</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>14px</strong></label><input pp-default-value="14" id="font-size" class="regular-text range-slider-input" name="password_protected_below_page_styles[font-size]" min="0" max="100" step="1" value="14" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="14">Reset</button></div></td></tr><tr><th scope="row"><label for="position">Position</label></th><td><div class="range-slider-wrapper"><label for="position" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="position" class="regular-text range-slider-input" name="password_protected_below_page_styles[position]" min="0" max="100" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="color">Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(114, 119, 124);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#72777c" id="color" name="password_protected_below_page_styles[color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 92.8838px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 235, 235), rgb(255, 245, 235), rgb(255, 255, 235), rgb(245, 255, 235), rgb(235, 255, 235), rgb(235, 255, 245), rgb(235, 255, 255), rgb(235, 245, 255), rgb(235, 235, 255), rgb(245, 235, 255), rgb(255, 235, 255), rgb(255, 235, 245), rgb(255, 235, 235));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 61, 122), rgb(125, 125, 125));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 8%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="text-alignment">Text Alignment</label></th><td><select id="text-alignment" name="password_protected_below_page_styles[text-alignment]" class="regular-text">
					<option value="left">Left</option><option value="center" selected="selected">Center</option><option value="right">Right</option>
				</select></td></tr></tbody></table>
                    
                </div>';
                    break;
				case 'custom-css':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'page_content'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
                    <h2>Custom CSS <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="custom-css">Custom CSS</label></th><td><textarea id="custom-css" name="password_protected_custom_css_styles[custom-css]" class="large-text"></textarea></td></tr></tbody></table>
                    
                </div>';
					break;

				case 'password-request':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'password-request'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
                    <h2>Request Password <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="enable-password-requests">Enable Password Requests</label></th><td><div class="pp-toggle-wrapper">
					<input type="checkbox" value="yes" id="enable-password-requests" name="pp_password_request_setting[enable-password-requests]" checked="checked">
					<label class="pp-toggle" for="enable-password-requests">
						<span class="pp-toggle-slider"></span>
					</label>
				</div><p class="desc"><strong>Enable password requests for your site.</strong></p></td></tr><tr><th scope="row"><label for="password-request-label">Password Request Label</label></th><td><input type="text" value="Request for password" id="password-request-label" name="pp_password_request_setting[password-request-label]" class="regular-text"><p class="desc"><strong>Change the label of the password request button.</strong></p></td></tr><tr><th scope="row"><label for="add-your-email-label">Add Your Email Label</label></th><td><input type="text" value="Email Address" id="add-your-email-label" name="pp_password_request_setting[add-your-email-label]" class="regular-text"><p class="desc"><strong>Change the label of the email input field.</strong></p></td></tr><tr><th scope="row"><label for="email-place-holder-label">Email Placeholder Label</label></th><td><input type="text" value="Enter your email address" id="email-place-holder-label" name="pp_password_request_setting[email-place-holder-label]" class="regular-text"><p class="desc"><strong>Change the placeholder of the email input field.</strong></p></td></tr><tr><th scope="row"><label for="validation-button-label">Validation Button Label</label></th><td><input type="text" value="Validate your email" id="validation-button-label" name="pp_password_request_setting[validation-button-label]" class="regular-text"><p class="desc"><strong>Change the label of the validation button.</strong></p></td></tr></tbody></table>
                </div>';
					break;
				case 'requests':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'password-request'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
                    <h2>Requests <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <div class="pp-settings-wrapper">
								<div style="margin: 10px 0;">
						<a style="margin: 0 10px;text-decoration: none;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests">All (1)</a>
						<a style="margin: 0 10px;text-decoration: none;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;status=pending">Pending (0)</a>
						<a style="margin: 0 10px;text-decoration: none;;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;status=approved">Approved (1)</a>
						<a style="margin: 0 10px;text-decoration: none;;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;status=rejected">Rejected (0)</a>
					</div><table class="wp-list-table widefat fixed striped table-view-list ">
			<thead>
	<tr>
		<th scope="col" id="id" class="manage-column column-id column-primary">ID</th><th scope="col" id="email" class="manage-column column-email">Email</th><th scope="col" id="requested_content" class="manage-column column-requested_content">Requested Content</th><th scope="col" id="status" class="manage-column column-status">Status</th><th scope="col" id="datetime" class="manage-column column-datetime">Date &amp; Time</th><th scope="col" id="action" class="manage-column column-action">Action</th>	</tr>
	</thead>

	<tbody id="the-list">
		<tr><td class="id column-id has-row-actions column-primary" data-colname="ID">1<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class="email column-email" data-colname="Email">email@localwp.com</td><td class="requested_content column-requested_content" data-colname="Requested Content">wp-admin/admin.sds</td><td class="status column-status" data-colname="Status"><span class="pp-status pp-status-approved">Approved</span></td><td class="datetime column-datetime" data-colname="Date &amp; Time">March 27, 2025, 6:06 am</td><td class="action column-action" data-colname="Action"><a href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;action=resend&amp;id=1&amp;_wpnonce=b5c8a49b1b&amp;email=email@localwp.com" class="pp-rensend-password-email">Send Password Email</a>
				|
				<a href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;action=delete&amp;id=1&amp;_wpnonce=5468d5c8c5" class="pp-delete-request">Delete</a></td></tr>	</tbody>

	<tfoot>
	<tr>
		<th scope="col" class="manage-column column-id column-primary">ID</th><th scope="col" class="manage-column column-email">Email</th><th scope="col" class="manage-column column-requested_content">Requested Content</th><th scope="col" class="manage-column column-status">Status</th><th scope="col" class="manage-column column-datetime">Date &amp; Time</th><th scope="col" class="manage-column column-action">Action</th>	</tr>
	</tfoot>

</table>
		                            </div>
                </div>';
					break;
				case 'email-templates':
					$url = add_query_arg(
						array(
							'utm_source'   => 'plugin',
							'utm_medium'   => 'pop_up',
							'utm_campaign' => 'plugin',
							'utm_content'  => 'password-request'
						),
						'https://passwordprotectedwp.com/pricing/'
					);

					echo '<div>
                    <h2>Email Templates <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <div class="ppp-email-templates" id="validations"><h2>Validation\'s</h2>
<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="rp-validation-subject">Email Subject</label></th><td><input type="text" value="Verify Your Email Address for Password Request" id="rp-validation-subject" name="pp_email_templates_setting[rp-validation-subject]" class="regular-text"><p class="desc"><strong>Email template for validation email subject. Use <code>{site_name}</code> to replace with site name.</strong></p></td></tr><tr><th scope="row"><label for="rp-validation-body">Email Body</label></th><td><textarea id="rp-validation-body" name="pp_email_templates_setting[rp-validation-body]" class="regular-text">Hello,
Thank you for requesting access to our protected content. To proceed, we need to verify your email address.
                                                                                                   Please click the link below to validate your email:
{validation_link}
If you did not make this request, you can ignore this email.
                                                      Best regards,
{site_name}</textarea><p class="desc"><strong>Email template for validation email body. Use <code>{site_name}</code> to replace with site name. use <code>{validation_link}</code> to replace with validation link.</strong></p></td></tr></tbody></table></div>
                </div>';
					break;
			}
		}
        echo '</div>
        </div>';
	}

	public function cache_related_issue() {
		echo '<form action="options.php" method="post">';
		do_settings_sections( 'password-protected&tab=advanced&sub-tab=cache-issue' );
		settings_fields( 'password_protected_cache_issue' );
		submit_button();
		echo '</form>';
	}

}
