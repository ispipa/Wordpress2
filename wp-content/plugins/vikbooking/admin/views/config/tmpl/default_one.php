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

JHtml::fetch('jquery.framework', true, true);
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.sortable.min.js');

$vbo_app = new VboApplication();
$timeopst = VikBooking::getTimeOpenStore(true);
if (is_array($timeopst)) {
	$openat = VikBooking::getHoursMinutes($timeopst[0]);
	$closeat = VikBooking::getHoursMinutes($timeopst[1]);
} else {
	$openat = array(0, 0);
	$closeat = array(0, 0);
}
$wcheckintime = "<select name=\"timeopenstorefh\">\n";
for ($i = 0; $i <= 23; $i++) {
	if ($i < 10) {
		$in = "0".$i;
	} else {
		$in = $i;
	}
	$stat = $openat[0] == $i ? " selected=\"selected\"" : "";
	$wcheckintime .= "<option value=\"".$i."\"".$stat.">".$in."</option>\n";
}
$wcheckintime .= "</select> <select name=\"timeopenstorefm\">\n";
for ($i = 0; $i <= 59; $i++) {
	if ($i < 10) {
		$in = "0".$i;
	} else {
		$in = $i;
	}
	$stat = $openat[1] == $i ? " selected=\"selected\"" : "";
	$wcheckintime .= "<option value=\"".$i."\"".$stat.">".$in."</option>\n";
}
$wcheckintime .= "</select>\n";
$wcheckouttime = "<select name=\"timeopenstoreth\">\n";
for ($i = 0; $i <= 23; $i++) {
	if ($i < 10) {
		$in = "0".$i;
	} else {
		$in = $i;
	}
	$stat = $closeat[0]==$i ? " selected=\"selected\"" : "";
	$wcheckouttime .= "<option value=\"".$i."\"".$stat.">".$in."</option>\n";
}
$wcheckouttime .= "</select> <select name=\"timeopenstoretm\">\n";
for ($i = 0; $i <= 59; $i++) {
	if ($i < 10) {
		$in = "0".$i;
	} else {
		$in = $i;
	}
	$stat = $closeat[1] == $i ? " selected=\"selected\"" : "";
	$wcheckouttime .= "<option value=\"".$i."\"".$stat.">".$in."</option>\n";
}
$wcheckouttime .= "</select>\n";

$calendartype = VikBooking::calendarType(true);

$globnumadults = VikBooking::getSearchNumAdults(true);
$adultsparts = explode('-', $globnumadults);
$globnumchildren = VikBooking::getSearchNumChildren(true);
$childrenparts = explode('-', $globnumchildren);

$maxdatefuture = VikBooking::getMaxDateFuture(true);
$maxdate_val = intval(substr($maxdatefuture, 1, (strlen($maxdatefuture) - 1)));
$maxdate_interval = substr($maxdatefuture, -1, 1);

$smartseach_type = VikBooking::getSmartSearchType(true);

$vbosef = file_exists(VBO_SITE_PATH.DS.'router.php');

$vcm_autoupd  		= (int)VikBooking::vcmAutoUpdate();
$chat_enabled 		= (int)VikBooking::chatEnabled();
$precheckin_enabled = (int)VikBooking::precheckinEnabled();
$upselling_enabled  = (int)VikBooking::upsellingEnabled();

$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$usedf = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$usedf = 'm/d/Y';
} else {
	$usedf = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
?>
<script type="text/javascript">

function vboRemoveElement(el) {
	return (elem=document.getElementById(el)).parentNode.removeChild(elem);
}

function vboAddClosingDate() {
	var cdfrom = document.getElementById('cdfrom').value;
	var cdto = document.getElementById('cdto').value;
	if (cdfrom.length && cdto.length) {
		var cdcounter = document.getElementsByClassName('vbo-closed-date-entry').length + 1;
		var cdstring = "<div class=\"vbo-closed-date-entry\" id=\"vbo-closed-date-entry"+cdcounter+"\"><span>"+cdfrom+"</span> - <span>"+cdto+"</span> <span class=\"vbo-closed-date-rm\" onclick=\"vboRemoveElement('vbo-closed-date-entry"+cdcounter+"');\"><i class=\"vboicn-cross\"></i> </span><input type=\"hidden\" name=\"cdsfrom[]\" value=\""+cdfrom+"\" /><input type=\"hidden\" name=\"cdsto[]\" value=\""+cdto+"\" /></div>";
		document.getElementById('vbo-config-closed-dates').innerHTML += cdstring;
		document.getElementById('cdfrom').value = '';
		document.getElementById('cdto').value = '';
	}
}

function vboChangeMultiRoomSearch(type) {
	if (type == 'classic') {
		jQuery('.vbo-param-container[data-vbosearchtpl="classic"]').show();
	} else {
		jQuery('.vbo-param-container[data-vbosearchtpl="classic"]').hide();
	}
}

var chatenabled = <?php echo $chat_enabled; ?>;
var precheckinenabled = <?php echo $precheckin_enabled; ?>;

jQuery(document).ready(function() {

	jQuery('input[name="chatenabled"]').change(function() {
		if (chatenabled < 0) {
			jQuery('#chat-params-tr').hide();
			return;
		}
		if (jQuery(this).is(':checked') && parseInt(jQuery(this).val()) > 0) {
			jQuery('#chat-params-tr').fadeIn();
		} else {
			jQuery('#chat-params-tr').fadeOut();
		}
	});

	jQuery('input[name="precheckinenabled"]').change(function() {
		if (precheckinenabled < 0) {
			jQuery('#precheckin-params-tr').hide();
			return;
		}
		if (jQuery(this).is(':checked') && parseInt(jQuery(this).val()) > 0) {
			jQuery('#precheckin-params-tr').fadeIn();
		} else {
			jQuery('#precheckin-params-tr').fadeOut();
		}
	});

});

</script>

<div class="vbo-config-maintab-left">
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMBOOKING'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label">
						<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBCONFIGVCMAUTOUPD'), 'content' => JText::translate('VBCONFIGVCMAUTOUPDHELP'), 'icon_class' => 'vboicn-lifebuoy')); ?>
						<?php echo JText::translate('VBCONFIGVCMAUTOUPD'); ?>
					</div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('vcmautoupd', JText::translate('VBYES'), JText::translate('VBNO'), ($vcm_autoupd < 0 ? 0 : $vcm_autoupd), 1, 0).($vcm_autoupd < 0 ? '<span class="vbo-config-warn">'.JText::translate('VBCONFIGVCMAUTOUPDMISS').'</span>' : ''); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONEFIVE'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('allowbooking', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::allowBooking(), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONESIX'); ?></div>
					<div class="vbo-param-setting"><textarea name="disabledbookingmsg" rows="5" cols="50"><?php echo VikBooking::getDisabledBookingMsg(); ?></textarea></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONETENSIX'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="adminemail" value="<?php echo VikBooking::getAdminMail(); ?>" size="35"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBSENDEREMAIL'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="senderemail" value="<?php echo VikBooking::getSenderMail(); ?>" size="35"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONESEVEN'); ?></div>
					<div class="vbo-param-setting"><?php echo $wcheckintime; ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONETHREE'); ?></div>
					<div class="vbo-param-setting"><?php echo $wcheckouttime; ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONEELEVEN'); ?></div>
					<div class="vbo-param-setting">
						<select name="dateformat">
							<option value="%d/%m/%Y"<?php echo ($nowdf=="%d/%m/%Y" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBCONFIGONETWELVE'); ?></option>
							<option value="%m/%d/%Y"<?php echo ($nowdf=="%m/%d/%Y" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBCONFIGONEMDY'); ?></option>
							<option value="%Y/%m/%d"<?php echo ($nowdf=="%Y/%m/%d" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBCONFIGONETENTHREE'); ?></option>
						</select>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGDATESEP'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="datesep" value="<?php echo $datesep; ?>" size="3"/></div>
				</div>
				<?php
				$resmodcanc = VikBooking::getReservationModCanc();
				$resmodcancmin = VikBooking::getReservationModCancMin();
				?>
				<div class="vbo-param-container">
					<div class="vbo-param-label">
						<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOCONFIGALLOWMODCANC'), 'content' => JText::translate('VBOCONFIGALLOWMODCANCHELP'))); ?>
						<?php echo JText::translate('VBOCONFIGALLOWMODCANC'); ?>
					</div>
					<div class="vbo-param-setting">
						<script type="text/javascript">
						function vboChangeResModCanc(mode) {
							mode = parseInt(mode);
							document.getElementById('vbo-resmodcanc-lim').style.display = (mode > 0 ? 'flex' : 'none');
						}
						</script>
						<div class="vbo-resmodcanc-block">
							<select name="resmodcanc" onchange="vboChangeResModCanc(this.value);">
								<option value="0"<?php echo $resmodcanc == 0 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGMODCANC0'); ?></option>
								<option value="1"<?php echo $resmodcanc == 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGMODCANC1'); ?></option>
								<option value="2"<?php echo $resmodcanc == 2 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGMODCANC2'); ?></option>
								<option value="3"<?php echo $resmodcanc == 3 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGMODCANC3'); ?></option>
								<option value="4"<?php echo $resmodcanc == 4 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGMODCANC4'); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="vbo-param-container vbo-resmodcanc-lim vbo-param-nested" id="vbo-resmodcanc-lim" style="display: <?php echo $resmodcanc > 0 ? 'flex' : 'none'; ?>;">
					<div class="vbo-param-label"><label for="resmodcancmin"><?php echo JText::translate('VBOCONFIGMODCANCMINDAYS'); ?></label></div>
					<div class="vbo-param-setting"><input type="number" min="0" name="resmodcancmin" id="resmodcancmin" style="margin: 0;" value="<?php echo $resmodcancmin; ?>" /></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTODAYBOOKINGS'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('todaybookings', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::todayBookings(), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONECOUPONS'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('enablecoupons', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::couponsEnabled(), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGENABLECUSTOMERPIN'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('enablepin', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::customersPinEnabled(), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONETENFIVE'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('tokenform', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::tokenForm() ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGREQUIRELOGIN'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('requirelogin', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::requireLogin(), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBCONFIGAUTODISTFEATURE'), 'content' => JText::translate('VBCONFIGAUTODISTFEATUREHELP'))); ?> <?php echo JText::translate('VBCONFIGAUTODISTFEATURE'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('autoroomunit', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::autoRoomUnit() ? 1 : 0), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBCONFIGCHATENABLED'), 'content' => JText::translate('VBCONFIGCHATENABLEDHELP'))); ?> <?php echo JText::translate('VBCONFIGCHATENABLED'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('chatenabled', JText::translate('VBYES'), JText::translate('VBNO'), ($chat_enabled < 0 ? 0 : $chat_enabled), 1, 0).($chat_enabled < 0 ? '<span class="vbo-config-warn">'.JText::translate('VBCONFIGVCMAUTOUPDMISS').'</span>' : ''); ?></div>
				</div>
				<?php
				$chat_params = VikBooking::getChatParams();
				?>
				<div class="vbo-param-container vbo-param-nested" id="chat-params-tr" style="display: <?php echo $chat_enabled > 0 ? 'flex' : 'none'; ?>;">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGCHATPARAMS'); ?></div>
					<div class="vbo-param-setting">
						<select name="chat_res_status" style="max-width: 345px; margin: 0;">
							<option value="confirmed;standy;cancelled"<?php echo isset($chat_params->res_status) && count($chat_params->res_status) >= 3 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGCHATRESSTATALL'); ?></option>
							<option value="confirmed"<?php echo isset($chat_params->res_status) && count($chat_params->res_status) === 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGCHATRESSTATCONF'); ?></option>
						</select>
						<div style="margin-top: 5px;">
							<select name="chat_av_type" style="max-width: 345px; margin: 0;">
								<option value="checkin"<?php echo isset($chat_params->av_type) && $chat_params->av_type == 'checkin' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGCHATAVCHECKIN'); ?></option>
								<option value="checkout"<?php echo isset($chat_params->av_type) && $chat_params->av_type == 'checkout' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGCHATAVCHECKOUT'); ?></option>
							</select>
							<input type="number" name="chat_av_days" value="<?php echo isset($chat_params->av_days) ? (int)$chat_params->av_days : '0'; ?>" style="margin: 0;"/>
						</div>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGPRECHECKINENABLED'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('precheckinenabled', JText::translate('VBYES'), JText::translate('VBNO'), $precheckin_enabled, 1, 0); ?></div>
				</div>
				<div class="vbo-param-container vbo-param-nested" id="precheckin-params-tr" style="display: <?php echo $precheckin_enabled > 0 ? 'flex' : 'none'; ?>;">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGPRECHECKINMIND'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="precheckinminoffset" value="<?php echo VikBooking::precheckinMinOffset(); ?>" min="0" /></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBCONFIGUPSELLINGENABLED'), 'content' => JText::translate('VBCONFIGUPSELLINGENABLEDHELP'))); ?> <?php echo JText::translate('VBCONFIGUPSELLINGENABLED'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('upsellingenabled', JText::translate('VBYES'), JText::translate('VBNO'), $upselling_enabled, 1, 0); ?></div>
				</div>
				<?php
				$orphans_calcm = VikBooking::orphansCalculation();
				?>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOCONFIGORPHANSCALCM'), 'content' => JText::translate('VBOCONFIGORPHANSCALCMHELP'))); ?> <?php echo JText::translate('VBOCONFIGORPHANSCALCM'); ?></div>
					<div class="vbo-param-setting">
						<select name="orphanscal">
							<option value="next"<?php echo $orphans_calcm == 'next' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGORPHANSCALCMN'); ?></option>
							<option value="prevnext"<?php echo $orphans_calcm == 'prevnext' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGORPHANSCALCMPN'); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<div class="vbo-config-maintab-right">

	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBCONFIGSEARCHPARAMS'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGMINDAYSADVANCE'); ?></div>
					<?php
					/**
					 * Do not ever change the argument "true" from VikBooking::getMinDaysAdvance(true); as it is
					 * now used to detect that the back-end is calling it, and no closing dates should be applied.
					 * 
					 * @since 	1.14 (J) - 1.4.0 (WP)
					 */
					?>
					<div class="vbo-param-setting"><input type="number" name="mindaysadvance" value="<?php echo VikBooking::getMinDaysAdvance(true); ?>" min="0"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSEARCHDEFNIGHTS'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="autodefcalnights" value="<?php echo VikBooking::getDefaultNightsCalendar(true); ?>" min="0"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSEARCHPNUMROOM'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="numrooms" value="<?php echo VikBooking::getSearchNumRooms(true); ?>" min="0"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSEARCHPNUMADULTS'); ?></div>
					<div class="vbo-param-setting"><?php echo JText::translate('VBCONFIGSEARCHPFROM'); ?> <input type="number" name="numadultsfrom" value="<?php echo $adultsparts[0]; ?>" min="0"/> &nbsp;&nbsp; <?php echo JText::translate('VBCONFIGSEARCHPTO'); ?> <input type="number" name="numadultsto" value="<?php echo $adultsparts[1]; ?>" min="0"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSEARCHPNUMCHILDREN'); ?></div>
					<div class="vbo-param-setting"><?php echo JText::translate('VBCONFIGSEARCHPFROM'); ?> <input type="number" name="numchildrenfrom" value="<?php echo $childrenparts[0]; ?>" min="0"/> &nbsp;&nbsp; <?php echo JText::translate('VBCONFIGSEARCHPTO'); ?> <input type="number" name="numchildrento" value="<?php echo $childrenparts[1]; ?>" min="0"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEFUT'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="maxdate" value="<?php echo $maxdate_val; ?>" min="0"/> <select name="maxdateinterval"><option value="d"<?php echo $maxdate_interval == 'd' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEDAYS'); ?></option><option value="w"<?php echo $maxdate_interval == 'w' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEWEEKS'); ?></option><option value="m"<?php echo $maxdate_interval == 'm' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEMONTHS'); ?></option><option value="y"<?php echo $maxdate_interval == 'y' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPMAXDATEYEARS'); ?></option></select></div>
				</div>

				<div class="vbo-param-container">
					<div class="vbo-param-label">
						<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBCONFIGCLOSINGDATES'), 'content' => JText::translate('VBCONFIGCLOSINGDATESHELP'))); ?>
						<?php echo JText::translate('VBCONFIGCLOSINGDATES'); ?>
					</div>
					<div class="vbo-param-setting">
						<div style="width: 100%; display: inline-block;" class="btn-toolbar" id="filter-bar">
							<div class="btn-group pull-left">
								<?php echo $vbo_app->getCalendar('', 'cdfrom', 'cdfrom', $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true', 'placeholder' => JText::translate('VBCONFIGCLOSINGDATEFROM'))); ?>
							</div>
							<div class="btn-group pull-left">
								<?php echo $vbo_app->getCalendar('', 'cdto', 'cdto', $nowdf, array('class'=>'', 'size'=>'10', 'maxlength'=>'19', 'todayBtn' => 'true', 'placeholder' => JText::translate('VBCONFIGCLOSINGDATETO'))); ?>
							</div>
							<div class="btn-group pull-left">
								<button type="button" class="btn vbo-config-btn" onclick="vboAddClosingDate();"><i class="icon-new"></i><?php echo JText::translate('VBCONFIGCLOSINGDATEADD'); ?></button>
							</div>
						</div>
						<div id="vbo-config-closed-dates" style="display: block;">
					<?php
					$cur_closed_dates = VikBooking::getClosingDates();
					if (is_array($cur_closed_dates) && count($cur_closed_dates)) {
						foreach ($cur_closed_dates as $kcd => $vcd) {
							echo "<div class=\"vbo-closed-date-entry\" id=\"vbo-closed-date-entry".$kcd."\"><span>".date(str_replace("/", $datesep, $usedf), $vcd['from'])."</span> - <span>".date(str_replace("/", $datesep, $usedf), $vcd['to'])."</span> <span class=\"vbo-closed-date-rm\" onclick=\"vboRemoveElement('vbo-closed-date-entry".$kcd."');\"><i class=\"vboicn-cross\"></i> </span><input type=\"hidden\" name=\"cdsfrom[]\" value=\"".date($usedf, $vcd['from'])."\" /><input type=\"hidden\" name=\"cdsto[]\" value=\"".date($usedf, $vcd['to'])."\" /></div>"."\n";
						}
					}
					?>
						</div>
					</div>
				</div>

				<?php
				/**
				 * Choose the template file for the View "search". If compact, the "smartsearch" parameter
				 * will be ignored and set to hidden, because this new template does not use it.
				 * 
				 * @since 	1.13
				 */
				$search_tpl = VikBooking::searchResultsTmpl();
				?>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGSEARCHRESTPL'); ?></div>
					<div class="vbo-param-setting">
						<select name="srcrtpl" onchange="vboChangeMultiRoomSearch(this.value);">
							<option value="compact"<?php echo $search_tpl == 'compact' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGSEARCHRESTPLCM'); ?></option>
							<option value="classic"<?php echo $search_tpl == 'classic' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGSEARCHRESTPLCL'); ?></option>
						</select>
					</div>
				</div>
				<div class="vbo-param-container vbo-param-nested" data-vbosearchtpl="classic" style="<?php echo $search_tpl != 'classic' ? 'display: none;' : ''; ?>">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSEARCHPSMARTSEARCH'); ?></div>
					<div class="vbo-param-setting">
						<select name="smartsearch">
							<option value="dynamic"<?php echo $smartseach_type == 'dynamic' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPSMARTSEARCHDYN'); ?></option>
							<option value="automatic"<?php echo $smartseach_type == 'automatic' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSEARCHPSMARTSEARCHAUTO'); ?></option>
						</select>
					</div>
				</div>
				<div class="vbo-param-container vbo-param-nested" data-vbosearchtpl="classic" style="<?php echo $search_tpl != 'classic' ? 'display: none;' : ''; ?>">
					<div class="vbo-param-label">
						<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBO_INTERACTIVE_MAP_BOOK'), 'content' => JText::translate('VBO_INTERACTIVE_MAP_BOOK_HELP'))); ?>
						<?php echo JText::translate('VBO_INTERACTIVE_MAP_BOOK'); ?>
					</div>
					<div class="vbo-param-setting">
						<?php echo $vbo_app->printYesNoButtons('interactive_map', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::interactiveMapEnabled(), 1, 0); ?>
					</div>
				</div>
				<?php
				$searchsugg = (int)VikBooking::showSearchSuggestions();
				?>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGSHOWSEARCHSUGG'); ?></div>
					<div class="vbo-param-setting">
						<select name="searchsuggestions">
							<option value="1"<?php echo $searchsugg == 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOYESWITHAVAILABILITY'); ?></option>
							<option value="2"<?php echo $searchsugg == 2 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOYESNOAVAILABILITY'); ?></option>
							<option value="0"<?php echo $searchsugg == 0 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBNO'); ?></option>
						</select>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONETENFOUR'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('showcategories', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::showCategoriesFront(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSHOWCHILDREN'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('showchildren', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::showChildrenFront(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
			</div>
		</div>
	</fieldset>

	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMSYSTEM'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGCRONKEY'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="cronkey" value="<?php echo VikBooking::getCronKey(); ?>" size="6" /></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGMULTILANG'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('multilang', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::allowMultiLanguage(true), 1, 0); ?></div>
				</div>
				<!-- @wponly  we cannot display the setting for the SEF Router -->
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBLOADBOOTSTRAP'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('loadbootstrap', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::loadBootstrap(true), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOLOADFA'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('usefa', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::isFontAwesomeEnabled(true), 1, 0); ?></div>
				</div>
				<!-- @wponly  jQuery main library should not be loaded as it's already included by WP -->
				<!-- @wponly  calendar type should be always jQuery -->
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONECALENDAR'); ?></div>
					<div class="vbo-param-setting"><select name="calendar"><option value="jqueryui"<?php echo ($calendartype == "jqueryui" ? " selected=\"selected\"" : ""); ?>>jQuery UI</option></select></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_GMAPS_APIKEY'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="gmapskey" value="<?php echo VikBooking::getGoogleMapsKey(); ?>" size="30" /></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label">
						<?php echo JText::translate('VBOPREFCOUNTRIESORD'); ?> 
						<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOPREFCOUNTRIESORD'), 'content' => JText::translate('VBOPREFCOUNTRIESORDHELP'))); ?>
						<div class="vbo-preferred-countries-edit-wrap">
							<span onclick="vboDisplayCustomPrefCountries();"><?php VikBookingIcons::e('edit'); ?></span>
						</div>
					</div>
					<div class="vbo-param-setting">
						<ul class="vbo-preferred-countries-sortlist">
						<?php
						$preferred_countries = VikBooking::preferredCountriesOrdering(true);
						foreach ($preferred_countries as $ccode => $langname) {
							?>
							<li class="vbo-preferred-countries-elem">
								<span><?php VikBookingIcons::e('ellipsis-v'); ?> <?php echo $langname; ?></span>
								<input type="hidden" name="pref_countries[]" value="<?php echo $ccode; ?>" />
							</li>
							<?php
						}
						?>
						</ul>
						<script type="text/javascript">
						function vboDisplayCustomPrefCountries() {
							var all_countries = new Array;
							jQuery('input[name="pref_countries[]"]').each(function() {
								all_countries.push(jQuery(this).val());
							});
							var current_countries = all_countries.join(', ');
							var custom_countries = prompt("<?php echo addslashes(JText::translate('VBOPREFCOUNTRIESORD')); ?>", current_countries);
							if (custom_countries != null && custom_countries != current_countries) {
								jQuery('.vbo-preferred-countries-edit-wrap').append('<input type="hidden" name="cust_pref_countries" value="' + custom_countries + '"/>');
								jQuery('#adminForm').find('input[name="task"]').val('saveconfig');
								jQuery('#adminForm').submit();
							}
						}
						jQuery(document).ready(function() {
							jQuery('.vbo-preferred-countries-sortlist').sortable();
							jQuery('.vbo-preferred-countries-sortlist').disableSelection();
						});
						</script>
					</div>
				</div>
			</div>
		</div>
	</fieldset>

<?php
/**
 * @wponly 	trigger event onDisplayViewConfigGlobal to display additional parameters
 */
$extra_forms = JFactory::getApplication()->triggerEvent('onDisplayViewConfigGlobal', array($this));
foreach ($extra_forms as $extra_form) {
	foreach ($extra_form as $form_name => $form_html) {
		?>
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate($form_name); ?></legend>
			<?php echo $form_html; ?>
		</div>
	</fieldset>
		<?php
	}
}
//
?>

</div>

