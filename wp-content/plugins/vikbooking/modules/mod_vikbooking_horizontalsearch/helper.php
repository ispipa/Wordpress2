<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_horizontalsearch
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

final class VikBookingWidgetHorizontalSearch
{
	/**
	 * Returns a JS string to define the array-variables containing
	 * the months and the week days.
	 * 
	 * @param 	string 	$format 	either long or 3char.
	 * @param 	mixed 	$module_id 	the ID of the module to write unique variables
	 * 
	 * @return 	string
	 * 
	 * @since 	1.1.0
	 */
	public static function getMonWdayScript($format = 'long', $module_id = 0)
	{
		$module_id = (string)$module_id;

		return 'var vboMapWdays'.$module_id.' = ["'.self::applySubstr(JText::translate('VBJQCALSUN'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALMON'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALTUE'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALWED'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALTHU'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALFRI'), $format).'", "'.self::applySubstr(JText::translate('VBJQCALSAT'), $format).'"];
var vboMapMons'.$module_id.' = ["'.self::applySubstr(JText::translate('VBMONTHONE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTWO'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTHREE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHFOUR'), $format).'","'.self::applySubstr(JText::translate('VBMONTHFIVE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHSIX'), $format).'","'.self::applySubstr(JText::translate('VBMONTHSEVEN'), $format).'","'.self::applySubstr(JText::translate('VBMONTHEIGHT'), $format).'","'.self::applySubstr(JText::translate('VBMONTHNINE'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTEN'), $format).'","'.self::applySubstr(JText::translate('VBMONTHELEVEN'), $format).'","'.self::applySubstr(JText::translate('VBMONTHTWELVE'), $format).'"];';
	}

	/**
	 * Returns a string with the requested length.
	 * 
	 * @param 	string 	$text 			the text to apply the substr onto.
	 * @param 	string 	$format 		either long or 3char.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.1.0
	 */
	private static function applySubstr($text, $format)
	{
		$mb_supported = function_exists('mb_substr');

		if ($format == 'long') {
			return $text;
		}
		
		return $mb_supported ? mb_substr($text, 0, 3, 'UTF-8') : substr($text, 0, 3);
	}
}