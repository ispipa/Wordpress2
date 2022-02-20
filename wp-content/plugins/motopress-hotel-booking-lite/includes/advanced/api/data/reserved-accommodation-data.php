<?php
/**
 * @package MPHB\Advanced\Api
 * @since 4.1.0
 */

namespace MPHB\Advanced\Api\Data;

use MPHB\Entities\ReservedRoom;

class ReservedAccommodationData {

	public $entity;

	/**
	 * @return array
	 */
	private function getServices(){
		$services         = array();
		$reservedServices = $this->entity->getReservedServices();
		foreach ( $reservedServices as $service ) {
			$serviceData = new ServiceData( $service );
			$services[]  = $serviceData->getData();
		}

		return $services;
	}

	/**
	 * @return array
	 */
	private function getAccommodation(){
		$accommodationId   = $this->entity->getRoomId();
		$accommodationData = AccommodationData::findById( $accommodationId );

		return $accommodationData->getData();
	}

	private function getAccommodationType(){
		$accommodationTypeId   = $this->entity->getRoomTypeId();
		$accommodationTypeData = AccommodationTypeData::findById( $accommodationTypeId );

		return $accommodationTypeData->getData();
	}

	private function getRate(){
		$rateId = $this->entity->getRateId();
		$rate   = RateData::findById( $rateId );

		return $rate->getData();
	}

	public function getData( ReservedRoom $reservedAccommodation ){
		$this->entity = $reservedAccommodation;
		$data['accommodations']     = $this->getAccommodation();
		$data['accommodation_type'] = $this->getAccommodationType();
		$data['rate']               = $this->getRate();
		$data['services']           = $this->getServices();

		return $data;
	}
}