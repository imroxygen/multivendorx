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
				'label'       => __( 'Get Courses', 'moowoodle' ),
				'description' => __( 'Retrieve Moodle courses managed by MooWoodle, with optional search and category filters.', 'moowoodle' ),
				'category'    => 'moowoodle',

				'input_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'search' => array(
							'type'        => 'string',
							'description' => __( 'Search by course name or short name.', 'moowoodle' ),
						),

						'search_by' => array(
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

						'product_id' => array(
							'type'        => 'integer',
							'description' => __( 'Get the course linked to a WooCommerce product ID.', 'moowoodle' ),
						),

						'limit' => array(
							'type'        => 'integer',
							'description' => __( 'Maximum number of courses to return.', 'moowoodle' ),
							'default'     => 50,
						),
					),
				),

				'output_schema' => array(
					'type'       => 'array',
					'items'      => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'type' => 'integer',
							),

							'moodle_course_id' => array(
								'type' => 'integer',
							),

							'shortname' => array(
								'type' => 'string',
							),

							'name' => array(
								'type' => 'string',
							),

							'category_id' => array(
								'type' => 'integer',
							),

							'category_name' => array(
								'type' => 'string',
							),

							'product_id' => array(
								'type' => 'integer',
							),

							'product_name' => array(
								'type' => 'string',
							),

							'start_date' => array(
								'type' => 'string',
							),

							'end_date' => array(
								'type' => 'string',
							),

							'enrolled_users' => array(
								'type' => 'integer',
							),
						),
					),
				),

				'execute_callback' => array(
					$this,
					'get_courses',
				),

				'permission_callback' => array(
					$this,
					'get_courses_permissions_check',
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

		$limit = ! empty( $input['limit'] )
			? min( absint( $input['limit'] ), 100 )
			: 50;

		$filters = array(
			'limit' => $limit,
		);

		if ( ! empty( $input['category_id'] ) ) {
			$filters['category_id'] = absint( $input['category_id'] );
		}

		if ( ! empty( $input['search'] ) ) {

			$search = sanitize_text_field( $input['search'] );

			if ( 'shortname' === ( $input['search_by'] ?? '' ) ) {
				$filters['shortname'] = $search;
			} else {
				$filters['fullname'] = $search;
			}
		}

		/**
		 * Find course linked to a WooCommerce product.
		 */
		if ( ! empty( $input['product_id'] ) ) {

			$course_id = (int) get_post_meta(
				absint( $input['product_id'] ),
				Util::MOOWOODLE_PRODUCT_META['wordpress_course_id'],
				true
			);

			if ( ! $course_id ) {
				return array();
			}

			$filters['id'] = $course_id;
		}

		$courses = MooWoodle()->course->get_courses( $filters );

		if ( empty( $courses ) ) {
			return array();
		}

		$results = array();

		foreach ( $courses as $course ) {

			$product_name = '';

			if ( ! empty( $course['product_id'] ) ) {

				$product = wc_get_product(
					(int) $course['product_id']
				);

				if ( $product ) {
					$product_name = $product->get_name();
				}
			}

			$categories = MooWoodle()->category->get_course_categories(
				(int) $course['category_id']
			);

			$category = reset( $categories );

			$enrolled_users = MooWoodle()->enrollment->get_enrollments(
				array(
					'course_id' => (int) $course['id'],
					'count'     => true,
				)
			);

			$results[] = array(
				'id'               => (int) $course['id'],
				'moodle_course_id' => (int) $course['moodle_course_id'],
				'shortname'        => $course['shortname'],
				'name'             => $course['fullname'],
				'category_id'      => (int) $course['category_id'],
				'category_name'    => $category['name'] ?? '',
				'product_id'       => (int) $course['product_id'],
				'product_name'     => $product_name,
				'start_date'       => ! empty( $course['startdate'] )
					? wp_date( 'Y-m-d', $course['startdate'] )
					: '',
				'end_date'         => ! empty( $course['enddate'] )
					? wp_date( 'Y-m-d', $course['enddate'] )
					: '',
				'enrolled_users'   => (int) $enrolled_users,
			);
		}

		return $results;
	}
}