<?php
/**
 * @package MPHB\Advanced\Api
 * @since 4.1.0
 */

namespace MPHB\Advanced\Api\Controllers\V1;

use MPHB\Advanced\Api\ApiHelper;
use MPHB\Advanced\Api\Controllers\AbstractRestController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class BookingAvailabilityController extends AbstractRestController {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'mphb/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'bookings/availability';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $post_type = 'mphb_booking';

	/**
	 * Register the routes.
	 */
	public function register_routes(){
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get the item schema, conforming to JSON Schema of endpoint.
	 *
	 * @return array
	 */
	public function get_item_schema(){
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'booking_availability',
			'type'       => 'object',
			'properties' => array(
				'check_in_date'      => array(
					'description' => sprintf( 'Check in date as %s.', MPHB()->settings()->dateTime()->getDateTransferFormat() ),
					'type'        => 'string',
					'format'      => 'date',
					'context'     => array( 'view' ),
					'required'    => true,
				),
				'check_out_date'     => array(
					'description' => sprintf( 'Check out date as %s.', MPHB()->settings()->dateTime()->getDateTransferFormat() ),
					'type'        => 'string',
					'format'      => 'date',
					'context'     => array( 'view' ),
					'required'    => true,
				),
				'accommodation_type' => array(
					'description' => 'Accommodation Type id. Enter 0 to select all.',
					'type'        => 'integer',
					'minimum'     => 0,
					'default'     => 0,
					'context'     => array( 'view' ),
				),
				'adults'             => array(
					'description' => 'Count of adults.',
					'type'        => 'integer',
					'minimum'     => 0,
					'default'     => 1,
					'context'     => array( 'view' ),
				),
				'children'           => array(
					'description' => 'Count of children.',
					'type'        => 'integer',
					'minimum'     => 0,
					'default'     => 0,
					'context'     => array( 'view' ),
				),
				'availability'       => array(
					'type'    => 'array',
					'context' => array( 'view' ),
					'items'   => array(
						'type'       => 'object',
						'title'      => 'Accommodations',
						'properties' => array(
							'accommodation_type' => array(
								'description' => 'Accommodation Type id.',
								'type'        => 'integer',
							),
							'title'              => array(
								'description' => 'Title.',
								'type'        => 'string',
							),
							'base_price'         => array(
								'description' => 'Base price.',
								'type'        => 'number',
							),
							'accommodations'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'title'      => 'Accommodations',
									'properties' => array(
										'id'    => array(
											'description' => 'Accommodation id.',
											'type'        => 'integer',
										),
										'title' => array(
											'description' => 'Title.',
											'type'        => 'string',
										),
									),
								),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ){
		if ( ! ApiHelper::checkPostPermissions( $this->post_type, 'read' ) ) {
			return new WP_Error( 'mphb_rest_cannot_view',
				'Sorry, you cannot list resources.',
				array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * @param  array  $availability
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function prepare_item_for_response( $availability, $request ){
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = array(
			'check_in_date'      => ApiHelper::prepareDateResponse( $this->check_in_date ),
			'check_out_date'     => ApiHelper::prepareDateResponse( $this->check_out_date ),
			'accommodation_type' => $this->accommodation_type,
			'adults'             => $this->adults,
			'children'           => $this->children,
			'availability'       => array_values( $availability ),
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $availability, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param  WP_REST_Response  $response  The response object.
		 * @param  mixed  $post  Entity object.
		 * @param  WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "mphb_rest_prepare_{$this->post_type}", $response, $availability, $request );
	}

	/**
	 * @param  \DateTime  $checkInDate
	 * @param  \DateTime  $checkOutDate
	 * @param  int  $accommodationTypeId  Optional. 0 by default.
	 *
	 * @return array [%accommodationTypeId% => [
	 *                        'accommodation_type' => %accommodationTypeId%,
	 *                      'title' => %accommodationTypeTitle%,
	 *                        'base_price' => %accommodationTypeBasePrice%,
	 *                      'accommodations' => [
	 *                                              'id' => %accommodationId%,
	 *                                              'title' => %accommodationTitle%
	 *                                          ]
	 *                      ]
	 *               ]
	 * Will always return original
	 *     IDs because of direct query to the DB.
	 *
	 * @global \wpdb $wpdb
	 */
	protected function getAvailableAccommodations( \DateTime $checkInDate, \DateTime $checkOutDate, $accommodationTypeId = 0 ){
		global $wpdb;

		$lockedAccommodation = MPHB()->getRoomRepository()->getLockedRooms( $checkInDate, $checkOutDate,
			$accommodationTypeId, array( 'skip_buffer_rules' => false ) );

		$query = "SELECT accommodation_type_id.meta_value AS type_id, accommodation_types.post_title AS type_title, accommodations.ID AS accommodation_id, accommodations.post_title AS accommodation_title "
		         . "FROM $wpdb->posts AS accommodations "

		         . "INNER JOIN $wpdb->postmeta AS accommodation_type_id "
		         . "ON accommodations.ID = accommodation_type_id.post_id "
		         . "INNER JOIN $wpdb->posts AS accommodation_types "
		         . "ON accommodation_type_id.meta_value = accommodation_types.ID "

		         . "WHERE accommodations.post_type = '" . MPHB()->postTypes()->room()->getPostType() . "' "
		         . "AND accommodations.post_status = 'publish' "
		         . "AND accommodation_type_id.meta_key = 'mphb_room_type_id' "
		         . "AND accommodation_types.post_status = 'publish' "
		         . "AND accommodation_types.post_type = '" . MPHB()->postTypes()->roomType()->getPostType() . "' ";

		if ( ! empty( $lockedAccommodation ) ) {
			$query .= "AND accommodations.ID NOT IN (" . join( ',', $lockedAccommodation ) . ") ";
		}

		if ( $accommodationTypeId > 0 ) {
			$query .= "AND accommodation_type_id.meta_value = '$accommodationTypeId' ";
		} else {
			$query .= "AND accommodation_type_id.meta_value IS NOT NULL "
			          . "AND accommodation_type_id.meta_value <> '' ";
		}

		/**
		 * @var array [["type_id", "type_title", "accommodation_id", "accommodation_title"], ...]
		 */
		$results = $wpdb->get_results( $query, ARRAY_A );

		$availableAccommodations = array();

		foreach ( $results as $row ) {
			$typeId          = intval( $row['type_id'] );
			$accommodationId = intval( $row['accommodation_id'] );

			if ( ! isset( $availableAccommodations[ $typeId ] ) ) {
				$availableAccommodations[ $typeId ] = array(
					'accommodation_type' => $typeId,
					'title'              => $row['type_title'],
					'base_price'         => mphb_get_room_type_period_price( $this->check_in_date,
						$this->check_out_date, $typeId ),
				);
			}

			$availableAccommodations[ $typeId ]['accommodations'][] = array(
				'id'    => $accommodationId,
				'title' => $row['accommodation_title'],
			);
		}

		return $availableAccommodations;
	}

	/**
	 * Get a single item.
	 *
	 * @param  WP_REST_Request  $request  Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ){
		$this->check_in_date      = ApiHelper::prepareDateRequest( $request['check_in_date'] );
		$this->check_out_date     = ApiHelper::prepareDateRequest( $request['check_out_date'] );
		$this->accommodation_type = $request['accommodation_type'];
		$this->adults             = $request['adults'];
		$this->children           = $request['children'];

		$rooms = $this->getAvailableAccommodations( $this->check_in_date, $this->check_out_date,
			$this->accommodation_type );

		if ( count( $rooms ) && ! empty( $request['accommodation_type'] ) &&
		     is_null( MPHB()->getRoomTypePersistence()->getPost( $request['accommodation_type'] ) ) ) {
			return new WP_Error( "mphb_rest_invalid_accommodation_type",
				'Invalid ID.', array( 'status' => 400 ) );
		}

		$rooms = $this->filterAccommodationsByRates( $rooms );
		$rooms = $this->filterAccommodationsByCapacity( $rooms );
		$rooms = $this->filterAccommodationsByRules( $rooms );

		$data     = $this->prepare_item_for_response( $rooms, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param  array  $availability
	 * @param  WP_REST_Request  $request  Request object.
	 *
	 * @return array.
	 */
	protected function prepare_links( $availability, $request ){
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);
		if ( ! count( $availability ) ) {
			return $links;
		}
		foreach ( $availability as $available ) {
			$links['accommodation_types'][] = array(
				"href"       => rest_url(
					sprintf( '/%s/%s/%d',
						$this->namespace, 'accommodation_types',
						$available['accommodation_type']
					) ),
				"embeddable" => true
			);
			foreach ( $available['accommodations'] as $accommodation ) {
				$links['accommodations'][] = array(
					"href"       => rest_url(
						sprintf( '/%s/%s/%d',
							$this->namespace, 'accommodations',
							$accommodation['id']
						) ),
					"embeddable" => true
				);
			}
		}

		return $links;
	}

	private function filterAccommodationsByRates( $accommodations ){
		$rateSearchAtts = array(
			'check_in_date'  => $this->check_in_date,
			'check_out_date' => $this->check_out_date
		);

		foreach ( $accommodations as $key => $accommodation ) {
			$accommodationTypeId = $accommodation['accommodation_type'];
			if ( ! MPHB()->getRateRepository()->isExistsForRoomType( $accommodationTypeId, $rateSearchAtts ) ) {
				unset( $accommodations[ $key ] );
			}
		}

		return $accommodations;
	}

	private function filterAccommodationsByCapacity( $accommodations ){
		foreach ( $accommodations as $key => $accommodation ) {
			$accommodationTypeId = $accommodation['accommodation_type'];
			$accommodationType   = MPHB()->getRoomTypeRepository()->findById( $accommodationTypeId );

			if ( is_null( $accommodationType ) || $accommodationType->getAdultsCapacity() < $this->adults || $accommodationType->getChildrenCapacity() < $this->children ) {
				unset( $accommodations[ $key ] );
			}
		}

		return $accommodations;
	}

	private function filterAccommodationsByRules( $accommodations ){
		foreach ( $accommodations as $key => $accommodation ) {
			$accommodationTypeId = $accommodation['accommodation_type'];
			if ( ! MPHB()->getRulesChecker()->verify( $this->check_in_date, $this->check_out_date,
				$accommodationTypeId ) ) {
				unset( $accommodations[ $key ] );
				continue;
			}

			$unavailableAccommodations = MPHB()->getRulesChecker()->customRules()->getUnavailableRooms( $this->check_in_date,
				$this->check_out_date, $accommodationTypeId );

			if ( ! empty( $unavailableAccommodations ) ) {
				$availableAccommodations = array_diff( $accommodations[ $accommodationTypeId ], $unavailableAccommodations );
				$accommodations[ $key ]['accommodations'] = $availableAccommodations;
			}
		}

		return $accommodations;
	}
}