<?php
/**
 * MooWoodle Synchronization Abilities controller.
 *
 * @package MooWoodle
 */

namespace MooWoodle\Abilities\AbilitiesApi;

use MooWoodle\Util;

defined( 'ABSPATH' ) || exit;

/**
 * MooWoodle Synchronization Abilities controller.
 */
class Synchronization {

	/**
	 * Register synchronization abilities.
	 */
	public function register_abilities() {

		/**
		 * Get synchronization status.
		 */
		wp_register_ability(
			'moowoodle/get-sync-status',
			array(
				'label'       => __( 'Get Sync Status', 'moowoodle' ),
				'description' => __( 'Check the current MooWoodle course synchronization status.', 'moowoodle' ),
				'category'    => 'moowoodle',

				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'type' => array(
							'type'        => 'string',
							'description' => __( 'Synchronization type.', 'moowoodle' ),
							'enum'        => array(
								'course',
							),
							'default'     => 'course',
						),
					),
				),

				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type'        => 'object',
							'description' => __( 'Current course synchronization progress.', 'moowoodle' ),
						),

						'running' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether course synchronization is currently running.', 'moowoodle' ),
						),
					),
				),

				'execute_callback' => array(
					$this,
					'get_sync_status',
				),

				'permission_callback' => array(
					$this,
					'permissions_check',
				),

				'meta' => array(
					'mcp' => array(
						'public' => true,
					),
				),
			)
		);

		/**
		 * Start course synchronization.
		 */
		wp_register_ability(
			'moowoodle/sync-courses',
			array(
				'label'       => __( 'Sync Courses', 'moowoodle' ),
				'description' => __( 'Synchronize Moodle courses and related WooCommerce products with MooWoodle.', 'moowoodle' ),
				'category'    => 'moowoodle',

				'input_schema' => array(
					'type'       => 'object',
				),

				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array(
							'type' => 'boolean',
						),

						'message' => array(
							'type' => 'string',
						),
					),
				),

				'execute_callback' => array(
					$this,
					'sync_courses',
				),

				'permission_callback' => array(
					$this,
					'permissions_check',
				),

				'meta' => array(
					'mcp' => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Check permission.
	 *
	 * @return bool
	 */
	public function permissions_check() {

		return Util::current_user_has_capability(
			array( 'manage_options' )
		);
	}

	/**
	 * Get course synchronization status.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public function get_sync_status( $input ) {

		return array(
			'status'  => Util::get_sync_status( 'course' ),
			'running' => (bool) get_transient( 'course_sync_running' ),
		);
	}

	/**
	 * Synchronize Moodle courses.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public function sync_courses( $input ) {

		try {

			// Flush previous sync status.
			Util::flush_sync_status( 'course' );

			set_transient( 'course_sync_running', true );

			$sync_settings = MooWoodle()->setting->get_setting(
				'sync_course_options',
				array()
			);

			/**
			 * Sync Moodle course categories.
			 */
			if ( in_array( 'sync_courses_category', $sync_settings, true ) ) {

				$response = MooWoodle()->external_service->do_request(
					'get_categories'
				);

				$categories = $response['data'] ?? array();

				Util::set_sync_status(
					array(
						'action' => __( 'Update Course Category', 'moowoodle' ),
						'total'  => count( $categories ),
					),
					'course'
				);

				MooWoodle()->category->update_course_categories(
					$categories
				);

				Util::set_sync_status(
					array(
						'action' => __( 'Update Product Category', 'moowoodle' ),
						'total'  => count( $categories ),
					),
					'course'
				);

				MooWoodle()->category->update_product_categories(
					$categories,
					'product_cat'
				);
			}

			/**
			 * Get courses from Moodle.
			 */
			$response = MooWoodle()->external_service->do_request(
				'get_courses'
			);

			$courses = $response['data'] ?? array();

			/**
			 * Update courses.
			 */
			Util::set_sync_status(
				array(
					'action' => __( 'Update Course', 'moowoodle' ),
					'total'  => max( 0, count( $courses ) - 1 ),
				),
				'course'
			);

			MooWoodle()->course->update_courses(
				$courses
			);

			/**
			 * Update WooCommerce products.
			 */
			MooWoodle()->product->update_products(
				$courses
			);

			/**
			 * Action after course synchronization.
			 */
			do_action( 'moowoodle_after_sync_course' );

			delete_transient( 'course_sync_running' );

			return array(
				'success' => true,
				'message' => __( 'Course synchronization completed successfully.', 'moowoodle' ),
			);

		} catch ( \Exception $e ) {

			delete_transient( 'course_sync_running' );

			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}
}