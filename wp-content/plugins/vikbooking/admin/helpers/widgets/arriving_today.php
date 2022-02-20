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

/**
 * Class handler for admin widget "arriving today".
 * 
 * @since 	1.4.0
 */
class VikBookingAdminWidgetArrivingToday extends VikBookingAdminWidget
{
	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_ARRIVETOD_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_ARRIVETOD_DESCR');
		$this->widgetId = basename(__FILE__);
	}

	public function render($data = null)
	{
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		$today_start_ts = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
		$today_end_ts = mktime(23, 59, 59, date("n"), date("j"), date("Y"));
		$checkin_today = array();

		$dbo = JFactory::getDbo();
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`status`,`o`.`checkin`,`o`.`checkout`,`o`.`roomsnum`,`o`.`country`,`o`.`closure`,`o`.`checked`,(SELECT CONCAT_WS(' ',`or`.`t_first_name`,`or`.`t_last_name`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id` LIMIT 1) AS `nominative`,(SELECT SUM(`or`.`adults`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `tot_adults`,(SELECT SUM(`or`.`children`) FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `tot_children` FROM `#__vikbooking_orders` AS `o` WHERE `o`.`checkin`>=".$today_start_ts." AND `o`.`checkin`<=".$today_end_ts." AND `o`.`closure`=0 ORDER BY `o`.`checkin` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$checkin_today = $dbo->loadAssocList();
		}

		$tot_arrivals = 0;
		foreach ($checkin_today as $in_today) {
			if ($in_today['status'] == 'confirmed') {
				$tot_arrivals++;
			}
		}

		// render the necessary PHP/JS code for the modal window only once
		if (!defined('VBO_JMODAL_CHECKIN_BOOKING')) {
			define('VBO_JMODAL_CHECKIN_BOOKING', 1);
			?>
			<script type="text/javascript">
			function vboJModalShowCallback() {
				if (typeof vbo_t_on == "undefined") {
					return;
				}
				// simulate STOP click
				if (vbo_t_on) {
					vbo_t_on = false;
					clearTimeout(vbo_t);
					jQuery(".vbo-dashboard-refresh-play").fadeIn();
				}
			}
			function vboJModalHideCallback() {
				if (typeof vbo_t_on == "undefined") {
					return;
				}
				// simulate PLAY click
				if (!vbo_t_on) {
					vboStartTimer();
					jQuery(".vbo-dashboard-refresh-play").fadeOut();
				}
			}
			</script>
			<?php
			echo $this->vbo_app->getJmodalScript('', 'vboJModalHideCallback();', 'vboJModalShowCallback();');
			echo $this->vbo_app->getJmodalHtml('vbo-checkin-booking', JText::translate('VBOMANAGECHECKSINOUT'));
		}
		//

		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head vbo-dashboard-today-checkin-head">
				<h4><i class="vboicn-enter"></i><?php echo JText::translate('VBDASHTODAYCHECKIN'); ?> <span class="arrivals-tot"><?php echo $tot_arrivals; ?></span></h4>
				<div class="btn-toolbar pull-right vbo-dashboard-search-checkin">
					<div class="btn-wrapper input-append pull-right">
						<input type="text" class="checkin-search form-control" placeholder="<?php echo JText::translate('VBODASHSEARCHKEYS'); ?>">
						<button type="button" class="btn" onclick="jQuery('.checkin-search').val('').trigger('keyup');"><i class="icon-remove"></i></button>
					</div>
				</div>
			</div>
			<div class="vbo-dashboard-today-checkin table-responsive">
				<table class="table vbo-table-search-cin">
					<thead>
						<tr class="vbo-dashboard-today-checkin-firstrow">
							<th class="left"><?php echo JText::translate('VBDASHUPRESONE'); ?></th>
							<th class="left"><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></th>
							<th class="center"><?php echo JText::translate('VBDASHUPRESSIX'); ?></th>
							<th class="center"><?php echo JText::translate('VBDASHUPRESTWO'); ?></th>
							<th class="center"><?php echo JText::translate('VBDASHUPRESFOUR'); ?></th>
							<th class="center"><?php echo JText::translate('VBDASHUPRESFIVE'); ?></th>
							<th class="vbo-tdright"> </th>
						</tr>
						<tr class="warning no-results">
							<td colspan="7"><i class="vboicn-warning"></i> <?php echo JText::translate('VBONORESULTS'); ?></td>
						</tr>
					</thead>
					<tbody>
					<?php
					if (!(count($checkin_today) > 0)) {
						?>
						<tr class="warning">
							<td colspan="7"><i class="vboicn-warning"></i> <?php echo JText::translate('VBONOCHECKINSTODAY'); ?></td>
						</tr>
						<?php
					}
					foreach ($checkin_today as $ink => $intoday) {
						$totpeople_str = $intoday['tot_adults']." ".($intoday['tot_adults'] > 1 ? JText::translate('VBMAILADULTS') : JText::translate('VBMAILADULT')).($intoday['tot_children'] > 0 ? ", ".$intoday['tot_children']." ".($intoday['tot_children'] > 1 ? JText::translate('VBMAILCHILDREN') : JText::translate('VBMAILCHILD')) : "");
						$room_names = array();
						$rooms = VikBooking::loadOrdersRoomsData($intoday['id']);
						foreach ($rooms as $rr) {
							$room_names[] = $rr['room_name'];
						}
						if ($intoday['roomsnum'] == 1) {
							// parse distintive features
							$unit_index = '';
							if (strlen($rooms[0]['roomindex']) && !empty($rooms[0]['params'])) {
								$room_params = json_decode($rooms[0]['params'], true);
								if (is_array($room_params) && array_key_exists('features', $room_params) && @count($room_params['features']) > 0) {
									foreach ($room_params['features'] as $rind => $rfeatures) {
										if ($rind == $rooms[0]['roomindex']) {
											foreach ($rfeatures as $fname => $fval) {
												if (strlen($fval)) {
													$unit_index = ' #'.$fval;
													break;
												}
											}
											break;
										}
									}
								}
							}
							//
							$roomstr = '<span class="vbo-smalltext">'.$room_names[0].$unit_index.'</span>';
						} else {
							$roomstr = '<span class="hasTooltip vbo-tip-small" title="'.implode(', ', $room_names).'">'.$intoday['roomsnum'].'</span><span class="hidden-for-search">'.implode(', ', $room_names).'</span>';
						}
						$act_status = '';
						if ($intoday['status'] == 'confirmed') {
							switch ($intoday['checked']) {
								case -1:
									$ord_status = '<span class="label label-error vbo-status-label" style="background-color: #d9534f;">'.JText::translate('VBOCHECKEDSTATUSNOS').'</span>';
									break;
								case 1:
									$ord_status = '<span class="label label-success vbo-status-label">'.JText::translate('VBOCHECKEDSTATUSIN').'</span>';
									break;
								case 2:
									$ord_status = '<span class="label label-success vbo-status-label">'.JText::translate('VBOCHECKEDSTATUSOUT').'</span>';
									break;
								default:
									$ord_status = '<span class="label label-success vbo-status-label">'.JText::translate('VBCONFIRMED').'</span>';
									break;
							}
							if ($vbo_auth_bookings && $intoday['closure'] != 1) {
								// @wponly lite - no registration
							}
						} elseif ($intoday['status'] == 'standby') {
							$ord_status = '<span label label-warning vbo-status-label>'.JText::translate('VBSTANDBY').'</span>';
						} else {
							$ord_status = '<span class="label label-error vbo-status-label" style="background-color: #d9534f;">'.JText::translate('VBCANCELLED').'</span>';
						}
						$nominative = strlen($intoday['nominative']) > 1 ? $intoday['nominative'] : VikBooking::getFirstCustDataField($intoday['custdata']);
						$country_flag = '';
						if (file_exists(VBO_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$intoday['country'].'.png')) {
							$country_flag = '<img src="'.VBO_ADMIN_URI.'resources/countries/'.$intoday['country'].'.png'.'" title="'.$intoday['country'].'" class="vbo-country-flag vbo-country-flag-left"/>';
						}
						?>
						<tr class="vbo-dashboard-today-checkin-rows">
							<td class="searchable left"><a href="index.php?option=com_vikbooking&amp;task=editorder&amp;cid[]=<?php echo $intoday['id']; ?>"><?php echo $intoday['id']; ?></a></td>
							<td class="searchable left"><?php echo $country_flag.$nominative; ?></td>
							<td class="center"><?php echo $totpeople_str; ?></td>
							<td class="searchable center"><?php echo $roomstr; ?></td>
							<td class="searchable center"><?php echo date(str_replace("/", $this->datesep, $this->df).' H:i', $intoday['checkout']); ?></td>
							<td class="searchable center" data-status="<?php echo $intoday['id']; ?>"><?php echo $ord_status; ?></td>
							<td class="vbo-tdright"><?php echo $act_status; ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function() {
			/* Check-in Search */
			jQuery(".checkin-search").keyup(function () {
				var inp_elem = jQuery(this);
				var instance_elem = inp_elem.closest('.vbo-admin-widget-wrapper');
				var searchTerm = inp_elem.val();
				var listItem = instance_elem.find('.vbo-table-search-cin tbody').children('tr');
				var searchSplit = searchTerm.replace(/ /g, "'):containsi('");
				jQuery.extend(jQuery.expr[':'], {'containsi': 
					function(elem, i, match, array) {
						return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
					}
				});
				instance_elem.find(".vbo-table-search-cin tbody tr td.searchable").not(":containsi('" + searchSplit + "')").each(function(e) {
					jQuery(this).parent('tr').attr('visible', 'false');
				});
				instance_elem.find(".vbo-table-search-cin tbody tr td.searchable:containsi('" + searchSplit + "')").each(function(e) {
					jQuery(this).parent('tr').attr('visible', 'true');
				});
				var jobCount = parseInt(instance_elem.find('.vbo-table-search-cin tbody tr[visible="true"]').length);
				instance_elem.find('.arrivals-tot').text(jobCount);
				if (jobCount > 0) {
					instance_elem.find('.vbo-table-search-cin').find('.no-results').hide();
				} else {
					instance_elem.find('.vbo-table-search-cin').find('.no-results').show();
				}
			});
		});
		</script>
		<?php
	}
}
