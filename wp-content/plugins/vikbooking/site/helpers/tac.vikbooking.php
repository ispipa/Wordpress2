<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

class TACVBO {

	public static $getArray = false;
	
	public static function tac_av_l() {
		$response = 'e4j.error';
		$dbo = JFactory::getDbo();
		$vbo_tn = VikBooking::getTranslator();
		$args = array();
		//VBO 1.10 all the calls to getString() and getInt() should not use 'request' as 3rd argument, but rather 'default' to get the right $input object in the VikRequest Class.
		//This will resolve issues after the setVar() method was called by other parts of the program. POST and GET requests will still provide non-empty values by using 'default'.
		$args['hash'] = VikRequest::getString('e4jauth', '', 'default');
		$args['start_date'] = VikRequest::getString('start_date', '', 'default');
		$args['end_date'] = VikRequest::getString('end_date', '', 'default');
		$args['nights'] = VikRequest::getInt('nights', 1, 'default');
		$args['num_rooms'] = VikRequest::getInt('num_rooms', 1, 'default');
		$args['start_ts'] = strtotime($args['start_date']);
		$args['end_ts'] = strtotime($args['end_date']);

		$valid = true;
		foreach ($args as $k => $v) {
			$valid = $valid && !empty($v);
		}

		//request type
		$req_type = VikRequest::getString('req_type', '', 'default');

		//API version
		$tac_apiv = 4;
		//API v4
		$args['num_adults'] = VikRequest::getInt('num_adults', 1, 'request');
		//API v5
		$args['adults'] = VikRequest::getVar('adults', array());
		$args['children'] = VikRequest::getVar('children', array());
		$args['children_age'] = VikRequest::getVar('children_age', array());
		if (!empty($args['adults']) && !empty($args['children']) && !isset($_REQUEST['num_adults'])) {
			$tac_apiv = 5;
		}
		if ($tac_apiv == 4) {
			$valid = !empty($args['num_adults']) ? $valid : false;
		} elseif ($tac_apiv == 5) {
			$valid = !empty($args['adults']) ? $valid : false;
		}
		//

		/**
		 * The back-end page Calendar can allow the admin to force bookings in case of
		 * no-availability. Its AJAX requests will contain "only_rates=1" and we compose
		 * an extra array-key with all the fully-booked rooms for the requested dates.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		$only_rates = VikRequest::getInt('only_rates', 0, 'request');
		$fullybooked = array();
		//

		if ($valid) {
			$checkauth = md5('vbo.e4j.vbo');
			if ($checkauth == $args['hash']) {
				$avail_rooms = array();
				if ($tac_apiv == 5) {
					//compose adults-children array and sql clause
					$arradultsrooms = array();
					$arradultsclause = array();
					$arrpeople = array();
					if (count($args['adults']) > 0) {
						foreach ($args['adults'] as $kad => $adu) {
							$roomnumb = $kad + 1;
							if (strlen($adu)) {
								$numadults = intval($adu);
								if ($numadults >= 0) {
									$arradultsrooms[$roomnumb] = $numadults;
									$arrpeople[$roomnumb]['adults'] = $numadults;
									$strclause = "(`fromadult`<=".$numadults." AND `toadult`>=".$numadults."";
									if (!empty($args['children'][$kad]) && intval($args['children'][$kad]) > 0) {
										$numchildren = intval($args['children'][$kad]);
										$arrpeople[$roomnumb]['children'] = $numchildren;
										$arrpeople[$roomnumb]['children_age'] = isset($args['children_age'][$roomnumb]) && count($args['children_age'][$roomnumb]) ? $args['children_age'][$roomnumb] : array();
										$strclause .= " AND `fromchild`<=".$numchildren." AND `tochild`>=".$numchildren."";
									} else {
										$arrpeople[$roomnumb]['children'] = 0;
										$arrpeople[$roomnumb]['children_age'] = array();
										if (intval($args['children'][$kad]) == 0) {
											$strclause .= " AND `fromchild` = 0";
										}
									}
									$strclause .= " AND `totpeople` >= ".($arrpeople[$roomnumb]['adults'] + $arrpeople[$roomnumb]['children']);
									$strclause .= " AND `mintotpeople` <= ".($arrpeople[$roomnumb]['adults'] + $arrpeople[$roomnumb]['children']);
									$strclause .= ")";
									$arradultsclause[] = $strclause;
								}
							}
						}
					}
					//
					//Set $args['adults'] to the number of adults occupying the first room but it could be a party of multiple rooms
					$args['num_adults'] = $arrpeople[1]['adults'];
					//
					//This clause would return one room type for each party type: implode(" OR ", $arradultsclause) - the AND clause must be used rather than OR.
					$q = "SELECT `id`, `units` FROM `#__vikbooking_rooms` WHERE `avail`=1 AND (".implode(" AND ", $arradultsclause).");";
				} else {
					//API v4
					$arrpeople = array();
					$arrpeople[1]['adults'] = $args['num_adults'];
					$arrpeople[1]['children'] = 0;
					$q = "SELECT `id`, `units` FROM `#__vikbooking_rooms` WHERE `avail`=1 AND `toadult`>=".$args['num_adults'].";";
				}
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 0) {
					if (self::$getArray) {
						return array('e4j.error' => 'The Query for fetching the rooms returned an empty result');
					}
					echo json_encode(array('e4j.error' => 'The Query for fetching the rooms returned an empty result'));
					exit;
				}

				$avail_rooms = $dbo->loadAssocList();
						
				// arr[0] = (sec) checkin, arr[1] = (sec) checkout
				$check_in_out = VikBooking::getTimeOpenStore();
				$args['start_ts'] += $check_in_out[0];
				$args['end_ts'] += $check_in_out[1];
		
				$room_ids = array();
				for ($i = 0; $i < count($avail_rooms); $i++) {
					$room_ids[$i] = $avail_rooms[$i]['id'];
				}
		
				$all_restrictions = VikBooking::loadRestrictions(true, $room_ids);
				$glob_restrictions = VikBooking::globalRestrictions($all_restrictions);
		
				if (count($glob_restrictions) > 0 && strlen($x = VikBooking::validateRoomRestriction($glob_restrictions, getdate($args['start_ts']), getdate($args['end_ts']), $args['nights'])) > 0) {
					if (self::$getArray) {
						return array('e4j.error' => 'Unable to proceed because of booking Restrictions in these dates ('.$x.')');
					}
					echo json_encode(array('e4j.error' => 'Unable to proceed because of booking Restrictions in these dates ('.$x.')'));
					exit;
				}

				$nowdf = VikBooking::getDateFormat();
				if ($nowdf == "%d/%m/%Y") {
					$df = 'd/m/Y';
				} elseif ($nowdf == "%m/%d/%Y") {
					$df = 'm/d/Y';
				} else {
					$df = 'Y/m/d';
				}
				//Closing Dates
				$err_closingdates = VikBooking::validateClosingDates($args['start_ts'], $args['end_ts'], $df);
				if (!empty($err_closingdates)) {
					if (self::$getArray) {
						return array('e4j.error' => JText::sprintf('VBERRDATESCLOSED', $err_closingdates));
					}
					echo json_encode(array('e4j.error' => JText::sprintf('VBERRDATESCLOSED', $err_closingdates)));
					exit;
				}
				//

				$hoursdiff = VikBooking::countHoursToArrival($args['start_ts']);
		
				//Get Rates
				$room_ids = array();
				foreach ($avail_rooms as $k => $room) {
					$room_ids[$room['id']] = $room;
				}
				$rates = array();
				$q = "SELECT `p`.*, `r`.`id` AS `r_reference_id`, `r`.`name` AS `r_short_desc`, `r`.`img`, `r`.`units`, `r`.`moreimgs`, `r`.`imgcaptions`, `prices`.`id` AS `price_reference_id`, `prices`.`name` AS `pricename`, `prices`.`breakfast_included`, `prices`.`free_cancellation`, `prices`.`canc_deadline`, `prices`.`minlos`, `prices`.`minhadv` FROM `#__vikbooking_dispcost` AS `p`, `#__vikbooking_rooms` AS `r`, `#__vikbooking_prices` AS `prices` WHERE `r`.`id`=`p`.`idroom` AND `p`.`idprice`=`prices`.`id` AND `p`.`days`=".$args['nights']." AND `r`.`id` IN (".implode(',', array_keys($room_ids)).") ORDER BY `p`.`cost` ASC;";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() == 0) {
					if (self::$getArray) {
						return array('e4j.error' => 'The Query for fetching the rates returned an empty result');
					}
					echo json_encode(array('e4j.error' => 'The Query for fetching the rates returned an empty result'));
					exit;
				}
				$rates = $dbo->loadAssocList();
				$vbo_tn->translateContents($rates, '#__vikbooking_rooms', array('id' => 'r_reference_id', 'r_short_desc' => 'name'));
				$vbo_tn->translateContents($rates, '#__vikbooking_prices', array('id' => 'price_reference_id', 'pricename' => 'name'));
				$arr_rates = array();
				/**
				 * If all results are excluded because of restrictions at rate plan level, we use this flag
				 * to know that the rate plans have a Min LOS or a Min Hours in Advance (Advance Booking Offset).
				 * This is to avoid users to say "why do I get no availability?"
				 * 
				 * @since 	1.12.1
				 */
				$err_rplan_restr = false;
				//
				foreach ($rates as $rate) {
					//VBO 1.10 - rate plans with a minlos, or with a min hours in advance
					if ((!empty($rate['minlos']) && $rate['minlos'] > $args['nights']) || $hoursdiff < $rate['minhadv']) {
						// this flag will tell us that some results were excluded due to restrictions at rate plan level
						$err_rplan_restr = true;
						continue;
					} else {
						//we don't want the properties 'minlos' and 'minhadv' in the response.
						unset($rate['minlos']);
						unset($rate['minhadv']);
					}
					//
					if (!isset($arr_rates[$rate['idroom']])) {
						$arr_rates[$rate['idroom']] = array();
					}
					$arr_rates[$rate['idroom']][] = $rate;
				}
		
				//Check Availability for the rooms with a rate for this number of nights
				$minus_units = 0;
				if (count($arr_rates) < $args['num_rooms']) {
					$minus_units = $args['num_rooms'] - count($arr_rates);
				}
				foreach ($arr_rates as $k => $datarate) {
					$room = $room_ids[$k];
					$consider_units = $room['units'] - $minus_units;
					if (!VikBooking::roomBookable($room['id'], $consider_units, $args['start_ts'], $args['end_ts']) || $consider_units <= 0) {
						unset($arr_rates[$k]);
						array_push($fullybooked, (int)$room['id']);
					} else {
				
						if (count($all_restrictions) > 0) {
							$room_restr = VikBooking::roomRestrictions($room['id'], $all_restrictions);
							if (count($room_restr) > 0) {
								if (strlen(VikBooking::validateRoomRestriction($room_restr, getdate($args['start_ts']), getdate($args['end_ts']), $args['nights'])) > 0) {
									unset($arr_rates[$k]);
								}
							}
						}	
				
					}
				}
		
				if (count($arr_rates) == 0) {
					if (self::$getArray) {
						return array('e4j.error' => 'No availability for these dates');
					}
					$res = array('e4j.error' => 'No availability for these dates' . ($err_rplan_restr === true ? ' (Rate Plan Restrictions)' : ''));
					if ($only_rates && count($fullybooked)) {
						$res['fullybooked'] = $fullybooked;
					}
					echo json_encode($res);
					exit;
				}
		
				//apply special prices
				//$arr_rates = VikBooking::applySeasonalPrices($arr_rates, $args['start_ts'], $args['end_ts']); //was just this code.
				//VikBooking v1.6 process any type of price (needed for the Rates Overview in the back-end)
				$arr_rates = VikBooking::applySeasonalPrices($arr_rates, $args['start_ts'], $args['end_ts']);
				$multi_rates = 1;
				foreach ($arr_rates as $idr => $tars) {
					$multi_rates = count($tars) > $multi_rates ? count($tars) : $multi_rates;
				}
				if ($multi_rates > 1) {
					for ($r = 1; $r < $multi_rates; $r++) {
						$deeper_rates = array();
						foreach ($arr_rates as $idr => $tars) {
							foreach ($tars as $tk => $tar) {
								if ($tk == $r) {
									$deeper_rates[$idr][0] = $tar;
									break;
								}
							}
						}
						if (!count($deeper_rates) > 0) {
							continue;
						}
						$deeper_rates = VikBooking::applySeasonalPrices($deeper_rates, $args['start_ts'], $args['end_ts']);
						foreach ($deeper_rates as $idr => $dtars) {
							foreach ($dtars as $dtk => $dtar) {
								$arr_rates[$idr][$r] = $dtar;
							}
						}
					}
				}
				//
				
				//children ages charge
				$children_sums = array();
				//end children ages charge
				
				//sum charges/discounts per occupancy for each room party
				foreach ($arrpeople as $roomnumb => $party) {
					//charges/discounts per adults occupancy
					foreach ($arr_rates as $r => $rates) {
						$children_charges = VikBooking::getChildrenCharges($r, $party['children'], $party['children_age'], $args['nights']);
						if (count($children_charges) > 0) {
							$children_sums[$r] += $children_charges['total'];
						}
						$diffusageprice = VikBooking::loadAdultsDiff($r, $party['adults']);
						//Occupancy Override - Special Price may be setting a charge/discount for this occupancy while default price had no occupancy pricing
						if (!is_array($diffusageprice)) {
							foreach ($rates as $kpr => $vpr) {
								if (array_key_exists('occupancy_ovr', $vpr) && array_key_exists($party['adults'], $vpr['occupancy_ovr']) && strlen($vpr['occupancy_ovr'][$party['adults']]['value'])) {
									$diffusageprice = $vpr['occupancy_ovr'][$party['adults']];
									break;
								}
							}
							reset($rates);
						}
						//
						if (is_array($diffusageprice)) {
							foreach ($rates as $kpr => $vpr) {
								if ($roomnumb == 1) {
									$arr_rates[$r][$kpr]['costbeforeoccupancy'] = $arr_rates[$r][$kpr]['cost'];
								}
								//Occupancy Override
								if (array_key_exists('occupancy_ovr', $vpr) && array_key_exists($party['adults'], $vpr['occupancy_ovr']) && strlen($vpr['occupancy_ovr'][$party['adults']]['value'])) {
									$diffusageprice = $vpr['occupancy_ovr'][$party['adults']];
								}
								//
								$arr_rates[$r][$kpr]['diffusage'] = $party['adults'];
								if ($diffusageprice['chdisc'] == 1) {
									//charge
									if ($diffusageprice['valpcent'] == 1) {
										//fixed value
										$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arr_rates[$r][$kpr]['days'] : $diffusageprice['value'];
										$arr_rates[$r][$kpr]['diffusagecost'][$roomnumb] = $aduseval;
										$arr_rates[$r][$kpr]['cost'] += $aduseval;
									} else {
										//percentage value
										$aduseval = $diffusageprice['pernight'] == 1 ? round(($arr_rates[$r][$kpr]['costbeforeoccupancy'] * $diffusageprice['value'] / 100) * $arr_rates[$r][$kpr]['days'], 2) : round(($arr_rates[$r][$kpr]['costbeforeoccupancy'] * $diffusageprice['value'] / 100), 2);
										$arr_rates[$r][$kpr]['diffusagecost'][$roomnumb] = $aduseval;
										$arr_rates[$r][$kpr]['cost'] += $aduseval;
									}
								} else {
									//discount
									if ($diffusageprice['valpcent'] == 1) {
										//fixed value
										$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arr_rates[$r][$kpr]['days'] : $diffusageprice['value'];
										$arr_rates[$r][$kpr]['diffusagediscount'][$roomnumb] = $aduseval;
										$arr_rates[$r][$kpr]['cost'] -= $aduseval;
									} else {
										//percentage value
										$aduseval = $diffusageprice['pernight'] == 1 ? round(((($arr_rates[$r][$kpr]['costbeforeoccupancy'] / $arr_rates[$r][$kpr]['days']) * $diffusageprice['value'] / 100) * $arr_rates[$r][$kpr]['days']), 2) : round(($arr_rates[$r][$kpr]['costbeforeoccupancy'] * $diffusageprice['value'] / 100), 2);
										$arr_rates[$r][$kpr]['diffusagediscount'][$roomnumb] = $aduseval;
										$arr_rates[$r][$kpr]['cost'] -= $aduseval;
									}
								}
							}
						} elseif ($roomnumb == 1) {
							foreach ($rates as $kpr => $vpr) {
								$arr_rates[$r][$kpr]['costbeforeoccupancy'] = $arr_rates[$r][$kpr]['cost'];
							}
						}
					}
					//end charges/discounts per adults occupancy
				}
				//end sum charges/discounts per occupancy for each room party
		
				//if the rooms are given to a party of multiple rooms, multiply the basic rates per room per number of rooms
				for($i = 2; $i <= $args['num_rooms']; $i++) {
					foreach ($arr_rates as $r => $rates) {
						foreach ($rates as $kpr => $vpr) {
							$arr_rates[$r][$kpr]['cost'] += $arr_rates[$r][$kpr]['costbeforeoccupancy'];
						}
					}
				}
				//end if the rooms are given to a party of multiple rooms, multiply the basic rates per room per number of rooms
				
				//children ages charge
				if (count($children_sums) > 0) {
					foreach ($arr_rates as $r => $rates) {
						if (array_key_exists($r, $children_sums)) {
							foreach ($rates as $kpr => $vpr) {
								$arr_rates[$r][$kpr]['cost'] += $children_sums[$r];
							}
						}
					}
				}
				//endchildren ages charge
				
				//sort results by price ASC
				$arr_rates = VikBooking::sortResults($arr_rates);
				//
		
				// compose taxes information
				$ivainclusa = VikBooking::ivaInclusa();
				$rates_ids = array();
				foreach ($arr_rates as $r => $rate) {
					foreach ($rate as $ids) {
						if (!in_array($ids['idprice'], $rates_ids)) {
							$rates_ids[] = $ids['idprice'];
						}
					}
				}
				$tax_rates = array();
				$q = "SELECT `p`.`id`,`t`.`aliq`,`t`.`taxcap` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `t` ON `p`.`idiva`=`t`.`id` WHERE `p`.`id` IN (".implode(',', $rates_ids).");";
				$dbo->setQuery($q);
				$dbo->execute();
				if ($dbo->getNumRows() > 0) {
					$alltaxrates = $dbo->loadAssocList();
					foreach ($alltaxrates as $tx) {
						if (!empty($tx['aliq']) && $tx['aliq'] > 0) {
							/**
							 * Tax Cap implementation.
							 * 
							 * @since 	1.12
							 */
							$tax_rates[$tx['id']] = array($tx['aliq'], (float)$tx['taxcap']);
						}
					}
				}
				if (count($tax_rates) > 0) {
					foreach ($arr_rates as $r => $rates) {
						foreach ($rates as $k => $rate) {
							if (array_key_exists($rate['idprice'], $tax_rates)) {
								if (intval($ivainclusa) == 1) {
									// prices tax included
									$realcost = $rate['cost'];
									$tax_oper = ($tax_rates[$rate['idprice']][0] + 100) / 100;
									$taxes = $rate['cost'] - ($rate['cost'] / $tax_oper);
									/**
									 * Tax Cap implementation.
									 * 
									 * @since 	1.12
									 */
									if ($tax_rates[$rate['idprice']][1] > 0 && $taxes > $tax_rates[$rate['idprice']][1]) {
										$taxes = $tax_rates[$rate['idprice']][1];
									}
								} else {
									// prices tax excluded
									$realcost = $rate['cost'] * (100 + $tax_rates[$rate['idprice']][0]) / 100;
									$taxes = $realcost - $rate['cost'];
									/**
									 * Tax Cap implementation.
									 * 
									 * @since 	1.12
									 */
									if ($tax_rates[$rate['idprice']][1] > 0 && $taxes > $tax_rates[$rate['idprice']][1]) {
										$realcost = $rate['cost'] + $tax_rates[$rate['idprice']][1];
										$taxes = $tax_rates[$rate['idprice']][1];
									}
								}
								if ($req_type == 'hotel_availability' || $req_type == 'booking_availability') {
									// always set 'cost' to the base rate tax excluded
									$realcost = $realcost - $taxes;
								}
								$arr_rates[$r][$k]['cost'] = round($realcost, 2);
								$arr_rates[$r][$k]['taxes'] = round($taxes, 2);
							}
						}
					}
					// sum taxes/fees for each room party
					foreach ($arrpeople as $roomnumb => $party) {
						foreach ($arr_rates as $r => $rates) {
							$city_tax_fees = VikBooking::getMandatoryTaxesFees(array($r), $party['adults'], $args['nights']);
							foreach ($rates as $k => $rate) {
								if (!isset($arr_rates[$r][$k]['city_taxes'])) {
									$arr_rates[$r][$k]['city_taxes'] = 0;
								}
								if (!isset($arr_rates[$r][$k]['fees'])) {
									$arr_rates[$r][$k]['fees'] = 0;
								}
								$arr_rates[$r][$k]['city_taxes'] += round($city_tax_fees['city_taxes'], 2);
								$arr_rates[$r][$k]['fees'] += round($city_tax_fees['fees'], 2);
							}
						}
					}
					// end sum taxes/fees for each room party
				} else {
					foreach ($arr_rates as $r => $rates) {
						foreach ($rates as $k => $rate) {
							$arr_rates[$r][$k]['taxes'] = round(0, 2);
							$arr_rates[$r][$k]['city_taxes'] = round(0, 2);
							$arr_rates[$r][$k]['fees'] = round(0, 2);
						}
					}
				}
				// end compose taxes information
		
				$response = $arr_rates;

				if (self::$getArray) {
					return $response;
				}

				if ($only_rates && count($fullybooked)) {
					$response['fullybooked'] = $fullybooked;
				}

				echo json_encode($response);
				exit;
			} else {
				$response = 'e4j.error.auth';
			}
		}

		if (self::$getArray) {
			return $response;
		}
		echo $response;
		exit;
	}
	
}
