<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

 // No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

class VikBookingHelper
{
	public static function printHeader($highlight = "")
	{
		$cookie = JFactory::getApplication()->input->cookie;
		$tmpl = VikRequest::getVar('tmpl');
		if ($tmpl == 'component') {
			return;
		}
		$view = VikRequest::getVar('view');
		/**
		 * @wponly Hide menu for Pro-update views
		 */
		$skipmenu = array('getpro');
		if (in_array($view, $skipmenu)) {
			return;
		}
		//
		$channel_manager_btn = '';
		if (file_exists(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			$channel_manager_btn = '<li><span class="vmenulink"><a href="index.php?option=com_vikchannelmanager" class="vcmlink"><i class="vboicn-cloud"></i>'.JText::translate('VBMENUCHANNELMANAGER').'</a></span></li>';
		}
		$backlogo = VikBooking::getBackendLogo();
		$vbo_auth_global = JFactory::getUser()->authorise('core.vbo.global', 'com_vikbooking');
		$vbo_auth_rateplans = JFactory::getUser()->authorise('core.vbo.rateplans', 'com_vikbooking');
		$vbo_auth_rooms = JFactory::getUser()->authorise('core.vbo.rooms', 'com_vikbooking');
		$vbo_auth_pricing = JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking');
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		$vbo_auth_availability = JFactory::getUser()->authorise('core.vbo.availability', 'com_vikbooking');
		$vbo_auth_management = JFactory::getUser()->authorise('core.vbo.management', 'com_vikbooking');
		$vbo_auth_pms = JFactory::getUser()->authorise('core.vbo.pms', 'com_vikbooking');
		$reviews_dld = 0;
		?>
		<div class="vbo-menu-container<?php echo $view == 'dashboard' ? ' vbo-menu-container-closer' : ''; ?>">
			<div class="vbo-menu-left">
				<a href="index.php?option=com_vikbooking"><img src="<?php echo VBO_ADMIN_URI.(!empty($backlogo) ? 'resources/'.$backlogo : 'vikbooking.png'); ?>" alt="VikBooking Logo" /></a>
			</div>
			<div class="vbo-menu-right">
				<ul class="vbo-menu-ul">
					<?php
					if ($vbo_auth_global || $vbo_auth_management) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('cogs'); ?><a href="javascript: void(0);"><?php echo JText::translate('VBMENUFOUR'); ?></a></span>
						<ul class="vbo-submenu-ul">
							<?php if ($vbo_auth_global) : ?><li><span class="<?php echo ($highlight=="14" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=payments"><?php echo JText::translate('VBMENUTENEIGHT'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_global) : ?><li><span class="<?php echo ($highlight=="16" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=customf"><?php echo JText::translate('VBMENUTENTEN'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_management) : ?><li><span class="<?php echo ($highlight=="21" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=translations"><?php echo JText::translate('VBMENUTRANSLATIONS'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_global) : ?><li><span class="<?php echo ($highlight=="11" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=config"><?php echo JText::translate('VBMENUTWELVE'); ?></a></span></li><?php endif; ?>
						</ul>
					</li><?php
					}
					if ($vbo_auth_rateplans) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('briefcase'); ?><a href="javascript: void(0);"><?php echo JText::translate('VBMENURATEPLANS'); ?></a></span>
						<ul class="vbo-submenu-ul">
							<li><span class="<?php echo ($highlight=="2" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=iva"><?php echo JText::translate('VBMENUNINE'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="1" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=prices"><?php echo JText::translate('VBMENUFIVE'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="17" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=coupons"><?php echo JText::translate('VBMENUCOUPONS'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="packages" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=packages"><?php echo JText::translate('VBMENUPACKAGES'); ?></a></span></li>
						</ul>
					</li><?php
					}
					if ($vbo_auth_rooms || $vbo_auth_pricing) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('bed'); ?><a href="javascript: void(0);"><?php echo JText::translate('VBMENUTWO'); ?></a></span>
						<ul class="vbo-submenu-ul">
							<?php if ($vbo_auth_rooms) : ?><li><span class="<?php echo ($highlight=="4" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=categories"><?php echo JText::translate('VBMENUSIX'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_rooms) : ?><li><span class="<?php echo ($highlight=="5" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=carat"><?php echo JText::translate('VBMENUTENFOUR'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_pricing) : ?><li><span class="<?php echo ($highlight=="6" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=optionals"><?php echo JText::translate('VBMENUTENFIVE'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_rooms) : ?><li><span class="<?php echo ($highlight=="7" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=rooms"><?php echo JText::translate('VBMENUTEN'); ?></a></span></li><?php endif; ?>
						</ul>
					</li><?php
					}
					if ($vbo_auth_pricing) {
					?><li class="vbo-menu-parent-li">
						<span><i class="vboicn-calculator"></i><a href="javascript: void(0);"><?php echo JText::translate('VBMENUFARES'); ?></a></span>
						<ul class="vbo-submenu-ul">
							<li><span class="<?php echo ($highlight=="fares" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=tariffs"><?php echo JText::translate('VBMENUPRICESTABLE'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="13" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=seasons"><?php echo JText::translate('VBMENUTENSEVEN'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="restrictions" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=restrictions"><?php echo JText::translate('VBMENURESTRICTIONS'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="20" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=ratesoverv"><?php echo JText::translate('VBMENURATESOVERVIEW'); ?></a></span></li>
						</ul>
					</li><?php
					}
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('calendar-check'); ?><a href="javascript: void(0);"><?php echo JText::translate('VBMENUTHREE'); ?></a></span>
						<ul class="vbo-submenu-ul">
							<li><span class="<?php echo ($highlight=="18" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking"><?php echo JText::translate('VBMENUDASHBOARD'); ?></a></span></li>
							<?php if ($vbo_auth_availability || $vbo_auth_bookings) : ?><li><span class="<?php echo ($highlight=="19" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=calendar"><?php echo JText::translate('VBMENUQUICKRES'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_availability) : ?><li><span class="<?php echo ($highlight=="15" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=overv"><?php echo JText::translate('VBMENUTENNINE'); ?></a></span></li><?php endif; ?>
							<?php if ($vbo_auth_bookings) : ?><li><span class="<?php echo ($highlight=="8" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=orders"><?php echo JText::translate('VBMENUSEVEN'); ?></a></span></li><?php endif; ?>
							<?php echo $vbo_auth_availability || $vbo_auth_bookings ? $channel_manager_btn : ''; ?>
						</ul>
					</li><?php
					if ($vbo_auth_management) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('chart-pie'); ?><a href="javascript: void(0);"><?php echo JText::translate('VBMENUMANAGEMENT'); ?></a></span>
						<ul class="vbo-submenu-ul">
							<li><span class="<?php echo ($highlight=="22" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=customers"><?php echo JText::translate('VBMENUCUSTOMERS'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="invoices" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=invoices"><?php echo JText::translate('VBMENUINVOICES'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="stats" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=stats"><?php echo JText::translate('VBMENUSTATS'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="trackings" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=trackings"><?php echo JText::translate('VBMENUTRACKINGS'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="crons" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=crons"><?php echo JText::translate('VBMENUCRONS'); ?></a></span></li>
						</ul>
					</li><?php
					}
					if ($vbo_auth_pms) {
					?><li class="vbo-menu-parent-li">
						<span><?php VikBookingIcons::e('tasks'); ?><a href="javascript: void(0);"><?php echo JText::translate('VBMENUPMS'); ?></a></span>
						<ul class="vbo-submenu-ul">
							<li><span class="<?php echo ($highlight=="operators" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=operators"><?php echo JText::translate('VBMENUOPERATORS'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="tableaux" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=tableaux"><?php echo JText::translate('VBMENUTABLEAUX'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="pmsreports" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=pmsreports"><?php echo JText::translate('VBMENUPMSREPORTS'); ?></a></span></li>
							<li><span class="<?php echo ($highlight=="einvoicing" ? "vmenulinkactive" : "vmenulink"); ?>"><a href="index.php?option=com_vikbooking&amp;task=einvoicing"><?php echo JText::translate('VBMENUEINVOICING'); ?></a></span></li>
						</ul>
					</li><?php
					}
					?>
				</ul>
				<div class="vbo-menu-updates">
			<?php
			/**
			 * @wponly PRO Version
			 */
			VikBookingLoader::import('update.license');
			if (!VikBookingLicense::isPro()) {
				?>
					<button type="button" class="vbo-gotopro" title="<?php echo addslashes(JText::translate('VBOGOTOPROBTN')); ?>" onclick="document.location.href='admin.php?option=com_vikbooking&view=gotopro';">
						<?php VikBookingIcons::e('rocket'); ?>
						<span><?php echo JText::translate('VBOGOTOPROBTN'); ?></span>
					</button>
				<?php
			} else {
				?>
					<button type="button" class="vbo-alreadypro" title="<?php echo addslashes(JText::translate('VBOISPROBTN')); ?>" onclick="document.location.href='admin.php?option=com_vikbooking&view=gotopro';">
						<?php VikBookingIcons::e('trophy'); ?>
						<span><?php echo JText::translate('VBOISPROBTN'); ?></span>
					</button>
				<?php
			}
			
			if ($vbo_auth_management && ($highlight == '18' || $highlight == '11')) {
				// VCM Opportunities
				$opp = VikBooking::getVcmOpportunityInstance();
				if (!is_null($opp)) {
					// download opportunities if it's time to do it
					if ($opp->shouldRequestOpportunities()) {
						$opp->downloadOpportunities();
					}
					// count opportunities
					$opp_filters = array(
						'status' => 0,
						'action' => 0,
					);
					$new_opp_count = count($opp->loadOpportunities($opp_filters, null, null));
					if ($new_opp_count > 0) {
						/**
						 * @wponly  we use admin.php for the button link
						 */
						?>
					<button type="button" class="vbo-opportunities-btnbadge" data-opportunity-count="<?php echo $new_opp_count; ?>" title="<?php echo addslashes(JText::translate('VBOGOTOOPPORTUNITIES')); ?>" onclick="document.location.href='admin.php?option=com_vikchannelmanager&task=opportunities';">
						<?php VikBookingIcons::e('crown'); ?>
						<span><?php echo JText::translate('VBOGOTOOPPORTUNITIES'); ?></span>
					</button>
						<?php
					}
				}

				// Guest Reviews
				$reviews_dld = VikBooking::shouldDownloadReviews();
				/**
				 * @wponly  we use admin.php for the button link
				 */
				?>
					<button type="button" class="vbo-reviews-btnbadge" data-reviews-count="" title="<?php echo addslashes(JText::translate('VBOPANELREVIEWS')); ?>" onclick="<?php echo $reviews_dld >= 0 ? "window.open('admin.php?option=com_vikchannelmanager&task=reviews', '_blank'); jQuery(this).removeClass('vbo-reviews-btnbadge-alert').attr('data-reviews-count', '');" : "alert('" . addslashes(JText::translate('VBOGUESTREVSVCMREQ')) . "');"; ?>">
						<?php VikBookingIcons::e('star'); ?>
						<span><?php echo JText::translate('VBOPANELREVIEWS'); ?></span>
					</button>
				<?php
			}
			?>	
				</div>
			</div>
		</div>
		<div style="clear: both;"></div>
		<script type="text/javascript">
		var vbo_menu_type = <?php echo (int)$cookie->get('vboMenuType', '0', 'string'); ?>;
		var vbo_menu_on = ((vbo_menu_type % 2) == 0);
		//
		function vboDetectMenuChange(e) {
			e = e || window.event;
			if ((e.which == 77 || e.keyCode == 77) && e.altKey) {
				//ALT+M
				vbo_menu_type++;
				vbo_menu_on = ((vbo_menu_type % 2) == 0);
				console.log(vbo_menu_type, vbo_menu_on);
				//Set Cookie for next page refresh
				var nd = new Date();
				nd.setTime(nd.getTime() + (365*24*60*60*1000));
				document.cookie = "vboMenuType="+vbo_menu_type+"; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";
			}
		}
		document.onkeydown = vboDetectMenuChange;
		//
		jQuery(document).ready(function() {
			jQuery('.vbo-menu-parent-li').click(function() {
				if (jQuery(this).find('ul.vbo-submenu-ul').is(':visible')) {
					vbo_menu_on = false;
					return;
				}
				jQuery('ul.vbo-submenu-ul').hide();
				jQuery(this).find('ul.vbo-submenu-ul').show();
				vbo_menu_on = true;
			});
			jQuery('.vbo-menu-parent-li').hover(
				function() {
					if (vbo_menu_on === true) {
						jQuery(this).addClass('vbo-menu-parent-li-opened');
						jQuery(this).find('ul.vbo-submenu-ul').show();
					}
				},function() {
					if (vbo_menu_on === true) {
						jQuery(this).removeClass('vbo-menu-parent-li-opened');
						jQuery(this).find('ul.vbo-submenu-ul').hide();
					}
				}
			);
			var targetY = jQuery('.vbo-menu-right').offset().top + jQuery('.vbo-menu-right').outerHeight() + 150;
			jQuery(document).click(function(event) { 
				if (!jQuery(event.target).closest('.vbo-menu-right').length && parseInt(event.which) == 1 && event.pageY < targetY) {
					jQuery('ul.vbo-submenu-ul').hide();
					vbo_menu_on = true;
				}
			});
			if (jQuery('.vmenulinkactive').length) {
				jQuery('.vmenulinkactive').parent('li').parent('ul').parent('li').addClass('vbo-menu-parent-li-active');
				if ((vbo_menu_type % 2) != 0) {
					jQuery('.vmenulinkactive').parent('li').parent('ul').show();
				}
			}
			if (<?php echo $reviews_dld; ?> > 0) {
				// download new reviews
				jQuery.ajax({
					type: 'POST',
					url: 'index.php?option=com_vikchannelmanager&task=reviews_download&tmpl=component',
					data: {
						return: 1,
						everywhere: 1
					}
				}).done(function(resp) {
					try {
						if (resp.indexOf('e4j.ok') >= 0) {
							var tot_reviews = resp.replace('e4j.ok.', '');
							tot_reviews = parseInt(tot_reviews);
							if (!isNaN(tot_reviews) && tot_reviews > 0) {
								// update button badge
								jQuery('.vbo-reviews-btnbadge').attr('data-reviews-count', tot_reviews).addClass('vbo-reviews-btnbadge-alert');
							}
						}
					}
					catch(err) {
						console.error('Error in decoding response', err, resp);
					}
				}).fail(function(resp){
					console.error('Could not download new reviews from Channel Manager', resp);
				});
			}
		});
		</script>
		<?php	
	}
	
	public static function printFooter()
	{
		$tmpl = VikRequest::getVar('tmpl');
		if ($tmpl == 'component') return;
		/**
		 * @wponly "Powered by" is VikWP.com
		 */
		echo '<br clear="all" />' . '<div id="hmfooter">' . JText::sprintf('VBFOOTER', VIKBOOKING_SOFTWARE_VERSION) . ' <a href="https://vikwp.com/" target="_blank">VikWP - vikwp.com</a></div>';
	}

	//VikUpdater plugin methods - Start
	public static function pUpdateProgram($version)
	{
		?>
		<form name="adminForm" action="index.php" method="post" enctype="multipart/form-data" id="adminForm">
	
			<div class="span12">
				<fieldset class="form-horizontal">
					<legend><?php $version->shortTitle ?></legend>
					<div class="control"><strong><?php echo $version->title; ?></strong></div>

					<div class="control" style="margin-top: 10px;">
						<button type="button" class="btn btn-primary" onclick="downloadSoftware(this);">
							<?php echo JText::translate($version->compare == 1 ? 'VBDOWNLOADUPDATEBTN1' : 'VBDOWNLOADUPDATEBTN0'); ?>
						</button>
					</div>

					<div class="control vik-box-error" id="update-error" style="display: none;margin-top: 10px;"></div>

					<?php if ( isset($version->changelog) && count($version->changelog) ) { ?>

						<div class="control vik-update-changelog" style="margin-top: 10px;">

							<?php echo self::digChangelog($version->changelog); ?>

						</div>

					<?php } ?>
				</fieldset>
			</div>

			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="option" value="com_vikbooking"/>
		</form>

		<div id="vikupdater-loading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999999 !important; background-color: rgba(0,0,0,0.5);">
			<div id="vikupdater-loading-content" style="position: fixed; left: 33.3%; top: 30%; width: 33.3%; height: auto; z-index: 101; padding: 10px; border-radius: 5px; background-color: #fff; box-shadow: 5px 5px 5px 0 #000; overflow: auto; text-align: center;">
				<span id="vikupdater-loading-message" style="display: block; text-align: center;"></span>
				<span id="vikupdater-loading-dots" style="display: block; font-weight: bold; font-size: 25px; text-align: center; color: green;">.</span>
			</div>
		</div>
		
		<script type="text/javascript">
		var isRunning = false;
		var loadingInterval;

		function vikLoadingAnimation() {
			var dotslength = jQuery('#vikupdater-loading-dots').text().length + 1;
			if (dotslength > 10) {
				dotslength = 1;
			}
			var dotscont = '';
			for (var i = 1; i <= dotslength; i++) {
				dotscont += '.';
			}
			jQuery('#vikupdater-loading-dots').text(dotscont);
		}

		function openLoadingOverlay(message) {
			jQuery('#vikupdater-loading-message').html(message);
			jQuery('#vikupdater-loading').fadeIn();
			loadingInterval = setInterval(vikLoadingAnimation, 1000);
		}

		function closeLoadingOverlay() {
			jQuery('#vikupdater-loading').fadeOut();
			clearInterval(loadingInterval);
		}

		function downloadSoftware(btn) {

			if ( isRunning ) {
				return;
			}

			switchRunStatus(btn);
			setError(null);

			var jqxhr = jQuery.ajax({
				url: "index.php?option=com_vikbooking&task=updateprogramlaunch&tmpl=component",
				type: "POST",
				data: {}
			}).done(function(resp){

				try {
					var obj = JSON.parse(resp);
				} catch (e) {
					console.log(resp);
					return;
				}
				
				if ( obj === null ) {

					// connection failed. Something gone wrong while decoding JSON
					alert('<?php echo addslashes('Connection Error'); ?>');

				} else if ( obj.status ) {

					document.location.href = 'index.php?option=com_vikbooking';
					return;

				} else {

					console.log("### ERROR ###");
					console.log(obj);

					if ( obj.hasOwnProperty('error') ) {
						setError(obj.error);
					} else {
						setError('Your website does not have a valid support license.<br />Please visit <a href="https://extensionsforjoomla.com" target="_blank">extensionsforjoomla.com</a> to purchase a new license or to receive assistance.');
					}

				}

				switchRunStatus(btn);

			}).fail(function(resp){
				console.log('### FAILURE ###');
				console.log(resp);
				alert('<?php echo addslashes('Connection Error'); ?>');

				switchRunStatus(btn);
			}); 
		}

		function switchRunStatus(btn) {
			isRunning = !isRunning;

			jQuery(btn).prop('disabled', isRunning);

			if ( isRunning ) {
				// start loading
				openLoadingOverlay('The process may take a few minutes to complete.<br />Please wait without leaving the page or closing the browser.');
			} else {
				// stop loading
				closeLoadingOverlay();
			}
		}

		function setError(err) {

			if ( err !== null && err !== undefined && err.length ) {
				jQuery('#update-error').show();
			} else {
				jQuery('#update-error').hide();
			}

			jQuery('#update-error').html(err);

		}

	</script>
		<?php
	}

	/**
	 * Scan changelog structure.
	 *
	 * @param 	array 	$arr 	The list containing changelog elements.
	 * @param 	mixed 	$html 	The html built. 
	 * 							Specify false to echo the structure immediately.
	 *
	 * @return 	string|void 	The HTML structure or nothing.
	 */
	private static function digChangelog(array $arr, $html = '')
	{

		foreach( $arr as $elem ):

			if ( isset($elem->tag) ):

				// build attributes

				$attributes = "";
				if ( isset($elem->attributes) ) {

					foreach( $elem->attributes as $k => $v ) {
						$attributes .= " $k=\"$v\"";
					}

				}

				// build tag opening

				$str = "<{$elem->tag}$attributes>";

				if ( $html ) {
					$html .= $str;
				} else {
					echo $str;
				}

				// display contents

				if ( isset($elem->content) ) {

					if ( $html ) {
						$html .= $elem->content;
					} else {
						echo $elem->content;
					}

				}

				// recursive iteration for elem children

				if ( isset($elem->children) ) {
					self::digChangelog($elem->children, $html);
				}

				// build tag closure

				$str = "</{$elem->tag}>";

				if ( $html ) {
					$html .= $str;
				} else {
					echo $str;
				}

			endif;

		endforeach;

		return $html;
	}
	//VikUpdater plugin methods - End

}