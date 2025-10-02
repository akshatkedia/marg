<?php
/**
 * Admin Menu Customizer
 *
 * Reorganizes the WordPress admin menu by grouping content-related items
 * under a single "Content" menu.
 *
 * @package TenUpTheme
 */

namespace TenUpTheme;

/**
 * AdminMenuCustomizer class
 */
class AdminMenuCustomizer {

	/**
	 * Initialize the admin menu customizations
	 *
	 * @return void
	 */
	public function init() {
		// Hook into admin_menu with high priority to ensure all menus are registered
		add_action( 'admin_menu', array( $this, 'reorganize_admin_menu' ), 999 );
		add_action( 'admin_menu', array( $this, 'create_content_menu' ), 998 );
		add_action( 'admin_menu', array( $this, 'create_site_settings_menu' ), 998 );
		add_action( 'admin_menu', array( $this, 'create_advanced_menu' ), 998 );
		add_action( 'admin_menu', array( $this, 'reorganize_settings_menu' ), 999 );
		add_action( 'admin_menu', array( $this, 'reorganize_advanced_menu' ), 999 );

		// Adjust parent file for proper highlighting
		add_filter( 'parent_file', array( $this, 'adjust_parent_file' ) );
	}

	/**
	 * Create the main Content menu
	 *
	 * @return void
	 */
	public function create_content_menu() {
		add_menu_page(
			'Content',                    // Page title
			'Content',                    // Menu title
			'edit_posts',                 // Capability
			'content-menu',               // Menu slug
			'',                          // Function (empty as this is just a parent)
			'dashicons-admin-page',       // Icon
			2                            // Position (after Dashboard)
		);
	}

	/**
	 * Reorganize the admin menu items
	 *
	 * @return void
	 */
	public function reorganize_admin_menu() {
		global $menu, $submenu;

		// Add Posts as submenu under Content
		add_submenu_page(
			'content-menu',
			'Posts',
			'Posts',
			'edit_posts',
			'edit.php'
		);

		// Add Pages as submenu under Content
		add_submenu_page(
			'content-menu',
			'Pages',
			'Pages',
			'edit_pages',
			'edit.php?post_type=page'
		);

		// Add Products (WooCommerce) as submenu under Content if it exists
		if ( class_exists( 'WooCommerce' ) ) {
			add_submenu_page(
				'content-menu',
				'Products',
				'Products',
				'edit_products',
				'edit.php?post_type=product'
			);
		}

		// Add Events as submenu under Content if post type exists
		if ( post_type_exists( 'event' ) ) {
			add_submenu_page(
				'content-menu',
				'Events',
				'Events',
				'edit_posts',
				'edit.php?post_type=event'
			);
		}

		// Add People as submenu under Content - check multiple possible post type names
		$people_post_types = array( 'people', 'person', 'team', 'staff', 'member' );
		$people_added = false;

		foreach ( $people_post_types as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				add_submenu_page(
					'content-menu',
					'People',
					'People',
					'edit_posts',
					'edit.php?post_type=' . $post_type
				);
				$people_added = true;
				break;
			}
		}

		// If not found by post type, search in the global menu
		if ( ! $people_added && isset( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[0] ) && stripos( $item[0], 'People' ) !== false ) {
					add_submenu_page(
						'content-menu',
						'People',
						'People',
						isset( $item[1] ) ? $item[1] : 'edit_posts',
						isset( $item[2] ) ? $item[2] : 'edit.php?post_type=people'
					);
					break;
				}
			}
		}

		// Alternative: Check for tribe_events (The Events Calendar plugin)
		if ( post_type_exists( 'tribe_events' ) ) {
			add_submenu_page(
				'content-menu',
				'Events',
				'Events',
				'edit_tribe_events',
				'edit.php?post_type=tribe_events'
			);
		}

		// Remove the original menu items
		remove_menu_page( 'edit.php' );                    // Posts
		remove_menu_page( 'edit.php?post_type=page' );     // Pages

		// Remove WooCommerce Products menu if it exists
		if ( class_exists( 'WooCommerce' ) ) {
			remove_menu_page( 'edit.php?post_type=product' );
		}

		// Remove Events menu if it exists
		if ( post_type_exists( 'event' ) ) {
			remove_menu_page( 'edit.php?post_type=event' );
		}

		if ( post_type_exists( 'tribe_events' ) ) {
			remove_menu_page( 'edit.php?post_type=tribe_events' );
		}

		// Remove People menu - check multiple possible post type names
		$people_post_types = array( 'people', 'person', 'team', 'staff', 'member' );
		foreach ( $people_post_types as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				remove_menu_page( 'edit.php?post_type=' . $post_type );
			}
		}

		// Also search and remove from global menu
		if ( isset( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[0] ) && stripos( $item[0], 'People' ) !== false ) {
					unset( $menu[$key] );
					break;
				}
			}
		}

		// Remove the empty parent menu item (first item) from Content submenu
		if ( isset( $submenu['content-menu'] ) && ! empty( $submenu['content-menu'] ) ) {
			unset( $submenu['content-menu'][0] );
		}
	}

	/**
	 * Create the Site Settings menu
	 *
	 * @return void
	 */
	public function create_site_settings_menu() {
		add_menu_page(
			'Site Settings',              // Page title
			'Site Settings',              // Menu title
			'manage_options',             // Capability
			'site-settings-menu',         // Menu slug
			'',                          // Function (empty as this is just a parent)
			'dashicons-admin-generic',   // Icon
			80                           // Position (near bottom)
		);
	}

	/**
	 * Reorganize settings-related menu items
	 *
	 * @return void
	 */
	public function reorganize_settings_menu() {
		global $menu, $submenu;

		// Add Appearance as submenu under Site Settings
		add_submenu_page(
			'site-settings-menu',
			'Appearance',
			'Appearance',
			'switch_themes',
			'themes.php'
		);

		// Add Plugins as submenu under Site Settings
		add_submenu_page(
			'site-settings-menu',
			'Plugins',
			'Plugins',
			'activate_plugins',
			'plugins.php'
		);

		// Add Users as submenu under Site Settings
		add_submenu_page(
			'site-settings-menu',
			'Users',
			'Users',
			'list_users',
			'users.php'
		);

		// Add Tools as submenu under Site Settings
		add_submenu_page(
			'site-settings-menu',
			'Tools',
			'Tools',
			'manage_options',
			'tools.php'
		);

		// Add Settings as submenu under Site Settings
		add_submenu_page(
			'site-settings-menu',
			'Settings',
			'Settings',
			'manage_options',
			'options-general.php'
		);

		// Remove the original menu items
		remove_menu_page( 'themes.php' );           // Appearance
		remove_menu_page( 'plugins.php' );          // Plugins
		remove_menu_page( 'users.php' );            // Users
		remove_menu_page( 'tools.php' );            // Tools
		remove_menu_page( 'options-general.php' );  // Settings

		// Remove the empty parent menu item (first item) from Site Settings submenu
		if ( isset( $submenu['site-settings-menu'] ) && ! empty( $submenu['site-settings-menu'] ) ) {
			unset( $submenu['site-settings-menu'][0] );
		}
	}

	/**
	 * Create the Advanced menu
	 *
	 * @return void
	 */
	public function create_advanced_menu() {
		add_menu_page(
			'Advanced',                  // Page title
			'Advanced',                  // Menu title
			'manage_options',           // Capability
			'advanced-menu',            // Menu slug
			'',                        // Function (empty as this is just a parent)
			'dashicons-admin-tools',   // Icon
			90                         // Position (near bottom)
		);
	}

	/**
	 * Reorganize advanced/plugin menu items
	 *
	 * @return void
	 */
	public function reorganize_advanced_menu() {
		global $menu, $submenu;

		// Add WooCommerce as submenu under Advanced
		if ( class_exists( 'WooCommerce' ) ) {
			add_submenu_page(
				'advanced-menu',
				'WooCommerce',
				'WooCommerce',
				'manage_woocommerce',
				'woocommerce'
			);
			remove_menu_page( 'woocommerce' );
		}

		// Add Analytics (if WooCommerce Analytics exists)
		if ( isset( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[0] ) && strpos( $item[0], 'Analytics' ) !== false ) {
					add_submenu_page(
						'advanced-menu',
						'Analytics',
						'Analytics',
						$item[1],
						$item[2]
					);
					remove_menu_page( $item[2] );
				}
			}
		}

		// Add Marketing (WooCommerce Marketing)
		if ( function_exists( 'wc_admin_is_registered_page' ) ) {
			add_submenu_page(
				'advanced-menu',
				'Marketing',
				'Marketing',
				'manage_woocommerce',
				'wc-admin&path=/marketing'
			);
			remove_menu_page( 'woocommerce-marketing' );
		}

		// Add ACF (Advanced Custom Fields)
		if ( class_exists( 'ACF' ) ) {
			add_submenu_page(
				'advanced-menu',
				'ACF',
				'ACF',
				'manage_options',
				'edit.php?post_type=acf-field-group'
			);
			remove_menu_page( 'edit.php?post_type=acf-field-group' );
		}

		// Add FileBird
		add_submenu_page(
			'advanced-menu',
			'FileBird',
			'FileBird',
			'manage_options',
			'filebird-settings'
		);
		remove_menu_page( 'filebird-settings' );

		// Add Payments (WooCommerce Payments if exists)
		// Try multiple possible menu slugs for Payments
		$payment_slugs = array(
			'wc-admin&path=/payments/overview',
			'wc-admin&path=/payments',
			'wcpay-overview',
			'wc-payments'
		);

		foreach ( $payment_slugs as $slug ) {
			remove_menu_page( $slug );
		}

		// Also check in the global menu array for any menu with "Payments" in the title
		if ( isset( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[0] ) && ( strpos( $item[0], 'Payments' ) !== false || strpos( $item[0], 'payments' ) !== false ) ) {
					add_submenu_page(
						'advanced-menu',
						'Payments',
						'Payments',
						isset( $item[1] ) ? $item[1] : 'manage_woocommerce',
						isset( $item[2] ) ? $item[2] : 'wc-admin&path=/payments'
					);
					unset( $menu[$key] );
					break;
				}
			}
		}

		// Add Code Snippets - check multiple possible menu slugs
		$snippets_slugs = array( 'snippets', 'code-snippets', 'edit-snippets' );
		$snippets_added = false;

		// Try to add by class existence first
		if ( class_exists( 'Code_Snippets' ) ) {
			add_submenu_page(
				'advanced-menu',
				'Snippets',
				'Snippets',
				'manage_options',
				'snippets'
			);
			$snippets_added = true;
		}

		// Remove all possible snippets menu slugs
		foreach ( $snippets_slugs as $slug ) {
			remove_menu_page( $slug );
		}

		// Also check in the global menu array for any menu with "Snippets" in the title
		if ( isset( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[0] ) && stripos( $item[0], 'Snippets' ) !== false ) {
					if ( ! $snippets_added ) {
						add_submenu_page(
							'advanced-menu',
							'Snippets',
							'Snippets',
							isset( $item[1] ) ? $item[1] : 'manage_options',
							isset( $item[2] ) ? $item[2] : 'snippets'
						);
					}
					unset( $menu[$key] );
					break;
				}
			}
		}

		// Add AltText.ai - check multiple possible menu slugs
		$alttext_slugs = array( 'atai', 'alttext', 'alttext-ai', 'alt-text-ai' );
		$alttext_added = false;

		// Remove all possible AltText.ai menu slugs
		foreach ( $alttext_slugs as $slug ) {
			remove_menu_page( $slug );
		}

		// Also check in the global menu array for any menu with "AltText" or "Alt Text" in the title
		if ( isset( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[0] ) && ( stripos( $item[0], 'AltText' ) !== false || stripos( $item[0], 'Alt Text' ) !== false || stripos( $item[0], 'atai' ) !== false ) ) {
					add_submenu_page(
						'advanced-menu',
						'AltText.ai',
						'AltText.ai',
						isset( $item[1] ) ? $item[1] : 'manage_options',
						isset( $item[2] ) ? $item[2] : 'atai'
					);
					unset( $menu[$key] );
					$alttext_added = true;
					break;
				}
			}
		}

		// If not found yet, add it with the known slug
		if ( ! $alttext_added ) {
			add_submenu_page(
				'advanced-menu',
				'AltText.ai',
				'AltText.ai',
				'manage_options',
				'atai'
			);
		}

		// Add WP Synchro
		add_submenu_page(
			'advanced-menu',
			'WP Synchro',
			'WP Synchro',
			'manage_options',
			'wpsynchro_menu'
		);
		remove_menu_page( 'wpsynchro_menu' );

		// Add WP All Export - check multiple possible menu slugs
		$export_slugs = array( 'pmxe-admin-export', 'pmxe-admin-manage', 'pmxe-admin-home' );
		$export_added = false;

		if ( class_exists( 'PMXE_Plugin' ) ) {
			add_submenu_page(
				'advanced-menu',
				'All Export',
				'All Export',
				'manage_options',
				'pmxe-admin-export'
			);
			$export_added = true;
		}

		// Remove all possible export menu slugs
		foreach ( $export_slugs as $slug ) {
			remove_menu_page( $slug );
		}

		// Also check in the global menu array for any menu with "Export" in the title
		if ( isset( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[0] ) && ( stripos( $item[0], 'All Export' ) !== false || ( stripos( $item[0], 'Export' ) !== false && stripos( $item[2], 'pmxe' ) !== false ) ) ) {
					if ( ! $export_added ) {
						add_submenu_page(
							'advanced-menu',
							'All Export',
							'All Export',
							isset( $item[1] ) ? $item[1] : 'manage_options',
							isset( $item[2] ) ? $item[2] : 'pmxe-admin-export'
						);
					}
					unset( $menu[$key] );
					break;
				}
			}
		}

		// Add WP All Import
		if ( class_exists( 'PMXI_Plugin' ) ) {
			add_submenu_page(
				'advanced-menu',
				'All Import',
				'All Import',
				'manage_options',
				'pmxi-admin-home'
			);
			remove_menu_page( 'pmxi-admin-home' );
		}

		// Check for any remaining plugin menus and move them
		$plugin_keywords = array( 'Marketing', 'Analytics', 'Payments', 'Snippets', 'AltText', 'Alt Text', 'All Export', 'All Import' );

		if ( isset( $menu ) && is_array( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( ! isset( $item[0] ) || ! isset( $item[2] ) ) {
					continue;
				}

				// Skip core WordPress menus we've already handled
				$core_menus = array( 'index.php', 'upload.php', 'edit-comments.php', 'profile.php' );
				if ( in_array( $item[2], $core_menus, true ) ) {
					continue;
				}

				// Check if this menu item matches any of our plugin keywords
				foreach ( $plugin_keywords as $keyword ) {
					if ( stripos( $item[0], $keyword ) !== false ) {
						add_submenu_page(
							'advanced-menu',
							$item[0],
							$item[0],
							$item[1],
							$item[2]
						);
						unset( $menu[$key] );
						break;
					}
				}
			}
		}

		// Remove the empty parent menu item (first item) from Advanced submenu
		if ( isset( $submenu['advanced-menu'] ) && ! empty( $submenu['advanced-menu'] ) ) {
			unset( $submenu['advanced-menu'][0] );
		}
	}

	/**
	 * Adjust parent file for proper menu highlighting
	 *
	 * @param string $parent_file The parent file.
	 * @return string
	 */
	public function adjust_parent_file( $parent_file ) {
		global $current_screen;

		// Check if we're on one of our content types
		$content_types = array( 'post', 'page', 'product', 'event', 'tribe_events', 'people', 'person', 'team', 'staff', 'member' );

		if ( isset( $current_screen->post_type ) && in_array( $current_screen->post_type, $content_types, true ) ) {
			return 'content-menu';
		}

		// Also check for the base edit.php without post type (Posts)
		if ( $parent_file === 'edit.php' ) {
			return 'content-menu';
		}

		// Check if we're on one of the settings pages
		$settings_pages = array( 'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php' );

		if ( in_array( $parent_file, $settings_pages, true ) ) {
			return 'site-settings-menu';
		}

		// Check if we're on one of the advanced/plugin pages
		$advanced_pages = array(
			'woocommerce',
			'woocommerce-marketing',
			'edit.php?post_type=acf-field-group',
			'filebird-settings',
			'snippets',
			'code-snippets',
			'edit-snippets',
			'atai',
			'alttext',
			'alttext-ai',
			'alt-text-ai',
			'wpsynchro_menu',
			'pmxe-admin-export',
			'pmxe-admin-manage',
			'pmxe-admin-home',
			'pmxi-admin-home',
			'wc-admin&path=/payments/overview',
			'wc-admin&path=/payments',
			'wcpay-overview',
			'wc-payments'
		);

		if ( in_array( $parent_file, $advanced_pages, true ) ) {
			return 'advanced-menu';
		}

		// Check if parent file contains payments
		if ( strpos( $parent_file, 'payments' ) !== false || strpos( $parent_file, 'Payments' ) !== false ) {
			return 'advanced-menu';
		}

		// Check for WooCommerce admin pages
		if ( isset( $current_screen->id ) && strpos( $current_screen->id, 'woocommerce' ) !== false ) {
			return 'advanced-menu';
		}

		return $parent_file;
	}
}