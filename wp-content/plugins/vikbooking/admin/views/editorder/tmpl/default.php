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

$row = $this->row;
$rooms = $this->rooms;
$busy = $this->busy;
$customer = $this->customer;
$payments = $this->payments;

$history_obj = VikBooking::getBookingHistoryInstance();
$history_obj->setBid($row['id']);

// load datepicker for all CMS compatibility
JHtml::fetch('stylesheet', VBO_SITE_URI.'resources/jquery-ui.min.css');
JHtml::fetch('jquery.framework', true, true);
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');

$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
$currencyname = VikBooking::getCurrencyName();

$dbo = JFactory::getDbo();
$vbo_app = new VboApplication();
$after_tax = VikBooking::ivaInclusa();
$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
$vbo_modals_html = array();
$payment = VikBooking::getPayment($row['idpayment']);
$pactive_tab = VikRequest::getString('vbo_active_tab', 'vbo-tab-details', 'request');
$printreceipt = VikRequest::getInt('print', 0, 'request');
$printreceipt = ($printreceipt > 0);
if ($printreceipt) {
	//we set a different page title when printing the receipt for the "PDF Printer" to give the file a good name.
	JFactory::getDocument()->setTitle(VikBooking::getFrontTitle()." - ".JText::translate('VBOFISCRECEIPT')." #".$row['id']);
}
$gotouri = 'index.php?option=com_vikbooking&task=editorder&cid[]='.$row['id'];

$tars = array();
$arrpeople = array();
$is_package = !empty($row['pkg']) ? true : false;
$is_cust_cost = false;
foreach ($rooms as $ind => $or) {
	$num = $ind + 1;
	$arrpeople[$num]['adults'] = $or['adults'];
	$arrpeople[$num]['children'] = $or['children'];
	$arrpeople[$num]['children_age'] = $or['childrenage'];
	$arrpeople[$num]['t_first_name'] = $or['t_first_name'];
	$arrpeople[$num]['t_last_name'] = $or['t_last_name'];
	if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
		//package or custom cost set from the back-end
		$is_cust_cost = true;
		continue;
	}
	$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=".(int)$or['idtar'].";";
	$dbo->setQuery($q);
	$dbo->execute();
	$tar = $dbo->getNumRows() ? $dbo->loadAssocList() : array();
	$tar = VikBooking::applySeasonsRoom($tar, $row['checkin'], $row['checkout']);
	//different usage
	if ($or['fromadult'] <= $or['adults'] && $or['toadult'] >= $or['adults']) {
		$diffusageprice = VikBooking::loadAdultsDiff($or['idroom'], $or['adults']);
		//Occupancy Override
		$occ_ovr = VikBooking::occupancyOverrideExists($tar, $or['adults']);
		$diffusageprice = $occ_ovr !== false ? $occ_ovr : $diffusageprice;
		//
		if (is_array($diffusageprice)) {
			//set a charge or discount to the price(s) for the different usage of the room
			foreach ($tar as $kpr => $vpr) {
				$tar[$kpr]['diffusage'] = $or['adults'];
				if ($diffusageprice['chdisc'] == 1) {
					//charge
					if ($diffusageprice['valpcent'] == 1) {
						//fixed value
						$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
						$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
						$tar[$kpr]['diffusagecost'] = "+".$aduseval;
						$tar[$kpr]['room_base_cost'] = $vpr['cost'];
						$tar[$kpr]['cost'] = $vpr['cost'] + $aduseval;
					} else {
						//percentage value
						$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
						$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $tar[$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
						$tar[$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
						$tar[$kpr]['room_base_cost'] = $vpr['cost'];
						$tar[$kpr]['cost'] = $aduseval;
					}
				} else {
					//discount
					if ($diffusageprice['valpcent'] == 1) {
						//fixed value
						$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
						$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
						$tar[$kpr]['diffusagecost'] = "-".$aduseval;
						$tar[$kpr]['room_base_cost'] = $vpr['cost'];
						$tar[$kpr]['cost'] = $vpr['cost'] - $aduseval;
					} else {
						//percentage value
						$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
						$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $tar[$kpr]['days']) * $diffusageprice['value'] / 100) * $tar[$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
						$tar[$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
						$tar[$kpr]['room_base_cost'] = $vpr['cost'];
						$tar[$kpr]['cost'] = $aduseval;
					}
				}
			}
		}
	}
	//
	$tars[$num] = $tar;
}
$pcheckin = $row['checkin'];
$pcheckout = $row['checkout'];
$secdiff = $pcheckout - $pcheckin;
$daysdiff = $secdiff / 86400;
if (is_int($daysdiff)) {
	if ($daysdiff < 1) {
		$daysdiff = 1;
	}
} else {
	if ($daysdiff < 1) {
		$daysdiff = 1;
	} else {
		$sum = floor($daysdiff) * 86400;
		$newdiff = $secdiff - $sum;
		$maxhmore = VikBooking::getHoursMoreRb() * 3600;
		if ($maxhmore >= $newdiff) {
			$daysdiff = floor($daysdiff);
		} else {
			$daysdiff = ceil($daysdiff);
		}
	}
}
$otachannel = '';
$otachannel_name = '';
$otachannel_bid = '';
$otacurrency = '';
if (!empty($row['channel'])) {
	$channelparts = explode('_', $row['channel']);
	$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) > 0 ? $channelparts[1] : ucwords($channelparts[0]);
	$otachannel_name = $otachannel;
	$otachannel_bid = $otachannel.(!empty($row['idorderota']) ? ' - Booking ID: '.$row['idorderota'] : '');
	if (strstr($otachannel, '.') !== false) {
		$otaccparts = explode('.', $otachannel);
		$otachannel = $otaccparts[0];
	}
	$otacurrency = strlen($row['chcurrency']) > 0 ? $row['chcurrency'] : '';
}

$status_type = !empty($row['type']) ? JText::translate('VBO_BTYPE_' . strtoupper($row['type'])) . ' / ' : '';
$extra_status = $row['refund'] > 0 ? ' / ' . JText::translate('VBO_STATUS_REFUNDED') : '';
if ($row['status'] == "confirmed") {
	$saystaus = '<span class="label label-success">' . $status_type . JText::translate('VBCONFIRMED') . $extra_status . '</span>';
} elseif ($row['status'] == "standby") {
	$saystaus = '<span class="label label-warning">' . $status_type . JText::translate('VBSTANDBY') . $extra_status . '</span>';
} else {
	$saystaus = '<span class="label label-error" style="background-color: #d9534f;">' . $status_type . JText::translate('VBCANCELLED') . $extra_status . '</span>';
}

//Prepare modal (used for the Registration and for reconstructing the credit card details through the channel manager)
echo $vbo_app->getJmodalScript();
//end Prepare modal
?>
<script type="text/javascript">
function vbToggleLog(elem) {
	var logdiv = document.getElementById('vbpaymentlogdiv').style.display;
	if (logdiv == 'block') {
		// document.getElementById('vbpaymentlogdiv').style.display = 'none';
		// jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").removeClass("vbo-bookingdet-noteslogs-btn-active");
	} else {
		jQuery(".vbo-bookingdet-noteslogs-btn-active").removeClass("vbo-bookingdet-noteslogs-btn-active");
		if (document.getElementById('vbhistorydiv')) {
			document.getElementById('vbhistorydiv').style.display = 'none';
		}
		if (document.getElementById('vbmessagingdiv')) {
			document.getElementById('vbmessagingdiv').style.display = 'none';
		}
		document.getElementById('vbadminnotesdiv').style.display = 'none';
		document.getElementById('vbinvnotesdiv').style.display = 'none';
		document.getElementById('vbpaymentlogdiv').style.display = 'block';
		jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").addClass("vbo-bookingdet-noteslogs-btn-active");
		if (typeof sessionStorage !== 'undefined') {
			sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', 'paylogs');
		}
	}
}
function changePayment() {
	var newpayment = document.getElementById('newpayment').value;
	if (newpayment != '') {
		var paymentname = document.getElementById('newpayment').options[document.getElementById('newpayment').selectedIndex].text;
		if (confirm('<?php echo addslashes(JText::translate('VBCHANGEPAYCONFIRM')); ?>' + paymentname + '?')) {
			document.adminForm.submit();
		} else {
			document.getElementById('newpayment').selectedIndex = 0;
		}
	}
}
function vbToggleNotes(elem) {
	var notesdiv = document.getElementById('vbadminnotesdiv').style.display;
	if (notesdiv == 'block') {
		// document.getElementById('vbadminnotesdiv').style.display = 'none';
		// jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").removeClass("vbo-bookingdet-noteslogs-btn-active");
	} else {
		jQuery(".vbo-bookingdet-noteslogs-btn-active").removeClass("vbo-bookingdet-noteslogs-btn-active");
		if (document.getElementById('vbpaymentlogdiv')) {
			document.getElementById('vbpaymentlogdiv').style.display = 'none';
		}
		if (document.getElementById('vbhistorydiv')) {
			document.getElementById('vbhistorydiv').style.display = 'none';
		}
		if (document.getElementById('vbmessagingdiv')) {
			document.getElementById('vbmessagingdiv').style.display = 'none';
		}
		document.getElementById('vbinvnotesdiv').style.display = 'none';
		document.getElementById('vbadminnotesdiv').style.display = 'block';
		jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").addClass("vbo-bookingdet-noteslogs-btn-active");
		if (typeof sessionStorage !== 'undefined') {
			sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', 'notes');
		}
	}
}
function vbToggleHistory(elem) {
	var historydiv = document.getElementById('vbhistorydiv').style.display;
	if (historydiv == 'block') {
		// document.getElementById('vbhistorydiv').style.display = 'none';
		// jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").removeClass("vbo-bookingdet-noteslogs-btn-active");
	} else {
		jQuery(".vbo-bookingdet-noteslogs-btn-active").removeClass("vbo-bookingdet-noteslogs-btn-active");
		if (document.getElementById('vbpaymentlogdiv')) {
			document.getElementById('vbpaymentlogdiv').style.display = 'none';
		}
		if (document.getElementById('vbmessagingdiv')) {
			document.getElementById('vbmessagingdiv').style.display = 'none';
		}
		document.getElementById('vbinvnotesdiv').style.display = 'none';
		document.getElementById('vbadminnotesdiv').style.display = 'none';
		document.getElementById('vbhistorydiv').style.display = 'block';
		jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").addClass("vbo-bookingdet-noteslogs-btn-active");
		if (typeof sessionStorage !== 'undefined') {
			sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', 'history');
		}
	}
}
function vbToggleInvNotes(elem) {
	var invnotesdiv = document.getElementById('vbinvnotesdiv').style.display;
	if (invnotesdiv == 'block') {
		// document.getElementById('vbinvnotesdiv').style.display = 'none';
		// jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").removeClass("vbo-bookingdet-noteslogs-btn-active");
	} else {
		jQuery(".vbo-bookingdet-noteslogs-btn-active").removeClass("vbo-bookingdet-noteslogs-btn-active");
		if (document.getElementById('vbpaymentlogdiv')) {
			document.getElementById('vbpaymentlogdiv').style.display = 'none';
		}
		if (document.getElementById('vbhistorydiv')) {
			document.getElementById('vbhistorydiv').style.display = 'none';
		}
		if (document.getElementById('vbmessagingdiv')) {
			document.getElementById('vbmessagingdiv').style.display = 'none';
		}
		document.getElementById('vbadminnotesdiv').style.display = 'none';
		document.getElementById('vbinvnotesdiv').style.display = 'block';
		jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").addClass("vbo-bookingdet-noteslogs-btn-active");
		if (typeof sessionStorage !== 'undefined') {
			sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', 'invnotes');
		}
	}
}
function toggleDiscount(elem) {
	var discsp = document.getElementById('vbdiscenter').style.display;
	if (discsp == 'block') {
		document.getElementById('vbdiscenter').style.display = 'none';
		jQuery(elem).find('i').removeClass("fa-chevron-up").addClass("fa-chevron-down");
	} else {
		document.getElementById('vbdiscenter').style.display = 'block';
		jQuery(elem).find('i').removeClass("fa-chevron-down").addClass("fa-chevron-up");
	}
}
function vbToggleMessaging(elem) {
	var messaging = document.getElementById('vbmessagingdiv').style.display;
	if (messaging == 'block') {
		// document.getElementById('vbmessagingdiv').style.display = 'none';
		// jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").removeClass("vbo-bookingdet-noteslogs-btn-active");
	} else {
		jQuery(".vbo-bookingdet-noteslogs-btn-active").removeClass("vbo-bookingdet-noteslogs-btn-active");
		if (document.getElementById('vbpaymentlogdiv')) {
			document.getElementById('vbpaymentlogdiv').style.display = 'none';
		}
		if (document.getElementById('vbhistorydiv')) {
			document.getElementById('vbhistorydiv').style.display = 'none';
		}
		document.getElementById('vbadminnotesdiv').style.display = 'none';
		document.getElementById('vbinvnotesdiv').style.display = 'none';
		document.getElementById('vbmessagingdiv').style.display = 'block';
		jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").addClass("vbo-bookingdet-noteslogs-btn-active");
		if (typeof sessionStorage !== 'undefined') {
			sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', 'messaging');
		}
		if (typeof VCMChat !== 'undefined') {
			VCMChat.getInstance().scrollToBottom();
		}
	}
}
</script>

<div class="vbo-bookingdet-topcontainer">
	<form name="adminForm" id="adminForm" action="index.php" method="post">
	<?php
	if ($printreceipt) {
		//print the company details
		$companylogo = VikBooking::getSiteLogo();
		$next_receipt = VikBooking::getNextReceiptNumber($row['id']);
		?>
		<div class="vbo-receipt-company-block-outer">
			<div class="vbo-receipt-company-block">
			<?php
			if (!empty($companylogo)) {
				?>
				<div class="vbo-receipt-company-logo"><img src="<?php echo VBO_ADMIN_URI.'resources/'.$companylogo; ?>" /></div>
				<?php
			}
			?>
				<div class="vbo-receipt-company-info"><?php echo VikBooking::getInvoiceCompanyInfo(); ?></div>
			</div>
			<div class="vbo-receipt-numdate-block">
				<div class="vbo-receipt-numdate-inner">
					<div class="vbo-receipt-numdate-title">
						<span><?php echo JText::translate('VBOFISCRECEIPT'); ?></span>
						<span style="float: right; cursor: pointer; color: #ff0000;" onclick="vboMakePrintOnly();"><?php VikBookingIcons::e('times-circle'); ?></span>
					</div>
					<div class="vbo-receipt-numdate-num">
						<span class="vbo-receipt-numdate-num-lbl"><?php echo JText::translate('VBOFISCRECEIPTNUM'); ?></span>
						<span class="vbo-receipt-numdate-num-val">
							<span class="vbo-showin-print" id="vbo-receipt-num"><?php echo $next_receipt; ?></span>
							<input class="vbo-hidein-print" id="vbo-receipt-num-inp" type="number" min="0" value="<?php echo $next_receipt; ?>" onchange="document.getElementById('vbo-receipt-num').innerText = this.value;" />
						</span>
					</div>
					<div class="vbo-receipt-numdate-date">
						<span class="vbo-receipt-numdate-date-lbl"><?php echo JText::translate('VBOFISCRECEIPTDATE'); ?></span>
						<span class="vbo-receipt-numdate-date-val"><?php echo date(str_replace("/", $datesep, $df)); ?></span>
					</div>
				</div>
			</div>
			<div class="vbo-receipt-print-confirm vbo-hidein-print">
				<div class="vbo-receipt-print-btn">
					<span onclick="vboLaunchPrintReceipt();">
						<?php VikBookingIcons::e('print'); ?> 
						<span id="vbo-receipt-print-btn-name"><?php echo JText::translate('VBOPRINTRECEIPT'); ?></span>
					</span>
				</div>
			</div>
		</div>
		<?php
	}
	?>
		
		<div class="vbo-bookdet-container">
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span>ID</span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $row['id']; ?></span>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERONE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo date(str_replace("/", $datesep, $df).' H:i', $row['ts']); ?></span>
				</div>
			</div>
		<?php
		if (!$printreceipt && count($customer)) {
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<!-- @wponly lite - customer editing not supported -->
					<?php echo (isset($customer['country_img']) ? $customer['country_img'].' ' : '').'<span>'.ltrim($customer['first_name'].' '.$customer['last_name']).'</span>'; ?>
				</div>
			</div>
		<?php
		}
		if (!$printreceipt) {
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERROOMSNUM'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<?php echo $row['roomsnum']; ?>
				</div>
			</div>
			<?php
		}
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERFOUR'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<?php echo $row['days']; ?>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERFIVE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				$checkin_info = getdate($row['checkin']);
				$short_wday = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
				?>
					<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $row['checkin']); ?>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERSIX'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				$checkout_info = getdate($row['checkout']);
				$short_wday = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
				?>
					<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $row['checkout']); ?>
				</div>
			</div>
		<?php
		// @wponly lite - no registration

		/**
		 * Check whether the booking can be refunded.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */

		// refundable up to 3 days after check-out day
		$max_refund_ts = mktime(23, 59, 59, $checkout_info['mon'], ($checkout_info['mday'] + 3), $checkout_info['year']);
		// current payment driver must be set
		$tn_driver = is_array($payment) ? $payment['file'] : null;
		// transaction data validation callback
		$tn_data_callback = function($data) use ($tn_driver) {
			return (is_object($data) && isset($data->driver) && $data->driver == $tn_driver);
		};
		$prev_tn_data = $history_obj->getEventsWithData(array('P0', 'PN'), $tn_data_callback);
		$refundable = ($row['status'] != 'standby' && $max_refund_ts >= time() && is_array($prev_tn_data) && count($prev_tn_data));
		if ($refundable) {
			// prepare modal (refundtn) with a custom function to trigger the opening
			array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-refund-tn', JText::translate('VBO_ISSUE_REFUND')));
			echo $vbo_app->getJmodalScript('Refund', 'vboDetectRefundChanges();');
			// count the number of refunds made
			$refunds = $history_obj->getEventsWithData('RF', null, false);
			$refunds = !is_array($refunds) ? array() : $refunds;
			?>
			<script type="text/javascript">
				var vbo_refund_performed = false;
				function vboDetectRefundChanges() {
					if (vbo_refund_performed === true) {
						location.reload();
					}
				}
			</script>

			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBO_ISSUE_REFUND'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<button type="button" class="btn btn-small btn-danger vbo-dorefund-btn"<?php echo count($refunds) ? ' data-totrefunds="' . count($refunds) . '"' : ''; ?> onclick="vboOpenJModalRefund('vbo-refund-tn', 'index.php?option=com_vikbooking&task=refundtn&cid[]=<?php echo $row['id']; ?>&tmpl=component');">
						<?php echo JText::translate('VBO_REFUND'); ?>
					</button>
				</div>
			</div>
			<?php
		}
		//

		if (!$printreceipt && !empty($row['channel'])) {
			$ota_logo_img = VikBooking::getVcmChannelsLogo($row['channel']);
			if ($ota_logo_img === false) {
				$ota_logo_img = $otachannel_name;
			} else {
				$ota_logo_img = '<img src="'.$ota_logo_img.'" class="vbo-channelimg-medium"/>';
			}
			?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBPVIEWORDERCHANNEL'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $ota_logo_img; ?></span>
				</div>
			</div>
			<?php
		}
		if (!$printreceipt) {
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBSTATUS'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $saystaus; ?></span>
				</div>
			</div>
			<?php
			if (is_array($this->vcm_pre_approval)) {
				?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo $this->vcm_pre_approval[1]; ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span>
					<?php
					if (empty($this->vcm_pre_approval[2]) || !empty($this->vcm_pre_approval[3])) {
						// no pre-approvals before, or withdrawn before
						?>
						<a href="index.php?option=com_vikchannelmanager&task=sendSpecialOffer&vbo_oid=<?php echo $row['id']; ?>&spo_type=preapproval&spo_action=send&ota_thread_id=<?php echo $this->vcm_pre_approval[0]; ?>" class="btn btn-small btn-primary vcm-btn-channel-<?php echo strtolower($this->vcm_pre_approval[1]); ?>"><?php VikBookingIcons::e('thumbs-up'); ?> <?php echo JText::translate('VBO_PREAPPROVE_INQUIRY'); ?></a>
						<?php
					} else {
						// pre-approved before, pass the ID
						?>
						<a href="index.php?option=com_vikchannelmanager&task=sendSpecialOffer&vbo_oid=<?php echo $row['id']; ?>&spo_type=preapproval&spo_action=withdraw&ota_thread_id=<?php echo $this->vcm_pre_approval[0]; ?>&spo_id=<?php echo $this->vcm_pre_approval[2]; ?>" class="btn btn-small btn-danger"><?php VikBookingIcons::e('thumbs-down'); ?> <?php echo JText::translate('VBO_WITHDRAW_PREAPPROVAL'); ?></a>
						<?php
					}
					?>
					</span>
				</div>
			</div>
				<?php
			}
			if (!empty($row['channel']) && stripos($row['channel'], 'booking.com') !== false && $row['checkin'] <= time() && strtotime('+ 7 days', $row['checkout']) >= time()) {
				/**
				 * Guest misconduct reporting API for Booking.com.
				 * Only after reservation check-in date and no later than 7 days after check-out date. 
				 * 
				 * @since 	1.13.5 - VCM 1.7.2
				 */
				$misconduct_reported = $history_obj->hasEvent('GM');
				?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBOGUESTMISCONDUCT'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				if ($misconduct_reported === false) {
					// allow to report the guest misconduct
					?>
					<button type="button" class="btn btn-small btn-danger" onclick="vboRenderGuestMisconduct();"><?php VikBookingIcons::e('user-slash'); ?> <?php echo JText::translate('VBOREPORTMISCONDUCT'); ?></button>
					<?php
				} else {
					// guest misconduct already reported
					?>
					<button type="button" class="btn btn-small btn-warning" onclick="vboShowGuestMisconductReported();"><?php VikBookingIcons::e('user-slash'); ?> <?php echo $misconduct_reported; ?></button>
					<?php
				}
				?>
					<a id="vbo-gm-reloadlink" style="display: none;" href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $row['id']; ?>"></a>
				</div>
			</div>

			<div class="vbo-modal-overlay-block vbo-modal-overlay-block-guestmisconduct">
				<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
				<div class="vbo-modal-overlay-content vbo-modal-overlay-content-guestmisconduct">
					<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-guestmisconduct">
						<h3><?php VikBookingIcons::e('exclamation-circle'); ?> <?php echo JText::sprintf('VBOREPGUESTMISCONDUCTTO', 'Booking.com'); ?> <span class="vbo-modal-overlay-close-times" onclick="hideVboDialogGuestMisconduct();">&times;</span></h3>
					</div>
					<div class="vbo-modal-overlay-content-body">
						<div class="vbo-modal-guestmisconduct-addnew" data-bid="<?php echo $row['id']; ?>" data-otabid="<?php echo $row['idorderota']; ?>">
							<div class="vbo-modal-guestmisconduct-report-elems"></div>
							<div class="vbo-modal-guestmisconduct-addnew-save">
								<button type="button" id="vbo-guest-misconduct-submit-btn" class="btn btn-success" onclick="vboSubmitGuestMisconduct();" style="display: none;"><?php echo JText::translate('VBCHANNELMANAGERSENDRQ'); ?></button>
								<button type="button" class="btn btn-danger" onclick="hideVboDialogGuestMisconduct();"><?php echo JText::translate('VBOCLOSE'); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<script type="text/javascript">
			var vbodialogguestmisconduct_on = false;
			var vbo_guest_misconduct_fields = new Array;

			function vboShowGuestMisconductReported() {
				// this is like simulating the navigation to the booking history tab
				jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
				vbToggleHistory(document.getElementById('vbo-trig-bookhistory'));
			}

			function vboRenderGuestMisconduct() {
				// empty content
				jQuery('.vbo-modal-guestmisconduct-report-elems').html('<?php echo $this->escape(JText::translate('VIKLOADING') . '...'); ?>');
				vbo_guest_misconduct_fields = new Array;
				// display modal
				jQuery('.vbo-modal-overlay-block-guestmisconduct').fadeIn();
				vbodialogguestmisconduct_on = true;
				// make AJAX request to VCM to always reload the form fields
				var jqxhr = jQuery.ajax({
					type: "POST",
					url: "index.php",
					data: { option: "com_vikchannelmanager", task: "get_bcom_misconduct_categories", tmpl: "component" }
				}).done(function(res) {
					// parse the JSON response that contains the categories object with all fields for the reporting
					try {
						var misconduct_fields_rs = JSON.parse(res);
						console.log(misconduct_fields_rs);

						// make sure the request was successful
						if (!misconduct_fields_rs.status) {
							alert(misconduct_fields_rs.error);
							hideVboDialogGuestMisconduct();
							return false;
						}

						// render form from response obtained
						var misconduct_html = '';
						var misconduct_fields = misconduct_fields_rs.data;
						if (!misconduct_fields.hasOwnProperty('categories') || !misconduct_fields.hasOwnProperty('fields')) {
							alert('Unexptected response. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
							hideVboDialogGuestMisconduct();
							return false;
						}
						
						// main category of misconduct
						misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem">' + "\n";
						misconduct_html += '<label for="vbo-gm-category">' + misconduct_fields['labels']['category'] + '</label>';
						misconduct_html += '<select id="vbo-gm-category" data-gmname="category_id" onchange="vboGuestMisconductChangeCategory(this.value);">' + "\n";
						for (var category in misconduct_fields['categories']) {
							if (!misconduct_fields['categories'].hasOwnProperty(category)) {
								continue;
							}
							misconduct_html += '<option value="' + misconduct_fields['categories'][category]['id'] + '">' + misconduct_fields['categories'][category]['title'] + '</option>' + "\n";
						}
						misconduct_html += '</select>' + "\n";
						misconduct_html += '</div>' + "\n";
						// push category field in fields list for later submit
						vbo_guest_misconduct_fields.push('vbo-gm-category');

						// sub-categories or additional fields of misconduct categories
						for (var category in misconduct_fields['categories']) {
							if (!misconduct_fields['categories'].hasOwnProperty(category)) {
								continue;
							}
							if (!misconduct_fields['categories'][category].hasOwnProperty('subcategories') && !misconduct_fields['categories'][category].hasOwnProperty('additional_fields')) {
								// at least one of these two properties must be set
								continue;
							}
							if (misconduct_fields['categories'][category].hasOwnProperty('subcategories') && misconduct_fields['categories'][category]['subcategories'].length) {
								// sub-categories in drop down
								var subcat_field_id = 'vbo-gm-subcategory' + misconduct_fields['categories'][category]['id'];
								misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem vbo-modal-guestmisconduct-addnew-cond" data-gmcid="' + misconduct_fields['categories'][category]['id'] + '" style="display: none;">' + "\n";
								misconduct_html += '<label for="' + subcat_field_id + '">' + misconduct_fields['labels']['subcategory'] + '</label>';
								misconduct_html += '<select id="' + subcat_field_id + '" data-gmname="subcategory_id">' + "\n";
								for (var subcategory in misconduct_fields['categories'][category]['subcategories']) {
									if (!misconduct_fields['categories'][category]['subcategories'].hasOwnProperty(subcategory)) {
										continue;
									}
									misconduct_html += '<option value="' + misconduct_fields['categories'][category]['subcategories'][subcategory]['id'] + '">' + misconduct_fields['categories'][category]['subcategories'][subcategory]['title'] + '</option>' + "\n";
								}
								misconduct_html += '</select>' + "\n";
								misconduct_html += '</div>' + "\n";
								// push sub-category field in fields list for later submit
								if (vbo_guest_misconduct_fields.indexOf(subcat_field_id) < 0) {
									vbo_guest_misconduct_fields.push(subcat_field_id);
								}
							} else if (misconduct_fields['categories'][category].hasOwnProperty('additional_fields') && misconduct_fields['categories'][category]['additional_fields'].length) {
								// additional fields
								for (var extra_field in misconduct_fields['categories'][category]['additional_fields']) {
									if (!misconduct_fields['categories'][category]['additional_fields'].hasOwnProperty(extra_field)) {
										continue;
									}
									var field_id = 'vbo-gm-extrafield-' + misconduct_fields['categories'][category]['additional_fields'][extra_field]['name'];
									var field_name = misconduct_fields['categories'][category]['additional_fields'][extra_field]['name'];
									misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem vbo-modal-guestmisconduct-addnew-cond" data-gmcid="' + misconduct_fields['categories'][category]['id'] + '" style="display: none;">' + "\n";
									misconduct_html += '<label for="' + field_id + '">' + misconduct_fields['categories'][category]['additional_fields'][extra_field]['label'] + '</label>';
									if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'number') {
										misconduct_html += '<input data-gmname="' + field_name + '" type="number" id="' + field_id + '" value="" step="any" min="0" />';
									} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'text') {
										misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
									} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'text') {
										misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
									} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'textarea') {
										misconduct_html += '<textarea data-gmname="' + field_name + '" id="' + field_id + '" rows="5" cols="50" maxlength="240"></textarea>';
									} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'select') {
										misconduct_html += '<select data-gmname="' + field_name + '" id="' + field_id + '">';
										for (var choicekey in misconduct_fields['categories'][category]['additional_fields'][extra_field]['choices']) {
											// this is either an array when keys are numeric from 0, or an object when keys start from like 1, but with choicekey we take the proper key in the object
											if (!misconduct_fields['categories'][category]['additional_fields'][extra_field]['choices'].hasOwnProperty(choicekey)) {
												continue;
											}
											misconduct_html += '<option value="' + choicekey + '">' + misconduct_fields['categories'][category]['additional_fields'][extra_field]['choices'][choicekey] + '</option>';
										}
										misconduct_html += '</select>';
									}
									misconduct_html += '</div>' + "\n";
									// push additional field in fields list for later submit
									vbo_guest_misconduct_fields.push(field_id);
								}
							}
						}

						// mandatory fields for all categories of guest misconduct reporting
						for (var mandfield in misconduct_fields['fields']) {
							if (!misconduct_fields['fields'].hasOwnProperty(mandfield)) {
								continue;
							}
							var field_id = 'vbo-gm-mandfield-' + misconduct_fields['fields'][mandfield]['name'];
							var field_name = misconduct_fields['fields'][mandfield]['name'];
							misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem">' + "\n";
							misconduct_html += '<label for="' + field_id + '">' + misconduct_fields['fields'][mandfield]['label'] + '</label>';
							if (misconduct_fields['fields'][mandfield]['type'] == 'number') {
								misconduct_html += '<input data-gmname="' + field_name + '" type="number" id="' + field_id + '" value="" step="any" min="0" />';
							} else if (misconduct_fields['fields'][mandfield]['type'] == 'text') {
								misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
							} else if (misconduct_fields['fields'][mandfield]['type'] == 'text') {
								misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
							} else if (misconduct_fields['fields'][mandfield]['type'] == 'textarea') {
								misconduct_html += '<textarea data-gmname="' + field_name + '" id="' + field_id + '" rows="5" cols="50" maxlength="240"></textarea>';
							} else if (misconduct_fields['fields'][mandfield]['type'] == 'select') {
								misconduct_html += '<select data-gmname="' + field_name + '" id="' + field_id + '">';
								for (var choicekey in misconduct_fields['fields'][mandfield]['choices']) {
									if (!misconduct_fields['fields'][mandfield]['choices'].hasOwnProperty(choicekey)) {
										continue;
									}
									misconduct_html += '<option value="' + choicekey + '">' + misconduct_fields['fields'][mandfield]['choices'][choicekey] + '</option>';
								}
								misconduct_html += '</select>';
							}
							misconduct_html += '</div>' + "\n";
							// push mandatory field in fields list for later submit
							vbo_guest_misconduct_fields.push(field_id);
						}

						// display HTML content and submit button
						jQuery('.vbo-modal-guestmisconduct-report-elems').html(misconduct_html);
						jQuery('#vbo-guest-misconduct-submit-btn').show();
						// trigger change of main category drop down
						jQuery('#vbo-gm-category').trigger('change');
					} catch (e) {
						console.log(res);
						alert('Invalid response. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
						hideVboDialogGuestMisconduct();
						return false;
					}
				}).fail(function() {
					alert('Request failed. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
					hideVboDialogGuestMisconduct();
				});
			}

			function vboSubmitGuestMisconduct() {
				if (!vbo_guest_misconduct_fields.length) {
					alert('Empty data to submit. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
					return false;
				}
				
				// prevent double submissions
				vboGuestMisconductDisableSubmit();
				//

				var submit_fields = {
					option: "com_vikchannelmanager", 
					task: "submit_bcom_guestmisconduct", 
					tmpl: "component",
					bid: jQuery('.vbo-modal-guestmisconduct-addnew').attr('data-bid'),
					otabid: jQuery('.vbo-modal-guestmisconduct-addnew').attr('data-otabid'),
					bcom_keys: new Array
				};
				for (var elemid in vbo_guest_misconduct_fields) {
					if (!vbo_guest_misconduct_fields.hasOwnProperty(elemid)) {
						continue;
					}
					if (!jQuery('#' + vbo_guest_misconduct_fields[elemid]).length || !jQuery('#' + vbo_guest_misconduct_fields[elemid]).closest('.vbo-modal-guestmisconduct-addnew-elem').length) {
						// form element not found, or parent container not found
						continue;
					}
					if (jQuery('#' + vbo_guest_misconduct_fields[elemid]).closest('.vbo-modal-guestmisconduct-addnew-elem').is(':visible')) {
						// we include this value in the AJAX request
						var field_name = jQuery('#' + vbo_guest_misconduct_fields[elemid]).attr('data-gmname');
						var submit_key = 'bcom_' + field_name;
						submit_fields[submit_key] = jQuery('#' + vbo_guest_misconduct_fields[elemid]).val();
						submit_fields['bcom_keys'].push(submit_key);
					}
				}

				// make the AJAX request to VCM
				var jqxhr = jQuery.ajax({
					type: "POST",
					url: "index.php",
					data: submit_fields
				}).done(function(res) {
					try {
						var misconduct_rs = JSON.parse(res);
						console.log(submit_fields, misconduct_rs);

						// make sure the request was successful
						if (!misconduct_rs.status) {
							alert(misconduct_rs.error);
							vboGuestMisconductEnableSubmit();
							return false;
						}

						// make another AJAX request (now on VBO) to update the booking history
						var event_descr = jQuery('#vbo-gm-mandfield-details_text').length ? jQuery('#vbo-gm-mandfield-details_text').val() : '';
						jQuery.ajax({
							type: "POST",
							url: "index.php",
							data: {
								option: "com_vikbooking",
								task: "store_booking_history_event",
								tmpl: "component",
								bid: jQuery('.vbo-modal-guestmisconduct-addnew').attr('data-bid'),
								event: 'GM',
								descr: event_descr
							}
						}).done(function(res) {
							// just reload the page in case of success
							document.location.href = jQuery('#vbo-gm-reloadlink').attr('href');
						}).fail(function() {
							// we reload the page even in case of failure
							document.location.href = jQuery('#vbo-gm-reloadlink').attr('href');
						});
					} catch (e) {
						console.log(res);
						alert('Invalid update response. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
						vboGuestMisconductEnableSubmit();
						hideVboDialogGuestMisconduct();
						return false;
					}
				}).fail(function() {
					alert('Update Request failed. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
					vboGuestMisconductEnableSubmit();
					return false;
				});
			}

			function hideVboDialogGuestMisconduct() {
				if (vbodialogguestmisconduct_on === true) {
					jQuery(".vbo-modal-overlay-block-guestmisconduct").fadeOut(400, function () {
						jQuery(".vbo-modal-overlay-content-guestmisconduct").show();
					});
					// turn flag off
					vbodialogguestmisconduct_on = false;
				}
			}

			function vboGuestMisconductChangeCategory(gmcid) {
				jQuery('.vbo-modal-guestmisconduct-addnew-cond').each(function() {
					if (jQuery(this).attr('data-gmcid') == gmcid) {
						jQuery(this).show();
					} else {
						jQuery(this).hide();
					}
				});
			}

			function vboGuestMisconductDisableSubmit() {
				jQuery('#vbo-guest-misconduct-submit-btn').prepend('<i class="<?php echo VikBookingIcons::i('refresh', 'fa-spin fa-fw'); ?>"></i> ').prop('disabled', true);
			}

			function vboGuestMisconductEnableSubmit() {
				jQuery('#vbo-guest-misconduct-submit-btn').prop('disabled', false).find('i').remove();
			}
			</script>
				<?php
			}
			if ($this->vcm_host_to_guest_review) {
				?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBO_REVIEW_YOUR_GUEST'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<button type="button" class="btn btn-small btn-primary vcm-btn-channel-airbnbapi" onclick="vboOpenJModal('vbo-vcm-host-to-guest-review', 'index.php?option=com_vikchannelmanager&task=hostguestreview&cid[]=<?php echo $row['id']; ?>&tmpl=component');">
						<?php VikBookingIcons::e('smile'); ?>
						<?php echo JText::translate('VBO_WRITE_REVIEW'); ?>
					</button>
				</div>
			</div>
				<?php
				// prepare modal (host-to-guest review)
				array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-host-to-guest-review', JText::translate('VBO_REVIEW_YOUR_GUEST')));
				// end Prepare modal
			}
		}
		?>
		</div>

		<div class="vbo-bookingdet-innertop">
			<div class="vbo-bookingdet-commands">
			<?php
			if (is_array($busy) || $row['status']=="standby") {
				?>
				<div class="vbo-bookingdet-command">
					<button onclick="document.location.href='index.php?option=com_vikbooking&task=editbusy&cid[]=<?php echo $row['id']; ?>';" class="btn vbo-config-btn" type="button"><i class="icon-pencil"></i> <?php echo JText::translate('VBMODRES'); ?></button>
				</div>
				<?php
			}
			if ((array_key_exists(1, $tars) && count($tars[1]) > 0) || ($is_package || $is_cust_cost)) {

				/**
				 * @wponly 	Rewrite order view URI
				 */
				$model 		= JModel::getInstance('vikbooking', 'shortcodes');
				$itemid 	= $model->best('booking');
				$order_uri 	= '';
				$order_sid 	= (!empty($row['sid']) ? $row['sid'] : $row['idorderota']);
				if ($itemid) {
					$order_uri = JRoute::rewrite("index.php?option=com_vikbooking&Itemid={$itemid}&view=booking&sid={$order_sid}&ts={$row['ts']}");
				} else {
					VikError::raiseWarning('', 'No Shortcodes found, or no Shortcodes being used in Pages/Posts.');
				}

				?>
				<div class="vbo-bookingdet-command">
					<button onclick="window.open('<?php echo $order_uri; ?>', '_blank');" type="button" class="btn vbo-config-btn"><?php VikBookingIcons::e('external-link'); ?> <?php echo JText::translate('VBVIEWORDFRONT'); ?></button>
				</div>
				<?php
			}
			if (($row['status'] == "confirmed" || ($row['status'] == "standby" && !empty($row['custmail']))) && ((array_key_exists(1, $tars) && count($tars[1]) > 0) || ($is_package || $is_cust_cost))) {
				?>
				<div class="vbo-bookingdet-command">
					<button class="btn vbo-config-btn" type="button" onclick="document.location.href='index.php?option=com_vikbooking&task=resendordemail&cid[]=<?php echo $row['id']; ?>';"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBRESENDORDEMAIL'); ?></button>
				</div>
				<?php
			}
			if (is_array($this->vcm_special_offer)) {
				if (empty($this->vcm_special_offer[3]) || !empty($this->vcm_special_offer[4])) {
					// no special offer sent before, or withdrawn
				?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-primary vcm-btn-channel-<?php echo strtolower($this->vcm_special_offer[2]); ?>" onclick="vboOpenJModal('vbo-vcm-special-offer', 'index.php?option=com_vikchannelmanager&task=specialoffer&bid=<?php echo $row['id']; ?>&listing_id=<?php echo $this->vcm_special_offer[0]; ?>&ota_thread_id=<?php echo $this->vcm_special_offer[1]; ?>&tmpl=component');"><?php VikBookingIcons::e('certificate'); ?> <?php echo JText::sprintf('VBO_VCM_SPECIAL_OFFER', $this->vcm_special_offer[2]); ?></button>
				</div>
				<?php
					// prepare modal (VCM prepare special offer)
					array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-special-offer', JText::sprintf('VBO_VCM_SPECIAL_OFFER', $this->vcm_special_offer[2])));
					// end prepare modal
				} else {
					// special offer sent before, link to withdraw it
					?>
				<div class="vbo-bookingdet-command">
					<a href="index.php?option=com_vikchannelmanager&task=sendSpecialOffer&vbo_oid=<?php echo $row['id']; ?>&spo_type=special_offer&spo_action=withdraw&ota_thread_id=<?php echo $this->vcm_special_offer[1]; ?>&spo_id=<?php echo $this->vcm_special_offer[3]; ?>" class="btn btn-danger"><?php VikBookingIcons::e('ban'); ?> <?php echo JText::translate('VBO_WITHDRAW_SPOFFER'); ?></a>
				</div>
					<?php
				}
			}
			if ($row['status'] == "standby" || ($row['status'] == "cancelled" && $row['checkout'] >= time())) {
				?>
				<div class="vbo-bookingdet-command">
					<button class="btn btn-success" type="button" onclick="if (confirm('<?php echo addslashes(JText::translate('VBSETORDCONFIRMED')); ?> ?')) {document.location.href='index.php?option=com_vikbooking&task=setordconfirmed&cid[]=<?php echo $row['id'] . ($row['status'] == "cancelled" ? '&skip_notification=1' : ''); ?>';}"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBSETORDCONFIRMED'); ?></button>
				</div>
				<?php
			}
			if ($row['status'] == "cancelled" && !empty($row['custmail'])) {
				?>
				<div class="vbo-bookingdet-command">
					<button class="btn btn-warning" type="button" onclick="document.location.href='index.php?option=com_vikbooking&task=sendcancordemail&cid[]=<?php echo $row['id']; ?>';"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBSENDCANCORDEMAIL'); ?></button>
				</div>
				<?php
			}
			if ($row['status'] == 'confirmed' && $row['closure'] < 1) {
				// @wponly lite
			}
			if ($row['status'] == 'confirmed' && $row['closure'] < 1) {
				// @wponly lite
			}
			if ($row['status'] != 'confirmed' || $row['closure'] > 0 || $this->vcm_cancel_active_res) {
				if ($this->vcm_decline_actions) {
					// this booking requires some decline actions before being removed
					?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-danger" onclick="vboOpenJModal('vbo-vcm-decline-booking', 'index.php?option=com_vikchannelmanager&task=declinebooking&cid[]=<?php echo $row['id']; ?>&tmpl=component');"><?php VikBookingIcons::e('trash'); ?> <?php echo JText::translate('VBMAINEBUSYDEL'); ?></button>
				</div>
					<?php
					// prepare modal (VCM decline booking)
					array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-decline-booking', JText::translate('VBMAINEBUSYDEL')));
					// end prepare modal
				} elseif ($this->vcm_cancel_active_res) {
					// the active OTA booking can be cancelled
					?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-danger" onclick="vboOpenJModal('vbo-vcm-cancel-reservation', 'index.php?option=com_vikchannelmanager&task=cancelreservation&vbo_oid=<?php echo $row['id']; ?>&tmpl=component');"><?php VikBookingIcons::e('trash'); ?> <?php echo JText::translate('VBMAINEBUSYDEL'); ?></button>
				</div>
					<?php
					// prepare modal (VCM cancel reservation)
					array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-cancel-reservation', JText::translate('VBMAINEBUSYDEL')));
					// end prepare modal
				} else {
					// regular remove button with confirmation
					?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-danger" onclick="if (confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')){document.location.href='index.php?option=com_vikbooking&task=removeorders&cid[]=<?php echo $row['id']; ?>';}"><?php VikBookingIcons::e('trash'); ?> <?php echo $row['status'] == 'cancelled' ? JText::translate('VBO_PURGERM_RESERVATION') : JText::translate('VBMAINEBUSYDEL'); ?></button>
				</div>
					<?php
				}
			}
			?>
			</div>

			<div class="vbo-bookingdet-tabs">
				<div class="vbo-bookingdet-tab vbo-bookingdet-tab-active" data-vbotab="vbo-tab-details"><?php echo JText::translate('VBOBOOKDETTABDETAILS'); ?></div>
				<div class="vbo-bookingdet-tab" data-vbotab="vbo-tab-admin"><?php echo JText::translate('VBOBOOKDETTABADMIN'); ?></div>
			</div>
		</div>

		<div class="vbo-bookingdet-tab-cont" id="vbo-tab-details" style="display: block;">
			<div class="vbo-bookingdet-innercontainer">
				<div class="vbo-bookingdet-customer">
					<div class="vbo-bookingdet-detcont<?php echo $row['closure'] > 0 ? ' vbo-bookingdet-closure' : ''; ?>">
					<?php
					$custdata_parts = explode("\n", $row['custdata']);
					if (count($custdata_parts) > 2 && strpos($custdata_parts[0], ':') !== false && strpos($custdata_parts[1], ':') !== false) {
						//attempt to format labels and values
						foreach ($custdata_parts as $custdet) {
							if (strlen($custdet) < 1) {
								continue;
							}
							$custdet_parts = explode(':', $custdet);
							$custd_lbl = '';
							$custd_val = '';
							if (count($custdet_parts) < 2) {
								$custd_val = $custdet;
							} else {
								$custd_lbl = $custdet_parts[0];
								if ($custd_lbl == 'http' || $custd_lbl == 'https') {
									// shift values
									$custdet_parts[2] = $custdet_parts[1];
									$custdet_parts[1] = $custdet_parts[0];
									$custd_lbl = '';
								}
								unset($custdet_parts[0]);
								if ((!strcasecmp(trim($custdet_parts[1]), 'http') || !strcasecmp(trim($custdet_parts[1]), 'https')) && !empty($custdet_parts[2]) && strpos($custdet_parts[2], '//') !== false) {
									// this is a URI
									$custd_val = '<a href="' . trim(implode(':', $custdet_parts)) . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i></a>';
								} else {
									$custd_val = trim(implode(':', $custdet_parts));
								}
							}
							?>
						<div class="vbo-bookingdet-userdetail">
							<?php
							if (strlen($custd_lbl)) {
								?>
							<span class="vbo-bookingdet-userdetail-lbl"><?php echo VikBooking::tnCustomerRawDataLabel($custd_lbl); ?></span>
								<?php
							}
							if (strlen($custd_val)) {
								?>
							<span class="vbo-bookingdet-userdetail-val"><?php echo $custd_val; ?></span>
								<?php
							}
							?>
						</div>
							<?php
						}
					} else {
						if ($row['closure'] > 0) {
							?>
						<div class="vbo-bookingdet-userdetail">
							<span class="vbo-bookingdet-userdetail-val"><?php echo nl2br($row['custdata']); ?></span>
						</div>
							<?php
						} else {
							echo nl2br($row['custdata']);
							?>
						<div class="vbo-bookingdet-userdetail">
							<span class="vbo-bookingdet-userdetail-val">&nbsp;</span>
						</div>
							<?php
						}
					}
					if (!empty($row['ujid'])) {
						$orig_user = JFactory::getUser($row['ujid']);
						$author_name = is_object($orig_user) && property_exists($orig_user, 'name') && !empty($orig_user->name) ? $orig_user->name : '';
						?>
						<div class="vbo-bookingdet-userdetail">
							<span class="vbo-bookingdet-userdetail-val"><?php echo JText::sprintf('VBOBOOKINGCREATEDBY', $row['ujid'].(!empty($author_name) ? ' ('.$author_name.')' : '')); ?></span>
						</div>
						<?php
					}
					?>
					</div>
				<?php
				$invoiced = file_exists(VBO_SITE_PATH.DS.'helpers'.DS.'invoices'.DS.'generated'.DS.$row['id'].'_'.$row['sid'].'.pdf');
				if (!$printreceipt && ((!empty($row['channel']) && !empty($row['idorderota'])) || strlen($row['confirmnumber']) > 0 || $invoiced)) {
					?>
					<div class="vbo-bookingdet-detcont vbo-bookingdet-detcont-labels-wrap vbo-hidein-print">
					<?php
					if (!empty($row['channel']) && !empty($row['idorderota'])) {
						?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-idorderota">
							<span class="label label-info">
								<span><?php echo $otachannel_name . ' ID'; ?></span>
								<span class="badge"><?php echo !strcasecmp($row['type'], 'Inquiry') && $row['status'] == 'standby' ? '-----' : $row['idorderota']; ?></span>
							</span>
						</div>
						<?php
					}
					if (strlen($row['confirmnumber']) > 0) {
						?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-confirmnumber">
							<span class="label label-success">
								<span><?php echo JText::translate('VBCONFIRMNUMB'); ?></span>
								<span class="badge"><?php echo $row['confirmnumber']; ?></span>
							</span>
						</div>
						<?php
					}
					if ($invoiced) {
						?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-invoice">
							<span class="label label-success">
								<span><?php echo JText::translate('VBOCOLORTAGRULEINVONE'); ?></span>
								<span class="badge"><a href="<?php echo VBO_SITE_URI; ?>helpers/invoices/generated/<?php echo $row['id'].'_'.$row['sid']; ?>.pdf" target="_blank"><?php VikBookingIcons::e('download'); ?> <?php echo JText::translate('VBOINVDOWNLOAD'); ?></a></span>
							</span>
						</div>
						<?php
					}
					if (count($this->vcm_review)) {
						?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-review">
							<span class="label label-warning">
								<a href="index.php?option=com_vikchannelmanager&task=reviews&revid=<?php echo $this->vcm_review['id']; ?>" target="_blank" style="color: #fff;"><i class="vboicn-star-full"></i> <?php echo JText::translate('VBOSEEGUESTREVIEW'); ?></a>
							</span>
						</div>
						<?php
					}
					?>
					</div>
					<?php
				}
				if (!$printreceipt && $row['closure'] < 1) {
				?>
					<div class="vbo-bookingdet-detcont vbo-hidein-print">
						<div class="vbo-bookingdet-lblcont">
							<label for="custmail"><?php echo JText::translate('VBCUSTEMAIL'); ?></label>
						</div>
						<div class="vbo-bookingdet-inpwrap">
							<div class="vbo-bookingdet-inpcont">
								<input type="text" name="custmail" id="custmail" value="<?php echo $this->escape($row['custmail']); ?>" size="25" onkeyup="event.keyCode == 13 ? (event.preventDefault();document.adminForm.submit();) : '';"/>
							</div>
						<?php if (!empty($row['custmail'])) : ?>
							<div class="vbo-bookingdet-btncont">
								<button type="button" class="btn vbo-config-btn" onclick="vboToggleSendEmail();" style="vertical-align: top;"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBSENDEMAILACTION'); ?></button>
							</div>
						<?php endif; ?>
						</div>
					</div>
					<div class="vbo-bookingdet-detcont vbo-hidein-print">
						<div class="vbo-bookingdet-lblcont">
							<label for="custphone"><?php echo JText::translate('VBCUSTOMERPHONE'); ?></label>
						</div>
						<div class="vbo-bookingdet-inpwrap">
							<div class="vbo-bookingdet-inpcont">
								<?php echo $vbo_app->printPhoneInputField(array('name' => 'custphone', 'id' => 'custphone', 'value' => $this->escape($row['phone'])), array('nationalMode' => false, 'fullNumberOnBlur' => true)); ?>
							</div>
						<?php if (!empty($row['phone'])) : ?>
							<div class="vbo-bookingdet-btncont">
								<button type="button" class="btn vbo-config-btn" onclick="vboToggleSendSMS();" style="vertical-align: top;"><?php VikBookingIcons::e('comment-dots'); ?> <?php echo JText::translate('VBSENDSMSACTION'); ?></button>
							</div>
						<?php endif; ?>
						</div>
					</div>
				<?php
				}
				?>
				</div>

				<?php
				$all_id_prices = array();
				$used_indexes_map = array();
				?>
				<div class="vbo-bookingdet-summary">
					<div class="table-responsive">
						<table class="table">
						<?php
						foreach ($rooms as $ind => $or) {
							$num = $ind + 1;
							?>
							<tr class="vbo-bookingdet-summary-room">
								<td class="vbo-bookingdet-summary-room-firstcell">
									<div class="vbo-bookingdet-summary-roomnum"><?php VikBookingIcons::e('bed'); ?> <?php echo JText::translate('VBEDITORDERTHREE').' '.$num; ?></div>
								<?php
								//Room Specific Unit
								if ($row['status'] == "confirmed" && !empty($or['params'])) {
									$room_params = json_decode($or['params'], true);
									$arr_features = array();
									$unavailable_indexes = VikBooking::getRoomUnitNumsUnavailable($row, $or['idroom']);
									if (is_array($room_params) && array_key_exists('features', $room_params) && @count($room_params['features']) > 0) {
										foreach ($room_params['features'] as $rind => $rfeatures) {
											if (in_array($rind, $unavailable_indexes) || (isset($used_indexes_map[$or['idroom']]) && in_array($rind, $used_indexes_map[$or['idroom']]))) {
												continue;
											}
											foreach ($rfeatures as $fname => $fval) {
												if (strlen($fval)) {
													$arr_features[$rind] = '#'.$rind.' - '.JText::translate($fname).': '.$fval;
													break;
												}
											}
										}
									}
									if (count($arr_features) > 0) {
										//$or['id'] equals to the ID of each matching record in _ordersrooms
										//echo $vbo_app->getDropDown($arr_features, $or['roomindex'], JText::translate('VBOFEATASSIGNUNITEMPTY'), JText::translate('VBOFEATASSIGNUNIT'), 'roomindex['.$or['id'].']', $or['id']).'<br/>';
										?>
									<div class="vbo-bookingdet-summary-roomnum-chunit">
										<?php echo !$printreceipt ? $vbo_app->getNiceSelect($arr_features, $or['roomindex'], 'roomindex['.$or['id'].']', JText::translate('VBOFEATASSIGNUNIT'), JText::translate('VBOFEATASSIGNUNITEMPTY'), '', 'document.adminForm.submit();', $or['id']) : (!empty($or['roomindex']) && isset($arr_features[$or['roomindex']]) ? $arr_features[$or['roomindex']] : ''); ?>
									</div>
										<?php
										if (!empty($or['idroom']) && !empty($or['roomindex'])) {
											if (!array_key_exists($or['idroom'], $used_indexes_map)) {
												$used_indexes_map[$or['idroom']] = array();
											}
											$used_indexes_map[$or['idroom']][] = $or['roomindex'];
										}
									}
								}
								//
								?>
									<div class="vbo-bookingdet-summary-roomguests">
										<?php VikBookingIcons::e('male'); ?>
										<div class="vbo-bookingdet-summary-roomadults">
											<span><?php echo JText::translate('VBEDITORDERADULTS'); ?>:</span> <?php echo $arrpeople[$num]['adults']; ?>
										</div>
									<?php
									if ($arrpeople[$num]['children'] > 0) {
										$age_str = '';
										if (!empty($arrpeople[$num]['children_age'])) {
											$json_child = json_decode($arrpeople[$num]['children_age'], true);
											if (is_array($json_child) && isset($json_child['age']) && is_array($json_child['age']) && count($json_child['age'])) {
												$age_str = ' '.JText::sprintf('VBORDERCHILDAGES', implode(', ', $json_child['age']));
											}
										}
										?>
										<div class="vbo-bookingdet-summary-roomchildren">
											<span><?php echo JText::translate('VBEDITORDERCHILDREN'); ?>:</span> <?php echo $arrpeople[$num]['children'].$age_str; ?>
										</div>
										<?php
									}
									?>
									</div>
									<?php
									if (!empty($arrpeople[$num]['t_first_name'])) {
									?>
									<div class="vbo-bookingdet-summary-guestname">
										<span><?php echo $arrpeople[$num]['t_first_name'].' '.$arrpeople[$num]['t_last_name']; ?></span>
									</div>
									<?php
									}
									?>
								</td>
								<td>
									<div class="vbo-bookingdet-summary-roomname"><?php echo $or['name']; ?></div>
									<div class="vbo-bookingdet-summary-roomrate">
									<?php
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										if (!empty($or['pkg_name'])) {
											//package
											echo $or['pkg_name'];
										} else {
											//custom cost can have an OTA Rate Plan name
											if (!empty($or['otarplan'])) {
												echo ucwords($or['otarplan']);
											} else {
												echo JText::translate('VBOROOMCUSTRATEPLAN');
											}
										}
									} elseif (array_key_exists($num, $tars) && !empty($tars[$num][0]['idprice'])) {
										$all_id_prices[] = $tars[$num][0]['idprice'];
										echo VikBooking::getPriceName($tars[$num][0]['idprice']);
										if (!empty($tars[$num][0]['attrdata'])) {
											?>
										<div>
											<?php echo VikBooking::getPriceAttr($tars[$num][0]['idprice']).": ".$tars[$num][0]['attrdata']; ?>
										</div>
											<?php
										}
									} elseif (!empty($or['otarplan'])) {
										echo ucwords($or['otarplan']);
									} elseif ($row['closure'] < 1) {
										echo JText::translate('VBOROOMNORATE');
									}
									?>
									</div>
								</td>
								<td>
									<div class="vbo-bookingdet-summary-price">
									<?php
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										echo $currencyname.' '.VikBooking::numberFormat($or['cust_cost']);
									} elseif (array_key_exists($num, $tars) && !empty($tars[$num][0]['idprice'])) {
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
										echo $currencyname.' '.VikBooking::numberFormat(($after_tax ? VikBooking::sayCostPlusIva($display_rate, $tars[$num][0]['idprice']) : $display_rate));
									}
									?>
									</div>
								</td>
							</tr>
							<?php
							//Options
							if (!empty($or['optionals'])) {
								$stepo = explode(";", $or['optionals']);
								$counter = 0;
								foreach ($stepo as $roptkey => $oo) {
									if (empty($oo)) {
										continue;
									}
									$stept = explode(":", $oo);
									$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=".(int)$stept[0].";";
									$dbo->setQuery($q);
									$dbo->execute();
									if ($dbo->getNumRows() != 1) {
										continue;
									}
									$counter++;
									$actopt = $dbo->loadAssocList();
									$chvar = '';
									if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
										$optagenames = VikBooking::getOptionIntervalsAges($actopt[0]['ageintervals']);
										$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
										$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
										$child_num 	 = VikBooking::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
										$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
										$agestept = explode('-', $stept[1]);
										$stept[1] = $agestept[0];
										$chvar = $agestept[1];
										if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
											//percentage value of the adults tariff
											if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
												$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
											} else {
												$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
												$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
											}
										} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
											//VBO 1.10 - percentage value of room base cost
											if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
												$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
											} else {
												$display_rate = isset($tars[$num][0]['room_base_cost']) ? $tars[$num][0]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost']);
												$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
											}
										}
										$actopt[0]['chageintv'] = $chvar;
										$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
										$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $row['days'] * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
									} else {
										// VBO 1.11 - options percentage cost of the room total fee
										if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
											$deftar_basecosts = $or['cust_cost'];
										} else {
											$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
										}
										$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
										//
										$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $row['days'] * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
									}
									if ($actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
										$realcost = $actopt[0]['maxprice'];
										if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
											$realcost = $actopt[0]['maxprice'] * $stept[1];
										}
									}
									$realcost = $actopt[0]['perperson'] == 1 ? ($realcost * $arrpeople[$num]['adults']) : $realcost;
									$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
									?>
							<tr class="vbo-bookingdet-summary-options">
								<td class="vbo-bookingdet-summary-options-title"><?php echo $counter == 1 ? JText::translate('VBEDITORDEREIGHT') : '&nbsp;'; ?></td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo ($stept[1] > 1 ? $stept[1]." " : "").$actopt[0]['name']; ?></span>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo $currencyname." ".VikBooking::numberFormat(($after_tax ? $tmpopr : $realcost)); ?></span>
								</td>
							</tr>
								<?php
								}
							}
							//Custom extra costs
							if (!empty($or['extracosts'])) {
								$counter = 0;
								$cur_extra_costs = json_decode($or['extracosts'], true);
								foreach ($cur_extra_costs as $eck => $ecv) {
									$counter++;
									?>
							<tr class="vbo-bookingdet-summary-custcosts">
								<td class="vbo-bookingdet-summary-custcosts-title"><?php echo $counter == 1 ? JText::translate('VBPEDITBUSYEXTRACOSTS') : '&nbsp;'; ?></td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo $ecv['name']; ?></span>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo $currencyname." ".VikBooking::numberFormat(($after_tax ? VikBooking::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'])); ?></span>
								</td>
							</tr>
									<?php
								}
							}
						}
						//vikbooking 1.1 coupon
						if (strlen($row['coupon']) > 0) {
							$expcoupon = explode(";", $row['coupon']);
							?>
							<tr class="vbo-bookingdet-summary-coupon">
								<td><?php echo JText::translate('VBCOUPON'); ?></td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo $expcoupon[2]; ?></span>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost">- <?php echo $currencyname; ?> <?php echo VikBooking::numberFormat($expcoupon[1]); ?></span>
								</td>
							</tr>
							<?php
						}
						if ($row['refund'] > 0) {
							?>
							<tr class="vbo-bookingdet-summary-totpaid vbo-bookingdet-summary-totrefunded">
								<td>
									<strong><?php echo JText::translate('VBOBOOKHISTORYTRF'); ?></strong>
								</td>
								<td>
									<span class="vbo-amount-refunded-lbl"><?php echo JText::translate('VBO_AMOUNT_REFUNDED'); ?></span>
								</td>
								<td>
									<div id="vbo-amountrefunded-cont">
										<span id="vbo-amountrefunded-current"><?php echo $currencyname.' '.VikBooking::numberFormat($row['refund']); ?></span>
										<span id="vbo-amountrefunded-edit" style="margin-left: 5px; cursor: pointer;"><?php VikBookingIcons::e('edit'); ?></span>
									</div>
									<div id="vbo-amountrefunded-modcont" style="display: none;">
										<span id="vbo-amountrefunded-cancedit" style="margin-right: 5px; cursor: pointer;"><?php VikBookingIcons::e('times'); ?></span>
										<span id="vbo-amountrefunded-new"><input type="number" step="any" name="newamountrefunded" value="" min="0" style="margin: 0;" placeholder="<?php echo $row['refund']; ?>" disabled /></span>
										<span id="vbo-amountrefunded-save"><button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button></span>
									</div>
								</td>
							</tr>
							<?php
						}
						//Reservation Total
						//Taxes Breakdown (only if tot_taxes is greater than 0)
						$tax_breakdown = array();
						$base_aliq = 0;
						if (count($all_id_prices) > 0 && $row['tot_taxes'] > 0) {
							//only last type of price assuming that the tax breakdown is equivalent in case of different rates
							$q = "SELECT `p`.`id`,`p`.`name`,`p`.`idiva`,`t`.`aliq`,`t`.`breakdown` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `t` ON `p`.`idiva`=`t`.`id` WHERE `p`.`id`=".intval(array_pop($all_id_prices))." LIMIT 1;";
							$dbo->setQuery($q);
							$dbo->execute();
							if ($dbo->getNumRows() > 0) {
								$breakdown_info = $dbo->loadAssoc();
								if (!empty($breakdown_info['breakdown']) && !empty($breakdown_info['aliq'])) {
									$tax_breakdown = json_decode($breakdown_info['breakdown'], true);
									$tax_breakdown = is_array($tax_breakdown) && count($tax_breakdown) > 0 ? $tax_breakdown : array();
									$base_aliq = $breakdown_info['aliq'];
								}
							}
						}
						//
						if ($printreceipt && $row['tot_taxes'] > 0) {
							// only when printing the receipt we display an additional row for the total amount of tax
							?>
							<tr class="vbo-bookingdet-summary-total vbo-bookingdet-summary-totaltax">
								<td>
									
								</td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo JText::translate('VBTOTALVAT'); ?></span>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo $currencyname; ?> <?php echo VikBooking::numberFormat($row['tot_taxes']); ?></span>
								</td>
							</tr>
							<?php
						}
						?>
							<tr class="vbo-bookingdet-summary-total">
								<td>
								<?php
								if (!$printreceipt) {
									?>
									<span class="vbapplydiscsp" onclick="toggleDiscount(this);">
										<i class="<?php echo VikBookingIcons::i('chevron-down'); ?>" title="<?php echo JText::translate('VBAPPLYDISCOUNT'); ?>"></i>
									</span>
									<?php
								}
								?>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo JText::translate('VBEDITORDERNINE'); ?></span>

									<div class="vbdiscenter" id="vbdiscenter" style="display: none;">
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBTOTALVAT'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="tot_taxes" value="<?php echo $row['tot_taxes']; ?>" size="4"/></span>
										</div>
									<?php
									if (count($tax_breakdown)) {
										foreach ($tax_breakdown as $tbkk => $tbkv) {
											$tax_break_cost = $row['tot_taxes'] * floatval($tbkv['aliq']) / $base_aliq;
											?>
										<div class="vbdiscenter-entry vbdiscenter-entry-breakdown">
											<span class="vbdiscenter-label"><?php echo $tbkv['name']; ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <?php echo VikBooking::numberFormat($tax_break_cost); ?></span>
										</div>
											<?php
										}
									}
									?>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBTOTALCITYTAX'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="tot_city_taxes" value="<?php echo $row['tot_city_taxes']; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBTOTALFEES'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="tot_fees" value="<?php echo $row['tot_fees']; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label hasTooltip"<?php echo !empty($otachannel_name) ? ' title="'.$otachannel_name.'"' : ''; ?>><?php echo JText::translate('VBTOTALCOMMISSIONS'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="cmms" value="<?php echo $row['cmms']; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBAPPLYDISCOUNT'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="admindisc" value="<?php echo isset($expcoupon) ? (float)$expcoupon[1] : ''; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entrycentered">
											<button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button>
										</div>
									</div>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo (strlen($otacurrency) > 0 ? '('.$otacurrency.') '.$currencyname : $currencyname); ?> <?php echo VikBooking::numberFormat($row['total']); ?></span>
								</td>
							</tr>
						<?php
						if (!empty($row['totpaid']) && $row['totpaid'] > 0) {
							$diff_to_pay = $row['total'] - $row['totpaid'];
							?>
							<tr class="vbo-bookingdet-summary-totpaid">
								<td>&nbsp;</td>
								<td><?php echo JText::translate('VBAMOUNTPAID'); ?></td>
								<td>
									<div id="vbo-amountpaid-cont">
										<span id="vbo-amountpaid-current"><?php echo $currencyname.' '.VikBooking::numberFormat($row['totpaid']); ?></span>
										<span id="vbo-amountpaid-edit" style="margin-left: 5px; cursor: pointer;"><?php VikBookingIcons::e('edit'); ?></span>
									</div>
									<div id="vbo-amountpaid-modcont" style="display: none;">
										<span id="vbo-amountpaid-cancedit" style="margin-right: 5px; cursor: pointer;"><?php VikBookingIcons::e('times'); ?></span>
										<span id="vbo-amountpaid-new"><input type="number" step="any" name="newamountpaid" value="" min="0" style="margin: 0;" placeholder="<?php echo $row['totpaid']; ?>" disabled /></span>
									<?php
									if (is_array($payments)) {
										?>
										<span id="vbo-amountpaid-paymeth" style="margin: 0 5px; max-width: 160px;">
											<select name="newamountpaymeth" style="margin: 0;">
												<option value=""><?php echo JText::translate('VBPAYMENTMETHOD'); ?></option>
											<?php
											foreach ($payments as $pay) {
												?>
												<option value="<?php echo $pay['id'] . '_' . $pay['name']; ?>"><?php echo $pay['name']; ?></option>
												<?php
											}
											?>
											</select>
										</span>
										<?php
									}
									?>
										<span id="vbo-amountpaid-save"><button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button></span>
									</div>
								</td>
							</tr>
							<?php
							if ($diff_to_pay > 1) {
							?>
							<tr class="vbo-bookingdet-summary-totpaid vbo-bookingdet-summary-totremaining">
								<td>&nbsp;</td>
								<td>
									<div><?php echo JText::translate('VBTOTALREMAINING'); ?></div>
									<?php
									//enable second payment
									if (!$printreceipt && $row['status'] == 'confirmed' && !($row['paymcount'] > 0) && VikBooking::multiplePayments() && is_array($payment) && !empty($payment['id'])) {
										?>
										<div style="margin-top: 5px;">
											<a href="index.php?option=com_vikbooking&amp;task=editorder&amp;makepay=1&amp;cid[]=<?php echo $row['id']; ?>" class="vbo-makepayable-link"><?php VikBookingIcons::e('credit-card'); ?> <?php echo JText::translate('VBMAKEORDERPAYABLE'); ?></a>
										</div>
										<?php
									}
									//
									?>
								</td>
								<td><?php echo $currencyname.' '.VikBooking::numberFormat($diff_to_pay); ?></td>
							</tr>
							<?php
							}
						}
						if (!$printreceipt && $row['status'] == 'confirmed' && VikBooking::multiplePayments() && is_array($payment) && !empty($payment['id']) && $row['checkout'] > time()) {
							/**
							 * The amount payable can be modified by the admin.
							 * 
							 * @since 	1.14 (J) - 1.4.0 (WP)
							 */
							?>
							<tr class="vbo-bookingdet-summary-totpaid vbo-bookingdet-summary-totpayable">
								<td>&nbsp;</td>
								<td>
									<span class="vbo-amount-payable-lbl<?php echo $row['payable'] > 0 ? ' vbo-amount-payable-lbl-requested' : ''; ?>"><?php echo $row['payable'] > 0 ? JText::translate('VBO_AMOUNT_PAYABLE') : JText::translate('VBO_AMOUNT_PAYABLE_RQ'); ?></span>
								</td>
								<td>
									<div id="vbo-amountpayable-cont">
									<?php
									if ($row['payable'] > 0) {
										?>
										<span id="vbo-amountpayable-current"><?php echo $currencyname . ' ' . VikBooking::numberFormat($row['payable']); ?></span>
										<?php
									}
									?>
										<span id="vbo-amountpayable-edit" style="margin-left: 5px; cursor: pointer;"><?php VikBookingIcons::e('edit'); ?></span>
									</div>
									<div id="vbo-amountpayable-modcont" style="display: none;">
										<span id="vbo-amountpayable-cancedit" style="margin-right: 5px; cursor: pointer;"><?php VikBookingIcons::e('times'); ?></span>
										<span id="vbo-amountpayable-new"><input type="number" step="any" name="newamountpayable" value="" min="0" style="margin: 0;" placeholder="<?php echo $row['payable']; ?>" disabled /></span>
										<span id="vbo-amountpayable-save"><button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button></span>
									</div>
								</td>
							</tr>
							<?php
						}
						?>
						</table>
					</div>
				</div>
			</div>
		<?php
		if ($printreceipt) {
			$receipt_notes = VikBooking::getReceiptNotes();
			?>
			<div class="vbo-receipt-notes-container">
				<div class="vbo-receipt-notes-inner">
					<div class="vbo-receipt-notes-val vbo-showin-print" id="vbo-receipt-notes-val"><?php echo $receipt_notes; ?></div>
					<div class="vbo-receipt-notes-tarea vbo-hidein-print">
						<textarea id="vbo-receipt-notes" placeholder="<?php echo JText::translate('VBORECEIPTNOTESDEF'); ?>"><?php echo htmlspecialchars($receipt_notes); ?></textarea>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		</div>

		<div class="vbo-bookingdet-tab-cont" id="vbo-tab-admin" style="display: none;">
			<div class="vbo-bookingdet-innercontainer">
				<div class="vbo-bookingdet-admindata">
					<!-- @wponly lite -->
					<div class="vbo-bookingdet-admin-entry">
						<label for="newpayment"><?php echo JText::translate('VBPAYMENTMETHOD'); ?></label>
					<?php
					if (is_array($payment)) {
						?>
						<span><?php echo $payment['name']; ?></span>
						<?php
					}
					$chpayment = '';
					if (is_array($payments)) {
						$chpayment = '<div><select name="newpayment" id="newpayment" onchange="changePayment();"><option value="">'.JText::translate('VBCHANGEPAYLABEL').'</option>';
						// @wponly lite
						$chpayment .= '</select></div>';
					}
					echo $chpayment;
					?>
					</div>
				<?php
				$tn = VikBooking::getTranslator();
				$all_langs = $tn->getLanguagesList();
				if (count($all_langs) > 1) {
				?>
					<div class="vbo-bookingdet-admin-entry">
						<label for="newlang"><?php echo JText::translate('VBOBOOKINGLANG'); ?></label>
						<select name="newlang" id="newlang" onchange="document.adminForm.submit();">
						<?php
						foreach ($all_langs as $lk => $lv) {
							?>
							<option value="<?php echo $lk; ?>"<?php echo $row['lang'] == $lk ? ' selected="selected"' : ''; ?>><?php echo isset($lv['nativeName']) ? $lv['nativeName'] : $lv['name']; ?></option>
							<?php
						}
						?>
						</select>
					</div>
				<?php
				}
				?>
				</div>
				<div class="vbo-bookingdet-noteslogs">
					<?php
					$history = $history_obj->loadHistory();
					?>
					<div class="vbo-bookingdet-noteslogs-btns">
						<div class="vbo-bookingdet-noteslogs-btn vbo-bookingdet-noteslogs-btn-active">
							<a href="javascript: void(0);" id="vbo-trig-notes" onclick="javascript: vbToggleNotes(this);"><?php VikBookingIcons::e('user-lock'); ?> <?php echo JText::translate('VBADMINNOTESTOGGLE'); ?></a>
						</div>
					<?php
					/**
					 * Initialize chat instance by getting the proper channel name
					 * 
					 * @since  1.12
					 */
					if (empty($row['channel'])) {
						// front-end reservation chat handler
						$chat_channel = 'vikbooking';
					} else {
						$channelparts = explode('_', $row['channel']);
						if (preg_match("/(customer).*[0-9]$/", $channelparts[0]) && empty($row['idorderota'])) {
							// customer of type sales channel should use front-end reservation chat handler
							$chat_channel = 'vikbooking';
						} else {
							// let the getInstance method validate the channel chat handler
							$chat_channel = $row['channel'];
						}
					}
					$messaging = VikBooking::getVcmChatInstance($row['id'], $chat_channel);
					//
					if (!$printreceipt && !is_null($messaging)) {
						?>
						<div class="vbo-bookingdet-noteslogs-btn">
							<a name="messaging" href="javascript: void(0);" id="vbo-trig-messaging" onclick="javascript: vbToggleMessaging(this);"><?php VikBookingIcons::e('comment-dots'); ?> <?php echo JText::translate('VBO_GUEST_MESSAGING'); ?></a>
						</div>
						<?php
					}
					if (!empty($row['paymentlog'])) {
						?>
						<div class="vbo-bookingdet-noteslogs-btn">
							<a href="javascript: void(0);" id="vbo-trig-paylogs" onclick="javascript: vbToggleLog(this);"><?php VikBookingIcons::e('credit-card'); ?> <?php echo JText::translate('VBPAYMENTLOGTOGGLE'); ?></a>
							<a name="paymentlog" href="javascript: void(0);"></a>
						</div>
						<?php
					}
					if (count($history)) {
						?>
						<div class="vbo-bookingdet-noteslogs-btn">
							<a href="javascript: void(0);" id="vbo-trig-bookhistory" onclick="javascript: vbToggleHistory(this);"><?php VikBookingIcons::e('history'); ?> <?php echo JText::translate('VBOBOOKHISTORYTAB'); ?></a>
						</div>
						<script type="text/javascript">
						if (window.location.hash == '#bookhistory') {
							setTimeout(function() {
								jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
								vbToggleHistory(document.getElementById('vbo-trig-bookhistory'));
							}, 500);
						}
						</script>
						<?php
					}
					?>
						<div class="vbo-bookingdet-noteslogs-btn">
							<a href="javascript: void(0);" class="hasTooltip" id="vbo-trig-invnotes" onclick="javascript: vbToggleInvNotes(this);" title="<?php echo addslashes(JText::translate('VBBOOKINGINVNOTESHELP')); ?>"><?php VikBookingIcons::e('file-invoice'); ?> <?php echo JText::translate('VBBOOKINGINVNOTES'); ?></a>
						</div>
					</div>
					<div class="vbo-bookingdet-noteslogs-cont">
						<div id="vbadminnotesdiv" style="display: block;">
							<textarea name="adminnotes" class="vbadminnotestarea"><?php echo strip_tags($row['adminnotes']); ?></textarea>
							<input type="submit" name="updadmnotes" value="<?php echo JText::translate('VBADMINNOTESUPD'); ?>" class="btn btn-success" />
						</div>
					<?php
					if (count($history)) {
						?>
						<div id="vbhistorydiv" style="display: none;">
							<div class="vbo-booking-history-container table-responsive">
								<table class="table">
									<thead>
										<tr class="vbo-booking-history-firstrow">
											<td class="vbo-booking-history-td-type"><?php echo JText::translate('VBOBOOKHISTORYLBLTYPE'); ?></td>
											<td class="vbo-booking-history-td-date"><?php echo JText::translate('VBOBOOKHISTORYLBLDATE'); ?></td>
											<td class="vbo-booking-history-td-descr"><?php echo JText::translate('VBOBOOKHISTORYLBLDESC'); ?></td>
											<td class="vbo-booking-history-td-totpaid"><?php echo JText::translate('VBOBOOKHISTORYLBLTPAID'); ?></td>
											<td class="vbo-booking-history-td-tot"><?php echo JText::translate('VBOBOOKHISTORYLBLTOT'); ?></td>
										</tr>
									</thead>
									<tbody>
									<?php
									foreach ($history as $hist) {
										$hdescr = strpos($hist['descr'], '<') !== false ? $hist['descr'] : nl2br($hist['descr']);
										?>
										<tr class="vbo-booking-history-row">
											<td><?php echo $history_obj->validType($hist['type'], true); ?></td>
											<td>
											<?php
											/**
											 * @wponly 	Format the datetime string
											 */
											echo JHtml::fetch('date', $hist['dt']);
											?>
											</td>
											<td><?php echo $hdescr; ?></td>
											<td><?php echo $currencyname.' '.VikBooking::numberFormat($hist['totpaid']); ?></td>
											<td><?php echo $currencyname.' '.VikBooking::numberFormat($hist['total']); ?></td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							</div>
						</div>
						<?php
					}
					?>
						<div id="vbinvnotesdiv" style="display: none;">
							<textarea name="invnotes" id="invnotes" class="vbadminnotestarea"><?php echo $row['inv_notes']; ?></textarea>
							<input type="submit" name="updinvnotes" value="<?php echo JText::translate('VBADMINNOTESUPD'); ?>" class="btn btn-success" />
							<button type="button" class="btn vbo-config-btn btn-secondary pull-right" onclick="document.getElementById('invnotes-hid').value=document.getElementById('invnotes').value;document.getElementById('vbo-gen-invoice').submit();"><?php VikBookingIcons::e('file-invoice'); ?> <?php echo JText::translate('VBOGENBOOKINGINVOICE'); ?></button>
						</div>
					<?php
					if (!empty($row['paymentlog'])) {
						?>
						<div id="vbpaymentlogdiv" style="display: none;">
						<?php
						// PCI Data Retrieval
						if (!empty($row['idorderota']) && !empty($row['channel'])) {
							$channel_source = $row['channel'];
							if (strpos($row['channel'], '_') !== false) {
								$channelparts = explode('_', $row['channel']);
								$channel_source = $channelparts[0];
							}
							$checkout_info = getdate($row['checkout']);
							/**
							 * Limit for accessing the credit card details has been changed to check-out
							 * day at 23:59:59 + 10 extra days. It used to be at 23:59:59 on check-out day.
							 * 
							 * @since 	1.13
							 */
							$cardlimit = mktime(23, 59, 59, $checkout_info['mon'], ($checkout_info['mday'] + 10), $checkout_info['year']);
							if (time() < $cardlimit) {
								$plain_log = htmlspecialchars($row['paymentlog']);
								if (stripos($plain_log, 'card number') !== false && strpos($plain_log, '****') !== false) {
									// log contains credit card details
									// Prepare modal (Credit Card Details)
									array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-pcid', JText::translate('GETFULLCARDDETAILS'), '', 'width: 80%; height: 60%; margin-left: -40%; top: 20% !important;'));
									// end Prepare modal
									?>
							<div class="vcm-notif-pcidrq-container">
								<a class="vcm-pcid-launch" onclick="vboOpenJModal('vbo-vcm-pcid', 'index.php?option=com_vikchannelmanager&task=execpcid&channel_source=<?php echo $channel_source; ?>&otaid=<?php echo $row['idorderota']; ?>&tmpl=component');" href="javascript: void(0);"><?php echo JText::translate('GETFULLCARDDETAILS'); ?></a>
							</div>
									<?php
								}
							}
						}
						//
						?>
							<pre style="min-height: 100%;"><?php echo htmlspecialchars($row['paymentlog']); ?></pre>
						</div>
						<script type="text/javascript">
						if (window.location.hash == '#paymentlog') {
							setTimeout(function() {
								jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
								vbToggleLog(document.getElementById('vbo-trig-paylogs'));
							}, 500);
						}
						</script>
						<?php
					}
					if (!$printreceipt && !is_null($messaging)) {
						?>
						<div id="vbmessagingdiv" style="display: none;">
							<?php echo $messaging->renderChat(); ?>
						</div>
						<script type="text/javascript">
						jQuery(document).ready(function() {
							if (window.location.hash == '#messaging') {
								setTimeout(function() {
									jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
									vbToggleMessaging(document.getElementById('vbo-trig-messaging'));
								}, 100);
							}
						});
						</script>
						<?php
					}
					?>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" name="task" value="editorder">
		<input type="hidden" name="vbo_active_tab" id="vbo_active_tab" value="">
		<input type="hidden" name="whereup" value="<?php echo $row['id']; ?>">
		<input type="hidden" name="cid[]" value="<?php echo $row['id']; ?>">
		<input type="hidden" name="option" value="com_vikbooking">
		<?php
		$tmpl = VikRequest::getVar('tmpl');
		if ($tmpl == 'component') {
			echo '<input type="hidden" name="tmpl" value="component">';
		}
		$pgoto = VikRequest::getString('goto', '', 'request');
		if ($pgoto == 'overv') {
			echo '<input type="hidden" name="goto" value="overv">';
		}
		?>
	</form>
</div>
<?php
foreach ($vbo_modals_html as $modalhtml) {
	echo $modalhtml;
}
?>
<form action="index.php?option=com_vikbooking&amp;task=orders" method="post" id="vbo-gen-invoice">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="orders" />
	<input type="hidden" name="cid[]" value="<?php echo $row['id']; ?>" />
	<input type="hidden" name="invnotes" id="invnotes-hid" value="" />
</form>
<div class="vbo-info-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-content-sendsms">
		<div id="vbo-overlay-sms-cont" style="display: none;">
			<h4><?php echo JText::translate('VBSENDSMSACTION'); ?>: <span id="smstophone-lbl"><?php echo $row['phone']; ?></span></h4>
			<form action="index.php?option=com_vikbooking" method="post">
				<div class="vbo-calendar-cfield-entry">
					<label for="smscont"><?php echo JText::translate('VBSENDSMSCUSTCONT'); ?></label>
					<span><textarea name="smscont" id="smscont" style="width: 99%; min-width: 99%;max-width: 99%; height: 35%;"></textarea></span>
				</div>
				<div class="vbo-calendar-cfields-bottom">
					<button type="submit" class="btn vbo-config-btn"><?php VikBookingIcons::e('comment'); ?> <?php echo JText::translate('VBSENDSMSACTION'); ?></button>
				</div>
				<input type="hidden" name="phone" id="smstophone" value="<?php echo $row['phone']; ?>" />
				<input type="hidden" name="goto" value="<?php echo urlencode('index.php?option=com_vikbooking&task=editorder&cid[]='.$row['id']); ?>" />
				<input type="hidden" name="task" value="sendcustomsms" />
			</form>
		</div>
		<div id="vbo-overlay-email-cont" style="display: none;">
			<h4><?php echo JText::translate('VBSENDEMAILACTION'); ?>: <span id="emailto-lbl"><?php echo $row['custmail']; ?></span></h4>
			<form action="index.php?option=com_vikbooking" method="post" enctype="multipart/form-data">
				<input type="hidden" name="bid" value="<?php echo $row['id']; ?>" />
			<?php
			$cur_emtpl = array();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='customemailtpls';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$cur_emtpl = $dbo->loadResult();
				$cur_emtpl = empty($cur_emtpl) ? array() : json_decode($cur_emtpl, true);
				$cur_emtpl = is_array($cur_emtpl) ? $cur_emtpl : array();
			}
			if (count($cur_emtpl) > 0) {
				?>
				<div class="vbo-calendar-custmail-tpls-wrap">
					<select id="emtpl-customemail" onchange="vboLoadEmailTpl(this.value);">
						<option value=""><?php echo JText::translate('VBEMAILCUSTFROMTPL'); ?></option>
					<?php
					foreach ($cur_emtpl as $emk => $emv) {
						?>
						<optgroup label="<?php echo $emv['emailsubj']; ?>">
							<option value="<?php echo $emk; ?>"><?php echo JText::translate('VBEMAILCUSTFROMTPLUSE'); ?></option>
							<option value="rm<?php echo $emk; ?>"><?php echo JText::translate('VBEMAILCUSTFROMTPLRM'); ?></option>
						</optgroup>
						<?php
					}
					?>
					</select>
				</div>
				<?php
			}

			/**
			 * Load all conditional text special tags.
			 * 
			 * @since 	1.14 (J) - 1.4.0 (WP)
			 */
			$extra_btns = array();
			$condtext_tags = VikBooking::getConditionalRulesInstance()->getSpecialTags();
			if (count($condtext_tags)) {
				$condtext_tags = array_keys($condtext_tags);
				foreach ($condtext_tags as $tag) {
					array_push($extra_btns, '<button type="button" class="btn btn-secondary btn-small vbo-condtext-specialtag-btn" onclick="setSpecialTplTag(\'emailcont\', \'' . $tag . '\');">' . $tag . '</button>');
				}
			}
			//
			?>
				<div class="vbo-calendar-cfields-wrap">
					<div class="vbo-calendar-cfield-entry">
						<label for="emailsubj"><?php echo JText::translate('VBSENDEMAILCUSTSUBJ'); ?></label>
						<span><input type="text" name="emailsubj" id="emailsubj" value="" size="30" /></span>
					</div>
					<div class="vbo-calendar-cfield-entry">
						<label for="emailcont"><?php echo JText::translate('VBSENDEMAILCUSTCONT'); ?></label>
						<textarea name="emailcont" id="emailcont" style="width: 99%; min-width: 99%; max-width: 99%; height: 120px; margin-bottom: 1px;"></textarea>
						<div class="btn-group pull-left vbo-smstpl-bgroup vbo-custmail-bgroup">
							<button onclick="setSpecialTplTag('emailcont', '{customer_name}');" class="btn btn-secondary btn-small" type="button">{customer_name}</button>
							<button onclick="setSpecialTplTag('emailcont', '{checkin_date}');" class="btn btn-secondary btn-small" type="button">{checkin_date}</button>
							<button onclick="setSpecialTplTag('emailcont', '{checkout_date}');" class="btn btn-secondary btn-small" type="button">{checkout_date}</button>
							<button onclick="setSpecialTplTag('emailcont', '{num_nights}');" class="btn btn-secondary btn-small" type="button">{num_nights}</button>
							<button onclick="setSpecialTplTag('emailcont', '{rooms_booked}');" class="btn btn-secondary btn-small" type="button">{rooms_booked}</button>
							<button onclick="setSpecialTplTag('emailcont', '{rooms_names}');" class="btn btn-secondary btn-small" type="button">{rooms_names}</button>
							<button onclick="setSpecialTplTag('emailcont', '{tot_adults}');" class="btn btn-secondary btn-small" type="button">{tot_adults}</button>
							<button onclick="setSpecialTplTag('emailcont', '{tot_children}');" class="btn btn-secondary btn-small" type="button">{tot_children}</button>
							<button onclick="setSpecialTplTag('emailcont', '{tot_guests}');" class="btn btn-secondary btn-small" type="button">{tot_guests}</button>
							<button onclick="setSpecialTplTag('emailcont', '{total}');" class="btn btn-secondary btn-small" type="button">{total}</button>
							<button onclick="setSpecialTplTag('emailcont', '{total_paid}');" class="btn btn-secondary btn-small" type="button">{total_paid}</button>
							<button onclick="setSpecialTplTag('emailcont', '{remaining_balance}');" class="btn btn-secondary btn-small" type="button">{remaining_balance}</button>
							<button onclick="setSpecialTplTag('emailcont', '{booking_id}');" class="btn btn-secondary btn-small" type="button">{booking_id}</button>
							<?php echo implode("\n", $extra_btns); ?>
						</div>
					</div>
					<div class="vbo-calendar-cfield-entry">
						<label for="emailattch"><?php echo JText::translate('VBSENDEMAILCUSTATTCH'); ?></label>
						<span><input type="file" name="emailattch" id="emailattch" /></span>
					</div>
					<div class="vbo-calendar-cfield-entry">
						<label for="emailfrom"><?php echo JText::translate('VBSENDEMAILCUSTFROM'); ?></label>
						<span><input type="text" name="emailfrom" id="emailfrom" value="<?php echo VikBooking::getSenderMail(); ?>" size="30" /></span>
					</div>
					<div class="vbo-calendar-cfields-bottom">
						<button type="submit" class="btn vbo-config-btn"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBSENDEMAILACTION'); ?></button>
					</div>
				</div>
				<input type="hidden" name="email" id="emailto" value="<?php echo $row['custmail']; ?>" />
				<input type="hidden" name="goto" value="<?php echo urlencode('index.php?option=com_vikbooking&task=editorder&cid[]='.$row['id']); ?>" />
				<input type="hidden" name="task" value="sendcustomemail" />
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
var vbo_overlay_on = false;
var vbo_print_only = false;
if (jQuery.isFunction(jQuery.fn.tooltip)) {
	jQuery(".hasTooltip").tooltip();
}
function vboToggleSendSMS() {
	var cur_phone = jQuery("#smstophone").val();
	var phone_set = jQuery("#custphone").trigger('vboupdatephonenumber').val();
	if (phone_set.length && phone_set != cur_phone) {
		jQuery("#smstophone").val(phone_set);
		jQuery("#smstophone-lbl").text(phone_set);
	}
	jQuery("#vbo-overlay-email-cont").hide();
	jQuery("#vbo-overlay-sms-cont").show();
	jQuery(".vbo-info-overlay-block").fadeToggle(400, function() {
		if (jQuery(".vbo-info-overlay-block").is(":visible")) {
			vbo_overlay_on = true;
		} else {
			vbo_overlay_on = false;
		}
	});
}
function vboToggleSendEmail() {
	var cur_email = jQuery("#emailto").val();
	var email_set = jQuery("#custmail").val();
	if (email_set.length && email_set != cur_email) {
		jQuery("#emailto").val(email_set);
		jQuery("#emailto-lbl").text(email_set);
	}
	jQuery("#vbo-overlay-sms-cont").hide();
	jQuery("#vbo-overlay-email-cont").show();
	jQuery(".vbo-info-overlay-block").fadeToggle(400, function() {
		if (jQuery(".vbo-info-overlay-block").is(":visible")) {
			vbo_overlay_on = true;
		} else {
			vbo_overlay_on = false;
		}
	});
}
function setSpecialTplTag(taid, tpltag) {
	var tplobj = document.getElementById(taid);
	if (tplobj != null) {
		var start = tplobj.selectionStart;
		var end = tplobj.selectionEnd;
		tplobj.value = tplobj.value.substring(0, start) + tpltag + tplobj.value.substring(end);
		tplobj.selectionStart = tplobj.selectionEnd = start + tpltag.length;
		tplobj.focus();
	}
}
jQuery(document).ready(function() {
	// sessionStorage for current tab
	if (typeof sessionStorage !== 'undefined' && !window.location.hash && <?php echo $printreceipt ? 'false' : 'true'; ?>) {
		var curtab = sessionStorage.getItem('vboEditOrderTab<?php echo $row['id']; ?>');
		switch (curtab) {
			case 'notes' :
				setTimeout(function() {
					jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
					vbToggleNotes(document.getElementById('vbo-trig-notes'));
				}, 100);
				break;
			case 'history' :
				setTimeout(function() {
					jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
					vbToggleHistory(document.getElementById('vbo-trig-bookhistory'));
				}, 100);
				break;
			case 'invnotes' :
				setTimeout(function() {
					jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
					vbToggleInvNotes(document.getElementById('vbo-trig-invnotes'));
				}, 100);
				break;
			case 'paylogs' :
				setTimeout(function() {
					jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
					vbToggleLog(document.getElementById('vbo-trig-paylogs'));
				}, 100);
				break;
			case 'messaging' :
				setTimeout(function() {
					jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
					vbToggleMessaging(document.getElementById('vbo-trig-messaging'));
				}, 100);
				break;
			default :
				break;
		}
	}
	if (window.location.hash == '#tab-admin') {
		setTimeout(function() {
			jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
		}, 100);
	}
	// Search customer - Start
	var vbocustsdelay = (function() {
		var timer = 0;
		return function(callback, ms) {
			clearTimeout(timer);
			timer = setTimeout(callback, ms);
		};
	})();
	function vboCustomerSearch(words) {
		jQuery("#vbo-searchcust-res").hide().html("");
		jQuery("#vbo-searchcust-loading").show();
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "index.php",
			data: { option: "com_vikbooking", task: "searchcustomer", kw: words, tmpl: "component" }
		}).done(function(cont) {
			if (cont.length) {
				var obj_res = JSON.parse(cont);
				customers_search_vals = obj_res[0];
				jQuery("#vbo-searchcust-res").html(obj_res[1]);
			} else {
				customers_search_vals = "";
				jQuery("#vbo-searchcust-res").html("----");
			}
			jQuery("#vbo-searchcust-res").show();
			jQuery("#vbo-searchcust-loading").hide();
		}).fail(function() {
			jQuery("#vbo-searchcust-loading").hide();
			alert("Error Searching.");
		});
	}
	jQuery("#vbo-searchcust").keyup(function(event) {
		vbocustsdelay(function() {
			var keywords = jQuery("#vbo-searchcust").val();
			var chars = keywords.length;
			if (chars > 1) {
				if ((event.which > 96 && event.which < 123) || (event.which > 64 && event.which < 91) || event.which == 13) {
					vboCustomerSearch(keywords);
				}
			} else {
				if (jQuery("#vbo-searchcust-res").is(":visible")) {
					jQuery("#vbo-searchcust-res").hide();
				}
			}
		}, 600);
	});
	jQuery("body").on("click", ".vbo-custsearchres-entry", function() {
		var custid = jQuery(this).attr("data-custid");
		if (confirm('<?php echo addslashes(JText::translate('VBOASSIGNNEWCUSTCONF')); ?>')) {
			jQuery('#newcustid').val(custid);
			document.adminForm.submit();
			return;
		}
	});
	// Search customer - End
	jQuery(document).mouseup(function(e) {
		if (!vbo_overlay_on) {
			return false;
		}
		var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
		if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
			jQuery(".vbo-info-overlay-block").fadeOut();
			vbo_overlay_on = false;
		}
	});
	jQuery(document).keyup(function(e) {
		if (e.keyCode == 27 && vbo_overlay_on) {
			jQuery(".vbo-info-overlay-block").fadeOut();
			vbo_overlay_on = false;
		}
	});
	jQuery(".vbo-bookingdet-tab").click(function() {
		var newtabrel = jQuery(this).attr('data-vbotab');
		var oldtabrel = jQuery(".vbo-bookingdet-tab-active").attr('data-vbotab');
		if (newtabrel == oldtabrel) {
			return;
		}
		if (newtabrel == 'vbo-tab-details' && typeof sessionStorage !== 'undefined') {
			sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', 'details');
		}
		jQuery(".vbo-bookingdet-tab").removeClass("vbo-bookingdet-tab-active");
		jQuery(this).addClass("vbo-bookingdet-tab-active");
		jQuery("#"+oldtabrel).hide();
		jQuery("#"+newtabrel).fadeIn();
		jQuery("#vbo_active_tab").val(newtabrel);
	});
	jQuery(".vbo-bookingdet-tab[data-vbotab='<?php echo $pactive_tab; ?>']").trigger('click');
	// edit amount paid
	jQuery('#vbo-amountpaid-edit').click(function() {
		jQuery('#vbo-amountpaid-cont').hide();
		jQuery('#vbo-amountpaid-modcont').show();
		jQuery('input[name="newamountpaid"]').prop('disabled', false);
		if (jQuery('input[name="newamountrefunded"]').length) {
			jQuery('input[name="newamountrefunded"]').val('');
		}
	});
	jQuery('#vbo-amountpaid-cancedit').click(function() {
		jQuery('#vbo-amountpaid-modcont').hide();
		jQuery('#vbo-amountpaid-cont').show();
	});
	// edit amount refunded
	jQuery('#vbo-amountrefunded-edit').click(function() {
		jQuery('#vbo-amountrefunded-cont').hide();
		jQuery('#vbo-amountrefunded-modcont').show();
		jQuery('input[name="newamountrefunded"]').prop('disabled', false);
		if (jQuery('input[name="newamountpaid"]').length) {
			jQuery('input[name="newamountpaid"]').val('');
		}
	});
	jQuery('#vbo-amountrefunded-cancedit').click(function() {
		jQuery('#vbo-amountrefunded-modcont').hide();
		jQuery('#vbo-amountrefunded-cont').show();
	});
	// edit amount payable
	jQuery('#vbo-amountpayable-edit').click(function() {
		jQuery('#vbo-amountpayable-cont').hide();
		jQuery('#vbo-amountpayable-modcont').show();
		jQuery('input[name="newamountrefunded"]').prop('disabled', false);
	});
	jQuery('#vbo-amountpayable-cancedit').click(function() {
		jQuery('#vbo-amountpayable-modcont').hide();
		jQuery('#vbo-amountpayable-cont').show();
	});
	// chat listener
	if (typeof VCMChat !== 'undefined') {
		jQuery(window).on('chatsync', function(e) {
			if (!jQuery("#vcm-chat-audio-notification").length) {
				jQuery("body").append("<audio id=\"vcm-audio-notification\" preload=\"auto\"><source type=\"audio/mp3\" src=\"<?php echo VCM_ADMIN_URI; ?>assets/css/audio/new_chat_message.mp3\"></source></audio>");
			}
			try {
				var promise = document.getElementById('vcm-audio-notification').play();
				console.log(promise);
			} catch (err) {
				console.warn('Could not play sound', err);
			}
		});
	}
});
var cur_emtpl = <?php echo json_encode($cur_emtpl); ?>;
function vboLoadEmailTpl(tplind) {
	if (!(tplind.length > 0)) {
		jQuery('#emailsubj').val('');
		jQuery('#emailcont').val('');
		return true;
	}
	if (tplind.substr(0, 2) == 'rm') {
		if (confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>')) {
			document.location.href = 'index.php?option=com_vikbooking&task=rmcustomemailtpl&cid[]=<?php echo $row['id']; ?>&tplind='+tplind.substr(2);
		}
		return false;
	}
	if (!cur_emtpl.hasOwnProperty(tplind)) {
		jQuery('#emailsubj').val('');
		jQuery('#emailcont').val('');
		return true;
	}
	jQuery('#emailsubj').val(cur_emtpl[tplind]['emailsubj']);
	jQuery('#emailcont').val(cur_emtpl[tplind]['emailcont']);
	jQuery('#emailfrom').val(cur_emtpl[tplind]['emailfrom']);
	return true;
}
<?php
$pcustomemail = VikRequest::getInt('customemail', '', 'request');
if ($pcustomemail > 0) {
	?>
	vboToggleSendEmail();
	<?php
}
if ($printreceipt) {
	?>
jQuery(document).ready(function() {
	jQuery('button, .vbo-bookingdet-innertop').hide();
	jQuery('body').find('a').each(function(k, v) {
		jQuery(this).replaceWith(jQuery(this).html());
	});
	jQuery('body').find("input[type='text']").each(function(k, v) {
		jQuery(this).replaceWith(jQuery(this).val());
	});
});
function vboMakePrintOnly() {
	vbo_print_only = true;
	jQuery(".vbo-receipt-numdate-block").remove();
	jQuery("#vbo-receipt-print-btn-name").text("<?php echo addslashes(JText::translate('VBOPRINT')); ?>");
}
function vboLaunchPrintReceipt() {
	var rcnotes = jQuery('#vbo-receipt-notes').val();
	var rnewnum = jQuery('#vbo-receipt-num-inp').val();
	if (rcnotes.length) {
		if (rcnotes.indexOf('<') < 0) {
			rcnotes = rcnotes.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br />$2');
		}
		jQuery('#vbo-receipt-notes-val').html(rcnotes);
	} else {
		jQuery('.vbo-receipt-notes-container').remove();
	}
	if (vbo_print_only === true) {
		window.print();
		return;
	}
	jQuery.ajax({
		type: "POST",
		url: "index.php?option=com_vikbooking&task=updatereceiptnum&tmpl=component",
		data: { newnum: rnewnum, newnotes: rcnotes, oid: "<?php echo $row['id']; ?>" }
	}).done(function(res) {
		window.print();
	}).fail(function() {
		alert('Could not update the next receipt number.')
		window.print();
	});
}
	<?php
}
?>
</script>
