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
 * Helper class for the administrator widgets.
 * 
 * @since 	1.4.0
 */
class VikBookingHelperAdminWidgets
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingHelperAdminWidgets
	 */
	protected static $instance = null;

	/**
	 * An array to store some cached/static values.
	 *
	 * @var array
	 */
	protected static $helper = null;

	/**
	 * The database handler instance.
	 *
	 * @var object
	 */
	protected $dbo;

	/**
	 * The list of widget instances loaded.
	 *
	 * @var array
	 */
	protected $widgets;

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		static::$helper = array();
		$this->dbo = JFactory::getDbo();
		$this->widgets = array();
		$this->load();
	}

	/**
	 * Returns the global object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	A new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Loads a list of all available admin widgets.
	 *
	 * @return 	self
	 */
	protected function load()
	{
		// require main/parent admin-widget class
		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'admin_widget.php');

		$widgets_base  = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR;
		$widgets_files = glob($widgets_base . '*.php');

		/**
		 * Trigger event to let other plugins register additional widgets.
		 *
		 * @return 	array 	A list of supported widgets.
		 */
		$list = JFactory::getApplication()->triggerEvent('onLoadAdminWidgets');
		foreach ($list as $chunk) {
			// merge default widget files with the returned ones
			$widgets_files = array_merge($widgets_files, (array)$chunk);
		}

		foreach ($widgets_files as $wf) {
			try {
				// require widget class file
				if (is_file($wf)) {
					require_once($wf);
				}

				// instantiate widget object
				$classname  = 'VikBookingAdminWidget' . str_replace(' ', '', ucwords(str_replace('_', ' ', basename($wf, '.php'))));
				if (class_exists($classname)) {
					$widget = new $classname();
					// push widget object
					array_push($this->widgets, $widget);
				}
			} catch (Exception $e) {
				// do nothing
			}
		}

		return $this;
	}

	/**
	 * Gets the default map of admin widgets.
	 *
	 * @return 	object 	the associative map of sections,
	 * 					containers and widgets.
	 */
	protected function getDefaultWidgetsMap()
	{
		$sections = array();

		// build default sections
		
		// top section
		$section = new stdClass;
		$section->name = 'Top';
		$section->containers = array();
		// start container
		$container = new stdClass;
		$container->size = 'large';
		$container->widgets = array(
			'sticky_notes.php',
			'arriving_today.php',
			'departing_today.php',
		);
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'small';
		$container->widgets = array(
			'latest_from_guests.php',
			'forecast.php',
		);
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// second section
		$section = new stdClass;
		$section->name = 'Top 2';
		$section->containers = array();
		// start container
		$container = new stdClass;
		$container->size = 'full';
		$container->widgets = array(
			'weekly_bookings.php',
		);
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// third section
		$section = new stdClass;
		$section->name = 'Middle';
		$section->containers = array();
		// start container
		$container = new stdClass;
		$container->size = 'full';
		$container->widgets = array(
			'today_rooms_occupancy.php',
		);
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// fourth section
		$section = new stdClass;
		$section->name = 'Bottom';
		$section->containers = array();
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = array(
			'orphan_dates.php',
			'rooms_locked.php',
		);
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'small';
		$container->widgets = array(
			'sticky_notes.php',
		);
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'small';
		$container->widgets = array(
			'visitors_counter.php',
		);
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// fifth section
		$section = new stdClass;
		$section->name = 'Bottom 2';
		$section->containers = array();
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = array(
			'last_reservations.php',
		);
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = array(
			'next_bookings.php',
		);
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// compose the final map object
		$map = new stdClass;
		$map->sections = $sections;
		
		return $map;
	}

	/**
	 * Gets the list of admin widgets instantiated.
	 *
	 * @return 	array 	list of admin widget objects.
	 */
	public function getWidgets()
	{
		return $this->widgets;
	}

	/**
	 * Gets a single admin widget instantiated.
	 * 
	 * @param 	string 	$id 	the widget identifier.
	 *
	 * @return 	mixed 	the admin widget object, false otherwise.
	 */
	public function getWidget($id)
	{
		foreach ($this->widgets as $widget) {
			if ($widget->getIdentifier() != $id) {
				continue;
			}
			return $widget;
		}

		return false;
	}

	/**
	 * Gets a list of sorted widget names, ids and descriptions.
	 *
	 * @return 	array 	associative and sorted widgets list.
	 */
	public function getWidgetNames()
	{
		$names = array();
		$pool  = array();

		foreach ($this->widgets as $widget) {
			$id 	= $widget->getIdentifier();
			$name 	= $widget->getName();
			$descr 	= $widget->getDescription();
			$wtdata = new stdClass;
			$wtdata->id 	= $id;
			$wtdata->name 	= $name;
			$wtdata->descr 	= $descr;
			$names[$name] 	= $wtdata;
		}

		// apply sorting by name
		ksort($names);

		// push sorted widgets to pool
		foreach ($names as $wtdata) {
			array_push($pool, $wtdata);
		}

		return $pool;
	}

	/**
	 * Gets the current or default map of admin widgets.
	 * If no map currently sets, stores the default map.
	 *
	 * @return 	array 	the associative map of sections,
	 * 					containers and widgets.
	 */
	public function getWidgetsMap()
	{
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='admin_widgets_map';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$map = json_decode($this->dbo->loadResult());
			return is_object($map) && isset($map->sections) && count($map->sections) ? $map : $this->getDefaultWidgetsMap();
		}

		$default_map = $this->getDefaultWidgetsMap();
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('admin_widgets_map', " . $this->dbo->quote(json_encode($default_map)) . ");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return $default_map;
	}

	/**
	 * Updates the map of admin widgets.
	 * 
	 * @param 	array 	$sections 	the list of sections for the map.
	 *
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function updateWidgetsMap($sections)
	{
		if (!is_array($sections) || !count($sections)) {
			return false;
		}

		// prepare new map object
		$map = new stdClass;
		$map->sections = $sections;

		$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $this->dbo->quote(json_encode($map)) . " WHERE `param`='admin_widgets_map';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return true;
	}

	/**
	 * Restores the default admin widgets map.
	 * First it resets the settings of each widget.
	 *
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function restoreDefaultWidgetsMap()
	{
		foreach ($this->widgets as $widget) {
			$widget->resetSettings();
		}

		$default_map = $this->getDefaultWidgetsMap();

		return $this->updateWidgetsMap($default_map->sections);
	}

	/**
	 * Forces the rendering of a specific widget identifier.
	 * 
	 * @param 	string 	$id 	the widget identifier.
	 * @param 	mixed 	$data 	anything to pass to the widget.
	 *
	 * @return 	mixed 	void on success, false otherwise.
	 */
	public function renderWidget($id, $data = null)
	{
		foreach ($this->widgets as $widget) {
			if ($widget->getIdentifier() != $id) {
				continue;
			}
			return $widget->render($data);
		}

		return false;
	}

	/**
	 * Maps the size identifier to a CSS class.
	 * 
	 * @param 	string 	$size 	the container size identifier.
	 *
	 * @return 	string 	the full CSS class for the container.
	 */
	public function getContainerCssClass($size)
	{
		$css_size_map = array(
			'small' => 'vbo-admin-widgets-container-small',
			'medium' => 'vbo-admin-widgets-container-medium',
			'large' => 'vbo-admin-widgets-container-large',
			'full' => 'vbo-admin-widgets-container-fullwidth',
		);

		return isset($css_size_map[$size]) ? $css_size_map[$size] : $css_size_map['full'];
	}

	/**
	 * Returns an associative array with the class names for the containers.
	 *
	 * @return 	array 	a text representation list of all sizes.
	 */
	public function getContainerClassNames()
	{
		return array(
			'full' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTFULL'),
				'css' => $this->getContainerCssClass('full'),
			),
			'large' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTLARGE'),
				'css' => $this->getContainerCssClass('large'),
			),
			'medium' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTMEDIUM'),
				'css' => $this->getContainerCssClass('medium'),
			),
			'small' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTSMALL'),
				'css' => $this->getContainerCssClass('small'),
			),
		);
	}

	/**
	 * Maps the size identifier to the corresponding name.
	 * 
	 * @param 	string 	$size 	the container size identifier.
	 *
	 * @return 	string 	the size name for the container.
	 */
	public function getContainerName($size)
	{
		$names = $this->getContainerClassNames();

		return isset($names[$size]) ? $names[$size]['name'] : $names['full']['name'];
	}

	/**
	 * Many widgets may need to know some values about the rooms.
	 * This method uses the static instance of the class to cache data.
	 * 
	 * @return 	void
	 */
	protected function loadRoomsData()
	{
		if (isset(static::$helper['all_rooms_ids'])) {
			// do not execute the same queries again
			return;
		}

		$all_rooms_ids = array();
		$all_rooms_units = array();
		$all_rooms_features = array();
		$unpublished_rooms = array();
		$tot_rooms_units = 0;

		$q = "SELECT `id`,`name`,`units`,`params`,`avail` FROM `#__vikbooking_rooms`;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$all_rooms = $this->dbo->loadAssocList();
			foreach ($all_rooms as $k => $r) {
				if ($r['avail'] < 1) {
					$unpublished_rooms[] = $r['id'];
				} else {
					$tot_rooms_units += $r['units'];
				}
				$all_rooms_ids[$r['id']] = $r['name'];
				$all_rooms_units[$r['id']] = $r['units'];
				$rparams = json_decode($r['params'], true);
				$all_rooms_features[$r['id']] = is_array($rparams) && array_key_exists('features', $rparams) && is_array($rparams['features']) ? $rparams['features'] : array();
			}
		}

		// update static values
		static::$helper['all_rooms_ids'] = $all_rooms_ids;
		static::$helper['all_rooms_units'] = $all_rooms_units;
		static::$helper['all_rooms_features'] = $all_rooms_features;
		static::$helper['unpublished_rooms'] = $unpublished_rooms;
		static::$helper['tot_rooms_units'] = $tot_rooms_units;
	}

	/**
	 * Many widgets could use this method to access cached information.
	 * 
	 * @param 	string 	$key 	the data key identifier.
	 * 
	 * @return 	mixed 	array/int on success, false otherwise.
	 */
	public function getRoomsData($key)
	{
		if (empty($key)) {
			return false;
		}

		if (!count(static::$helper)) {
			$this->loadRoomsData();
		}

		if (isset(static::$helper[$key])) {
			return static::$helper[$key];
		}

		return false;
	}

	/**
	 * Helper method to load all busy real records for all rooms until the end of today.
	 * This is useful to avoid double queries in the various widgets.
	 * 
	 * @return 	array
	 */
	public function loadBusyRecordsUnclosed()
	{
		if (!isset(static::$helper['all_rooms_ids'])) {
			$this->loadRoomsData();
		}

		if (isset(static::$helper['busy_records_unclosed'])) {
			return static::$helper['busy_records_unclosed'];
		}

		// cache value and return it
		$today_end_ts = mktime(23, 59, 59, date("n"), date("j"), date("Y"));
		static::$helper['busy_records_unclosed'] = VikBooking::loadBusyRecordsUnclosed(array_keys(static::$helper['all_rooms_ids']), $today_end_ts);

		return static::$helper['busy_records_unclosed'];
	}

	/**
	 * The first time the widget's customizer is open, the welcome is displayed.
	 * Congig value >= 1 means hide the welcome text, 0 or lower means show it.
	 * 
	 * @return 	bool
	 */
	public function showWelcome()
	{
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='admin_widgets_welcome';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			return ((int)$this->dbo->loadResult() < 1);
		}

		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('admin_widgets_welcome', '0');";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return true;
	}

	/**
	 * Updates the status of the welcome message for the widget's customizer.
	 * Congig value >= 1 means hide the welcome text, 0 or lower means show it.
	 * 
	 * @param 	int 	$val 	the new value to set in the configuration.
	 * 
	 * @return 	void
	 */
	public function updateWelcome($val)
	{
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='admin_widgets_welcome';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $this->dbo->quote((int)$val) . " WHERE `param`='admin_widgets_welcome';";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			return;
		}

		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('admin_widgets_welcome', " . $this->dbo->quote((int)$val) . ");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		return;
	}
}
