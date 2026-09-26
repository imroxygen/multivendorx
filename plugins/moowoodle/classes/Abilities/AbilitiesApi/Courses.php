<?php
/**
 * MooWoodle Courses Abilities controller.
 *
 * @package MooWoodle
 */

namespace MooWoodle\Abilities\AbilitiesApi;

use MooWoodle\Util;

defined( 'ABSPATH' ) || exit;

/**
 * MooWoodle Courses Abilities controller.
 */
class Courses {

	/**
	 * Register MooWoodle course abilities.
	 */
	public function register_abilities() {

		wp_register_ability(
			'moowoodle/get-courses',
			array(
				'label'               => __( 'Get Courses', 'moowoodle' ),
				'description'         => __( 'Retrieve Moodle courses managed by MooWoodle, with optional search and category filters.', 'moowoodle' ),
				'category'            => 'moowoodle',

				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'search'      => array(
							'type'        => 'string',
							'description' => __( 'Search by course name or short name.', 'moowoodle' ),
						),

						'search_by'   => array(
							'type'        => 'string',
							'description' => __( 'Field to search.', 'moowoodle' ),
							'enum'        => array(
								'name',
								'shortname',
							),
						),

						'category_id' => array(
							'type'        => 'integer',
							'description' => __( 'Filter courses by Moodle category ID.', 'moowoodle' ),
						),

						'product_id'  => array(
							'type'        => 'integer',
							'description' => __( 'Get the course linked to a WooCommerce product ID.', 'moowoodle' ),
						),

						'limit'       => array(
							'type'        => 'integer',
							'description' => __( 'Maximum number of courses to return.', 'moowoodle' ),
							'default'     => 50,
						),
					),
				),

				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(

						'total' => array(
							'type'        => 'integer',
							'description' => __( 'Total number of courses matching the filters.', 'moowoodle' ),
						),

						'items' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(

									'id'                => array(
										'type' => 'integer',
									),

									'moodle_url'        => array(
										'type' => 'string',
									),

									'moodle_course_id'  => array(
										'type' => 'integer',
									),

									'course_short_name' => array(
										'type' => 'string',
									),

									'course_name'       => array(
										'type' => 'string',
									),

									'product_name'      => array(
										'type' => 'string',
									),

									'product_url'       => array(
										'type' => 'string',
									),

									'product_image'     => array(
										'type' => 'string',
									),

									'category_name'     => array(
										'type' => 'string',
									),

									'enrolled_user'     => array(
										'type' => 'integer',
									),

									'view_users_url'    => array(
										'type' => 'string',
									),

									'date'              => array(
										'type' => 'string',
									),
								),
							),
						),
					),
				),

				'execute_callback'    => array(
					$this,
					'get_courses',
				),

				'permission_callback' => array(
					$this,
					'get_courses_permissions_check',
				),

				'meta'                => array(
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
	public function get_courses_permissions_check() {

		return Util::current_user_has_capability(
			array( 'manage_options' )
		);
	}

	/**
	 * Get courses.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public function get_courses( $input ) {

		$args = array(
			'limit'        => min( absint( $input['limit'] ?? 50 ), 100 ),
			'category'     => absint( $input['category_id'] ?? 0 ),
			'searchaction' => $input['search_by'] ?? '',
			'search'       => sanitize_text_field( $input['search'] ?? '' ),
		);

		if ( ! empty( $input['product_id'] ) ) {
			$args['product_id'] = absint( $input['product_id'] );
		}

		$records = MooWoodle()->rest->get_service( 'courses' )->get_courses_records( $args );

		return array(
			'items' => $records['items'] ?? array(),
			'total' => $records['total'] ?? 0,
		);
	}
}
