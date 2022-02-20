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

?>

<div class="vbo-free-nonavail-wrap">
	<div class="vbo-free-nonavail-inner">
		<div class="vbo-free-nonavail-logo">
			<img src="<?php echo VBO_SITE_URI; ?>resources/vikwp_free_logo.png" />
		</div>
		<div class="vbo-free-nonavail-expl">
			<h3><?php echo JText::translate('VBMENUINVOICES'); ?></h3>
			<p class="vbo-free-nonavail-descr"><?php echo JText::translate('VBOFREEINVOICESDESCR'); ?></p>
			<p class="vbo-free-nonavail-footer-descr">
				<button type="button" class="btn vbo-free-nonavail-gopro" onclick="document.location.href='admin.php?option=com_vikbooking&amp;view=gotopro';">
					<?php VikBookingIcons::e('rocket'); ?> <span><?php echo JText::translate('VBOGOTOPROBTN'); ?></span>
				</button>
			</p>
		</div>
	</div>
</div>