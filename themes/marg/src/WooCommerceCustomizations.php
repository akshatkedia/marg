<?php
/**
 * WooCommerce Customizations
 *
 * Customizes WooCommerce functionality including:
 * - Removing grouped and external/affiliate product types
 * - Removing upsells functionality
 *
 * @package TenUpTheme
 */

namespace TenUpTheme;

/**
 * WooCommerceCustomizations class
 */
class WooCommerceCustomizations {

	/**
	 * Initialize the WooCommerce customizations
	 *
	 * @return void
	 */
	public function init() {
		// Remove unwanted product types from selector
		add_filter( 'woocommerce_product_type_selector', array( $this, 'remove_unwanted_product_types' ) );

		// Remove unwanted product type options
		add_filter( 'product_type_options', array( $this, 'remove_unwanted_product_options' ) );

		// Add admin styles to hide unwanted product elements
		add_action( 'admin_head', array( $this, 'hide_unwanted_product_elements' ) );

		// Remove unwanted from product type query
		add_filter( 'woocommerce_product_type_query', array( $this, 'filter_product_type_query' ), 10, 2 );

		// Prevent unwanted products in REST API (optional - disabled by default)
		// add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'prevent_unwanted_in_api' ), 10, 3 );

		// Remove unwanted product data tabs
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'remove_unwanted_product_data_tabs' ) );

		// Add JavaScript to remove unwanted options from any dynamic interfaces
		add_action( 'admin_footer', array( $this, 'remove_unwanted_product_js' ) );

		// Remove upsells functionality
		$this->remove_upsells_functionality();
	}

	/**
	 * Remove unwanted product types from the product type selector dropdown
	 *
	 * @param array $types Product types array.
	 * @return array
	 */
	public function remove_unwanted_product_types( $types ) {
		// Remove grouped product type
		if ( isset( $types['grouped'] ) ) {
			unset( $types['grouped'] );
		}

		// Remove external/affiliate product type
		if ( isset( $types['external'] ) ) {
			unset( $types['external'] );
		}

		return $types;
	}

	/**
	 * Remove unwanted product type options from product data metabox
	 *
	 * @param array $options Product type options.
	 * @return array
	 */
	public function remove_unwanted_product_options( $options ) {
		// Remove external-specific options
		if ( isset( $options['external_url'] ) ) {
			unset( $options['external_url'] );
		}

		return $options;
	}

	/**
	 * Hide unwanted product elements via CSS
	 *
	 * @return void
	 */
	public function hide_unwanted_product_elements() {
		// Only on product edit pages
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		?>
		<style type="text/css">
			/* Hide grouped product option in type selector if it somehow still appears */
			#product-type option[value="grouped"],
			#product-type option[value="external"] {
				display: none !important;
			}

			/* Hide grouped product data tab */
			.product_data_tabs .grouped_tab,
			#grouped_product_data {
				display: none !important;
			}

			/* Hide external/affiliate product data tab */
			.product_data_tabs .external_tab,
			#external_product_data {
				display: none !important;
			}

			/* Hide grouped and external products in any filters or lists */
			.subsubsub li.grouped,
			.subsubsub li.external {
				display: none !important;
			}

			/* Hide grouped and external product options in bulk edit */
			.inline-edit-product select[name="_product_type"] option[value="grouped"],
			.inline-edit-product select[name="_product_type"] option[value="external"] {
				display: none !important;
			}

			/* Hide grouped and external option in quick edit */
			.quick-edit-row select[name="_product_type"] option[value="grouped"],
			.quick-edit-row select[name="_product_type"] option[value="external"] {
				display: none !important;
			}

			/* Hide external product URL and button text fields */
			.show_if_external {
				display: none !important;
			}

			/* Hide upsells field in Linked Products tab */
			.options_group .form-field._upsell_ids_field,
			label[for="upsell_ids"],
			#upsell_ids,
			.form-field._upsell_ids_field {
				display: none !important;
			}

			/* Hide upsells section on single product pages */
			.up-sells.upsells.products,
			.upsells.products,
			section.up-sells {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Remove unwanted product data tabs
	 *
	 * @param array $tabs Product data tabs.
	 * @return array
	 */
	public function remove_unwanted_product_data_tabs( $tabs ) {
		// Remove the grouped tab
		if ( isset( $tabs['grouped'] ) ) {
			unset( $tabs['grouped'] );
		}

		// Remove the external/affiliate tab
		if ( isset( $tabs['external'] ) ) {
			unset( $tabs['external'] );
		}

		return $tabs;
	}

	/**
	 * Filter product type query to exclude unwanted products
	 *
	 * @param array  $args Query arguments.
	 * @param string $type Query type.
	 * @return array
	 */
	public function filter_product_type_query( $args, $type = '' ) {
		// If querying product types, exclude grouped and external
		if ( isset( $args['type'] ) ) {
			if ( $args['type'] === 'grouped' || $args['type'] === 'external' ) {
				$args['type'] = 'simple'; // Fallback to simple
			}
		}

		return $args;
	}

	/**
	 * Add JavaScript to remove unwanted options from dynamic interfaces
	 *
	 * @return void
	 */
	public function remove_unwanted_product_js() {
		// Only on product edit pages
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Remove grouped and external options from product type selector
			$('#product-type option[value="grouped"]').remove();
			$('#product-type option[value="external"]').remove();

			// Remove grouped tab if it exists
			$('.product_data_tabs .grouped_tab').remove();
			$('#grouped_product_data').remove();

			// Remove external tab if it exists
			$('.product_data_tabs .external_tab').remove();
			$('#external_product_data').remove();

			// Remove from any dynamically created selects
			$(document).on('DOMNodeInserted', function(e) {
				if ($(e.target).is('select')) {
					$(e.target).find('option[value="grouped"]').remove();
					$(e.target).find('option[value="external"]').remove();
				}
			});

			// Handle WooCommerce product type change event
			$('#product-type').on('change', function() {
				if ($(this).val() === 'grouped' || $(this).val() === 'external') {
					$(this).val('simple').trigger('change');
				}
			});

			// Remove grouped and external from bulk edit and quick edit
			$('.inline-edit-product select[name="_product_type"] option[value="grouped"]').remove();
			$('.inline-edit-product select[name="_product_type"] option[value="external"]').remove();
			$('.quick-edit-row select[name="_product_type"] option[value="grouped"]').remove();
			$('.quick-edit-row select[name="_product_type"] option[value="external"]').remove();

			// Hide external product fields
			$('.show_if_external').hide();

			// Hide upsells field in Linked Products tab
			$('.form-field._upsell_ids_field').hide();
			$('label[for="upsell_ids"]').parent().hide();
			$('#upsell_ids').closest('.form-field').hide();

			// Remove upsells select2 if it exists
			if ($('#upsell_ids').length) {
				$('#upsell_ids').select2('destroy').hide();
			}

			// Hide upsells on any tab change
			$('.product_data_tabs').on('click', 'li', function() {
				setTimeout(function() {
					$('.form-field._upsell_ids_field').hide();
					$('label[for="upsell_ids"]').parent().hide();
					$('#upsell_ids').closest('.form-field').hide();
				}, 100);
			});
		});
		</script>
		<?php
	}

	/**
	 * Optional: Prevent unwanted products in REST API
	 * Uncomment the add_filter line in init() to enable this
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WC_Product       $product  Product object.
	 * @param \WP_REST_Request  $request  Request object.
	 * @return \WP_REST_Response
	 */
	public function prevent_unwanted_in_api( $response, $product, $request ) {
		// If creating/updating a product
		if ( in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$data = $response->get_data();

			// If type is grouped or external, change to simple
			if ( isset( $data['type'] ) && in_array( $data['type'], array( 'grouped', 'external' ), true ) ) {
				$data['type'] = 'simple';
				$response->set_data( $data );
			}
		}

		return $response;
	}

	/**
	 * Convert existing unwanted products to simple products
	 * This is a utility method that can be called manually if needed
	 *
	 * @param string $type Product type to convert ('grouped' or 'external' or 'all').
	 * @return array Results of the conversion
	 */
	public function convert_unwanted_to_simple( $type = 'all' ) {
		$results = array(
			'converted' => 0,
			'errors' => 0,
			'products' => array(),
		);

		$types_to_convert = array();

		// Determine which types to convert
		if ( 'all' === $type ) {
			$types_to_convert = array( 'grouped', 'external' );
		} elseif ( in_array( $type, array( 'grouped', 'external' ), true ) ) {
			$types_to_convert = array( $type );
		} else {
			return $results; // Invalid type specified
		}

		// Process each type
		foreach ( $types_to_convert as $product_type ) {
			// Get all products of the specified type
			$args = array(
				'type'   => $product_type,
				'limit'  => -1,
				'return' => 'objects',
			);

			$products = wc_get_products( $args );

			foreach ( $products as $product ) {
				try {
					// Convert to simple product
					$product_id = $product->get_id();
					wp_set_object_terms( $product_id, 'simple', 'product_type' );

					// Clear type-specific meta
					if ( 'grouped' === $product_type ) {
						// Clear grouped children
						delete_post_meta( $product_id, '_children' );
					} elseif ( 'external' === $product_type ) {
						// Clear external product meta
						delete_post_meta( $product_id, '_product_url' );
						delete_post_meta( $product_id, '_button_text' );
					}

					$results['converted']++;
					$results['products'][] = array(
						'id'         => $product_id,
						'name'       => $product->get_name(),
						'old_type'   => $product_type,
					);
				} catch ( \Exception $e ) {
					$results['errors']++;
				}
			}
		}

		// Clear product transients
		wc_delete_product_transients();

		return $results;
	}

	/**
	 * Remove upsells functionality from WooCommerce
	 *
	 * @return void
	 */
	public function remove_upsells_functionality() {
		// Remove upsells display from single product pages
		add_action( 'init', function() {
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		}, 20 );

		// Filter to return empty array for upsell IDs
		add_filter( 'woocommerce_product_get_upsell_ids', '__return_empty_array', 999 );
		add_filter( 'woocommerce_product_variation_get_upsell_ids', '__return_empty_array', 999 );

		// Remove upsell IDs when saving product
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'remove_upsell_ids_on_save' ) );

		// Filter REST API to remove upsell_ids
		add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'remove_upsells_from_api' ), 10, 3 );

		// Prevent upsells from being set via REST API
		add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'prevent_upsells_via_api' ), 10, 2 );
	}

	/**
	 * Remove upsell IDs when saving a product
	 *
	 * @param \WC_Product $product Product object.
	 * @return void
	 */
	public function remove_upsell_ids_on_save( $product ) {
		$product->set_upsell_ids( array() );
	}

	/**
	 * Remove upsells from REST API responses
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WC_Product       $product  Product object.
	 * @param \WP_REST_Request  $request  Request object.
	 * @return \WP_REST_Response
	 */
	public function remove_upsells_from_api( $response, $product, $request ) {
		$data = $response->get_data();

		// Remove upsell_ids from response
		if ( isset( $data['upsell_ids'] ) ) {
			$data['upsell_ids'] = array();
		}

		$response->set_data( $data );
		return $response;
	}

	/**
	 * Prevent upsells from being set via REST API
	 *
	 * @param \WC_Product      $product Product object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WC_Product
	 */
	public function prevent_upsells_via_api( $product, $request ) {
		// Clear any upsell_ids that might be set
		$product->set_upsell_ids( array() );
		return $product;
	}

	/**
	 * Utility function to clear all upsell relationships
	 * This can be called manually to remove all existing upsells
	 *
	 * @return array Results of the clearing operation
	 */
	public function clear_all_upsells() {
		$results = array(
			'cleared' => 0,
			'errors'  => 0,
			'products' => array(),
		);

		// Get all products
		$args = array(
			'limit'  => -1,
			'return' => 'objects',
		);

		$products = wc_get_products( $args );

		foreach ( $products as $product ) {
			$upsell_ids = $product->get_upsell_ids();

			// Only process if product has upsells
			if ( ! empty( $upsell_ids ) ) {
				try {
					// Clear upsell IDs
					$product->set_upsell_ids( array() );
					$product->save();

					// Also clear the meta directly
					delete_post_meta( $product->get_id(), '_upsell_ids' );

					$results['cleared']++;
					$results['products'][] = array(
						'id'   => $product->get_id(),
						'name' => $product->get_name(),
						'old_upsell_ids' => $upsell_ids,
					);
				} catch ( \Exception $e ) {
					$results['errors']++;
				}
			}
		}

		// Clear product transients
		wc_delete_product_transients();

		return $results;
	}
}