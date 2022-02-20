<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	system
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class used to provide support for the <head> of the page.
 *
 * @since 1.0
 */
class VikBookingAssets
{
	/**
	 * A list containing all the methods already used.
	 *
	 * @var array
	 */
	protected static $loaded = array();

	/**
	 * Loads all the assets required for the plugin.
	 *
	 * @return 	void
	 */
	public static function load()
	{
		// loads only once
		if (static::isLoaded(__METHOD__))
		{
			return;
		}

		$document = JFactory::getDocument();

		$internalFilesOptions = array('version' => VIKBOOKING_SOFTWARE_VERSION);

		// include localised strings for script files
		JText::script('CONNECTION_LOST');

		// system.js must be loaded on both front-end and back-end for tmpl=component support
		$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/system.js', $internalFilesOptions, array('id' => 'vbo-sys-script'));

		if (JFactory::getApplication()->isAdmin())
		{
			/* Load assets for CSS and JS */
			VikBooking::loadFontAwesome(true);
			
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'vikbooking.css', $internalFilesOptions, array('id' => 'vbo-style'));
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'fonts/vboicomoon.css', $internalFilesOptions, array('id' => 'vbo-icomoon-style'));
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'vikbooking_backendcustom.css', $internalFilesOptions, array('id' => 'vbo-custom-style'));

			VikBooking::getVboApplication()->normalizeBackendStyles();

			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/system.css', $internalFilesOptions, array('id' => 'vbo-sys-style'));
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/bootstrap.lite.css', $internalFilesOptions, array('id' => 'bootstrap-lite-style'));
			$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/bootstrap.min.js', $internalFilesOptions, array('id' => 'bootstrap-script'));

			$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/admin.js', $internalFilesOptions, array('id' => 'vbo-admin-script'));

			/**
			 * Load necessary assets for WordPress >= 5.3
			 * 
			 * @since 	1.2.10
			 */
			JLoader::import('adapter.application.version');
			$wpv = new JVersion;
			if (version_compare($wpv->getShortVersion(), '5.3', '>=')) {
				$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/bc/wp5.3.css', $internalFilesOptions, array('id' => 'vbo-wp-bc-style'));
			}
			//
		}
		else
		{
			if (VikBooking::loadBootstrap())
			{
				$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'bootstrap.min.css', $internalFilesOptions, array('id' => 'vbo-bs-style'));
				$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'bootstrap-theme.min.css', $internalFilesOptions, array('id' => 'vbo-bstheme-style'));
			}
			
			VikBooking::loadFontAwesome();
			$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'vikbooking_styles.css', $internalFilesOptions, array('id' => 'vbo-style'));
			$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'vikbooking_custom.css', $internalFilesOptions, array('id' => 'vbo-custom-style'));
		}
	}

	/**
	 * Checks if the method has been already loaded.
	 * This function assumes that after this check we are going
	 * to use the specified method.
	 *
	 * A method is considered loaded only if the arguments used are the same.
	 *
	 * @param 	string 	 $method 	The method to check for.
	 * @param 	array 	 $args 		The list of arguments.
	 * 
	 * @return 	boolean  True if already used, otherwise false.
	 */
	protected static function isLoaded($method, array $args = array())
	{
		// generate a unique signature containing the method name
		// and the list of arguments to use
		$sign = serialize(array($method, $args));

		// check if the method has been already loaded
		if (isset(static::$loaded[$sign]))
		{
			// already loaded
			return true;
		}

		// mark the method as loaded
		static::$loaded[$sign] = 1;

		// not loaded
		return false;
	}
}
