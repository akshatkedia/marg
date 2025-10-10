<?php
/**
 * ACF JSON Sync Script
 *
 * This script can be run to manually sync ACF field groups between JSON files and database.
 * Run this from the WordPress admin or via WP-CLI.
 *
 * Usage from browser: Navigate to /wp-admin/admin-ajax.php?action=acf_manual_sync&sync_key=marg2024
 * Usage from WP-CLI: wp eval-file wp-content/themes/marg/acf-sync-now.php
 */

// Check if we're in WordPress environment
if ( ! defined( 'ABSPATH' ) ) {
	// Try to load WordPress
	$wp_load_paths = array(
		__DIR__ . '/../../../wp-load.php',
		__DIR__ . '/../../../../wp-load.php',
		__DIR__ . '/../../../../../wp-load.php',
	);

	$wp_loaded = false;
	foreach ( $wp_load_paths as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			$wp_loaded = true;
			break;
		}
	}

	if ( ! $wp_loaded ) {
		die( 'Error: Could not load WordPress. Please run this script from within WordPress environment.' );
	}
}

// Check if ACF is active
if ( ! class_exists( 'ACF' ) ) {
	die( 'Error: Advanced Custom Fields plugin is not active.' );
}

/**
 * Perform ACF JSON sync
 */
function perform_acf_json_sync() {
	$results = array(
		'imported' => 0,
		'updated'  => 0,
		'skipped'  => 0,
		'errors'   => array(),
	);

	$json_path = get_stylesheet_directory() . '/acf-json';

	if ( ! file_exists( $json_path ) ) {
		return array( 'error' => 'ACF JSON directory not found at: ' . $json_path );
	}

	$json_files = glob( $json_path . '/group_*.json' );

	if ( empty( $json_files ) ) {
		return array( 'error' => 'No ACF JSON files found in: ' . $json_path );
	}

	echo "Found " . count( $json_files ) . " ACF JSON files to process.\n\n";

	foreach ( $json_files as $json_file ) {
		$filename = basename( $json_file );
		echo "Processing: $filename\n";

		$json_content = file_get_contents( $json_file );
		$json_data = json_decode( $json_content, true );

		if ( ! $json_data || ! isset( $json_data['key'] ) ) {
			$results['errors'][] = "Invalid JSON in file: $filename";
			echo "  ✗ Error: Invalid JSON structure\n";
			continue;
		}

		$field_group_key = $json_data['key'];
		$field_group_title = $json_data['title'] ?? 'Unknown';

		// Check if field group exists in database
		$existing_group = acf_get_field_group( $field_group_key );

		// Check if update is needed
		$needs_update = false;
		if ( ! $existing_group ) {
			$needs_update = true;
			echo "  → New field group to import: $field_group_title\n";
		} else {
			// Compare modified timestamps
			$json_modified = $json_data['modified'] ?? 0;
			$db_modified = get_post_meta( $existing_group['ID'], '_acf_modified', true );

			if ( $json_modified > $db_modified ) {
				$needs_update = true;
				echo "  → Field group needs update: $field_group_title\n";
				echo "    JSON modified: " . date( 'Y-m-d H:i:s', $json_modified ) . "\n";
				echo "    DB modified: " . ( $db_modified ? date( 'Y-m-d H:i:s', $db_modified ) : 'Unknown' ) . "\n";
			} else {
				$results['skipped']++;
				echo "  ✓ Already up to date: $field_group_title\n";
				continue;
			}
		}

		if ( $needs_update ) {
			// Import the field group
			$imported_group = acf_import_field_group( $json_data );

			if ( $imported_group ) {
				if ( ! $existing_group ) {
					$results['imported']++;
					echo "  ✓ Successfully imported: $field_group_title\n";
				} else {
					$results['updated']++;
					echo "  ✓ Successfully updated: $field_group_title\n";
				}

				// Update the modified timestamp
				if ( isset( $json_data['modified'] ) ) {
					update_post_meta( $imported_group['ID'], '_acf_modified', $json_data['modified'] );
				}
			} else {
				$results['errors'][] = "Failed to import/update: $field_group_title";
				echo "  ✗ Error: Failed to import/update\n";
			}
		}

		echo "\n";
	}

	// Clear ACF cache
	if ( function_exists( 'acf_get_cache' ) ) {
		acf_get_cache()->clear();
		echo "ACF cache cleared.\n\n";
	}

	return $results;
}

/**
 * Display results
 */
function display_sync_results( $results ) {
	echo "========================================\n";
	echo "ACF JSON Sync Results:\n";
	echo "========================================\n";

	if ( isset( $results['error'] ) ) {
		echo "Error: " . $results['error'] . "\n";
		return;
	}

	echo "✓ Imported: " . $results['imported'] . " new field groups\n";
	echo "✓ Updated: " . $results['updated'] . " existing field groups\n";
	echo "○ Skipped: " . $results['skipped'] . " up-to-date field groups\n";

	if ( ! empty( $results['errors'] ) ) {
		echo "\n✗ Errors encountered:\n";
		foreach ( $results['errors'] as $error ) {
			echo "  - $error\n";
		}
	}

	$total_processed = $results['imported'] + $results['updated'] + $results['skipped'];
	echo "\nTotal processed: $total_processed field groups\n";
	echo "========================================\n";
}

// Check if this is an AJAX request
if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_GET['action'] ) && $_GET['action'] === 'acf_manual_sync' ) {
	// Simple security check
	if ( ! isset( $_GET['sync_key'] ) || $_GET['sync_key'] !== 'marg2024' ) {
		wp_die( 'Invalid sync key' );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to perform this action.' );
	}

	header( 'Content-Type: text/plain' );
	echo "Starting ACF JSON Sync...\n";
	echo "========================================\n\n";

	$results = perform_acf_json_sync();
	display_sync_results( $results );

	die();
}

// If running from WP-CLI or direct PHP
if ( defined( 'WP_CLI' ) || php_sapi_name() === 'cli' ) {
	echo "Starting ACF JSON Sync...\n";
	echo "========================================\n\n";

	$results = perform_acf_json_sync();
	display_sync_results( $results );
}

// Add AJAX handler if in WordPress context
if ( defined( 'ABSPATH' ) && ! defined( 'WP_CLI' ) ) {
	add_action( 'wp_ajax_acf_manual_sync', function() {
		// Simple security check
		if ( ! isset( $_GET['sync_key'] ) || $_GET['sync_key'] !== 'marg2024' ) {
			wp_die( 'Invalid sync key' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to perform this action.' );
		}

		header( 'Content-Type: text/plain' );
		echo "Starting ACF JSON Sync...\n";
		echo "========================================\n\n";

		$results = perform_acf_json_sync();
		display_sync_results( $results );

		die();
	});
}