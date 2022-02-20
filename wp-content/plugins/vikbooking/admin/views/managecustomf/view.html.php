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

// import Joomla view library
jimport('joomla.application.component.view');

class VikBookingViewManagecustomf extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		$cid = VikRequest::getVar('cid', array(0));
		if (!empty($cid[0])) {
			$fid = $cid[0];
		}

		$dbo = JFactory::getDBO();
		$field = array();
		// @wponly lite
		$app = JFactory::getApplication();
		if (!empty($cid[0])) {
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `id`=".$dbo->quote($fid)." AND `type`='checkbox';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$field = $dbo->loadAssoc();
			} else {
				$app->redirect('admin.php?option=com_vikbooking');
				exit;
			}
		} else {
			$app->redirect('admin.php?option=com_vikbooking');
			exit;
		}
		
		$this->field = &$field;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		$cid = VikRequest::getVar('cid', array(0));
		
		if (!empty($cid[0])) {
			//edit
			JToolBarHelper::title(JText::translate('VBMAINCUSTOMFTITLE'), 'vikbooking');
			if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
				JToolBarHelper::save( 'updatecustomf', JText::translate('VBSAVE'));
				JToolBarHelper::spacer();
			}
			JToolBarHelper::cancel( 'cancelcustomf', JText::translate('VBANNULLA'));
			JToolBarHelper::spacer();
		} else {
			// @wponly lite
			$app = JFactory::getApplication();
			$app->redirect('admin.php?option=com_vikbooking');
			exit;
		}
	}

}
