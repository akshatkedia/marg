<?php
/**
 * ACF JSON Sync Utility
 *
 * Synchronizes ACF field groups between JSON files and database
 *
 * @package TenUpTheme
 */

namespace TenUpTheme;

/**
 * ACFSync class
 */
class ACFSync {

	/**
	 * Initialize ACF JSON sync functionality
	 *
	 * @return void
	 */
	public function init() {
		// Set custom ACF JSON save and load points
		add_filter( 'acf/settings/save_json', array( $this, 'set_acf_json_save_point' ) );
		add_filter( 'acf/settings/load_json', array( $this, 'set_acf_json_load_point' ) );

		// Add admin notice for sync status
		add_action( 'admin_notices', array( $this, 'display_sync_status' ) );

		// Add sync action to admin bar for easy access
		add_action( 'admin_bar_menu', array( $this, 'add_sync_to_admin_bar' ), 100 );

		// Handle sync action
		add_action( 'admin_init', array( $this, 'handle_sync_action' ) );

		// Auto-sync on theme activation
		add_action( 'after_switch_theme', array( $this, 'auto_sync_on_activation' ) );
	}

	/**
	 * Set ACF JSON save point
	 *
	 * @param string $path The default save path.
	 * @return string
	 */
	public function set_acf_json_save_point( $path ) {
		// Update path to save JSON in theme's acf-json folder
		$path = get_stylesheet_directory() . '/acf-json';
		return $path;
	}

	/**
	 * Set ACF JSON load point
	 *
	 * @param array $paths The default load paths.
	 * @return array
	 */
	public function set_acf_json_load_point( $paths ) {
		// Remove original path (optional)
		unset( $paths[0] );

		// Append path to load JSON from theme's acf-json folder
		$paths[] = get_stylesheet_directory() . '/acf-json';

		return $paths;
	}

	/**
	 * Display sync status in admin notices
	 *
	 * @return void
	 */
	public function display_sync_status() {
		// Only show on ACF Field Groups page
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'edit-acf-field-group' ) {
			return;
		}

		$groups_needing_sync = $this->get_groups_needing_sync();

		if ( ! empty( $groups_needing_sync ) ) {
			$count = count( $groups_needing_sync );
			?>
			<div class="notice notice-warning">
				<p>
					<strong>ACF Sync Required:</strong>
					<?php echo esc_html( $count ); ?> field group(s) need to be synchronized.
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=acf-field-group&acf_sync_all=1' ), 'acf_sync_all' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
						Sync All Field Groups
					</a>
				</p>
				<details style="margin-top: 10px;">
					<summary>View field groups needing sync</summary>
					<ul style="margin-top: 10px; margin-left: 20px;">
						<?php foreach ( $groups_needing_sync as $group ) : ?>
							<li><?php echo esc_html( $group['title'] ); ?> (<?php echo esc_html( $group['key'] ); ?>)</li>
						<?php endforeach; ?>
					</ul>
				</details>
			</div>
			<?php
		} else {
			?>
			<div class="notice notice-success">
				<p><strong>ACF Sync:</strong> All field groups are synchronized.</p>
			</div>
			<?php
		}
	}

	/**
	 * Get field groups that need syncing
	 *
	 * @return array
	 */
	private function get_groups_needing_sync() {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return array();
		}

		$groups_needing_sync = array();
		$json_path = get_stylesheet_directory() . '/acf-json';

		// Get all JSON files
		$json_files = glob( $json_path . '/group_*.json' );

		if ( empty( $json_files ) ) {
			return array();
		}

		foreach ( $json_files as $json_file ) {
			$json_data = json_decode( file_get_contents( $json_file ), true );

			if ( ! $json_data || ! isset( $json_data['key'] ) ) {
				continue;
			}

			// Check if group exists in database
			$db_group = acf_get_field_group( $json_data['key'] );

			if ( ! $db_group ) {
				// Group doesn't exist in DB, needs to be imported
				$groups_needing_sync[] = array(
					'key'    => $json_data['key'],
					'title'  => $json_data['title'] ?? 'Unknown',
					'status' => 'import_needed',
					'file'   => basename( $json_file ),
				);
			} else {
				// Compare modified times
				$json_modified = $json_data['modified'] ?? 0;
				$db_modified = get_post_meta( $db_group['ID'], '_acf_modified', true );

				if ( $json_modified > $db_modified ) {
					$groups_needing_sync[] = array(
						'key'    => $json_data['key'],
						'title'  => $json_data['title'] ?? 'Unknown',
						'status' => 'update_needed',
						'file'   => basename( $json_file ),
					);
				}
			}
		}

		return $groups_needing_sync;
	}

	/**
	 * Add sync option to admin bar
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin bar object.
	 * @return void
	 */
	public function add_sync_to_admin_bar( $admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$groups_needing_sync = $this->get_groups_needing_sync();
		$count = count( $groups_needing_sync );

		if ( $count > 0 ) {
			$admin_bar->add_node( array(
				'id'    => 'acf-sync',
				'title' => '<span style="color: #f0ad4e;">ACF Sync (' . $count . ')</span>',
				'href'  => wp_nonce_url( admin_url( 'edit.php?post_type=acf-field-group&acf_sync_all=1' ), 'acf_sync_all' ),
				'meta'  => array(
					'title' => 'Sync ACF Field Groups',
				),
			) );
		}
	}

	/**
	 * Handle sync action
	 *
	 * @return void
	 */
	public function handle_sync_action() {
		if ( ! isset( $_GET['acf_sync_all'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Verify nonce
		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'acf_sync_all' ) ) {
			wp_die( 'Security check failed' );
		}

		$this->sync_all_field_groups();

		// Redirect back with success message
		wp_redirect( admin_url( 'edit.php?post_type=acf-field-group&acf_synced=1' ) );
		exit;
	}

	/**
	 * Sync all field groups from JSON to database
	 *
	 * @return array Results of the sync operation
	 */
	public function sync_all_field_groups() {
		if ( ! function_exists( 'acf_import_field_group' ) ) {
			return array( 'error' => 'ACF not available' );
		}

		$results = array(
			'imported' => 0,
			'updated'  => 0,
			'errors'   => 0,
		);

		$json_path = get_stylesheet_directory() . '/acf-json';
		$json_files = glob( $json_path . '/group_*.json' );

		if ( empty( $json_files ) ) {
			return $results;
		}

		foreach ( $json_files as $json_file ) {
			$json_data = json_decode( file_get_contents( $json_file ), true );

			if ( ! $json_data || ! isset( $json_data['key'] ) ) {
				$results['errors']++;
				continue;
			}

			// Check if group exists
			$existing_group = acf_get_field_group( $json_data['key'] );

			// Import/update the field group
			$field_group = acf_import_field_group( $json_data );

			if ( $field_group ) {
				if ( ! $existing_group ) {
					$results['imported']++;
				} else {
					$results['updated']++;
				}

				// Update the modified timestamp
				if ( isset( $json_data['modified'] ) ) {
					update_post_meta( $field_group['ID'], '_acf_modified', $json_data['modified'] );
				}
			} else {
				$results['errors']++;
			}
		}

		// Clear ACF cache
		if ( function_exists( 'acf_get_cache' ) ) {
			acf_get_cache()->clear();
		}

		return $results;
	}

	/**
	 * Auto-sync on theme activation
	 *
	 * @return void
	 */
	public function auto_sync_on_activation() {
		$this->sync_all_field_groups();
	}

	/**
	 * Export all field groups to JSON
	 * This ensures JSON files are up to date with database
	 *
	 * @return array Results of the export operation
	 */
	public function export_all_field_groups() {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return array( 'error' => 'ACF not available' );
		}

		$results = array(
			'exported' => 0,
			'errors'   => 0,
		);

		$field_groups = acf_get_field_groups();
		$json_path = get_stylesheet_directory() . '/acf-json';

		// Create directory if it doesn't exist
		if ( ! file_exists( $json_path ) ) {
			wp_mkdir_p( $json_path );
		}

		foreach ( $field_groups as $group ) {
			// Get full field group data
			$field_group = acf_get_field_group( $group['key'] );

			if ( ! $field_group ) {
				$results['errors']++;
				continue;
			}

			// Get fields for this group
			$fields = acf_get_fields( $field_group );

			// Prepare for export
			$field_group['fields'] = $fields;
			$field_group['modified'] = time();

			// Prepare the export data
			$field_group = acf_prepare_field_group_for_export( $field_group );

			// Write to JSON file
			$file_path = $json_path . '/' . $field_group['key'] . '.json';
			$json_content = wp_json_encode( $field_group, JSON_PRETTY_PRINT );

			if ( file_put_contents( $file_path, $json_content ) !== false ) {
				$results['exported']++;
			} else {
				$results['errors']++;
			}
		}

		return $results;
	}
}