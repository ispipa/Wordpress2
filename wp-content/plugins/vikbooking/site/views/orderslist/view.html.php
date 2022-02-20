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


jimport('joomla.application.component.view');

class VikbookingViewOrderslist extends JViewVikBooking {
	function display($tpl = null) {
		VikBooking::prepareViewContent();
		$islogged = VikBooking::userIsLogged();
		$cpin = VikBooking::getCPinIstance();
		$pconfirmnumber = VikRequest::getString('confirmnumber', '', 'request');
		$pitemid = VikRequest::getString('Itemid', '', 'request');
		$dbo = JFactory::getDbo();
		if (!empty($pconfirmnumber)) {
			$q = "SELECT `id`,`ts`,`sid`,`idorderota` FROM `#__vikbooking_orders` WHERE `confirmnumber`=".$dbo->quote($pconfirmnumber).";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$odata = $dbo->loadAssocList();
				$mainframe = JFactory::getApplication();
				$mainframe->redirect(JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid='.(!empty($odata[0]['sid']) ? $odata[0]['sid'] : $odata[0]['idorderota']).'&ts='.$odata[0]['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false));
				exit;
			} else {
				if ($cpin->pinExists($pconfirmnumber)) {
					$cpin->setNewPin($pconfirmnumber);
				} else {
					VikError::raiseWarning('', JText::translate('VBINVALIDCONFIRMNUMBER'));
				}
			}
		}
		$customer_details = $cpin->loadCustomerDetails();
		$userorders = '';
		$navig = '';
		if ($islogged || count($customer_details) > 0) {
			$currentUser = JFactory::getUser();
			$lim = 10;
			$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
			$q = "SELECT SQL_CALC_FOUND_ROWS `o`.*,`co`.`idcustomer` FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `co`.`idorder`=`o`.`id` WHERE ".($islogged ? "`o`.`ujid`='".$currentUser->id."'".(count($customer_details) > 0 ? " OR " : "") : "").(count($customer_details) > 0 ? "`co`.`idcustomer`=".(int)$customer_details['id'] : "")." ORDER BY `o`.`checkin` DESC";
			$dbo->setQuery($q, $lim0, $lim);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$userorders = $dbo->loadAssocList();
				$dbo->setQuery('SELECT FOUND_ROWS();');
				jimport('joomla.html.pagination');
				$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
				$navig = $pageNav->getPagesLinks();
			}
		}
		$this->userorders = &$userorders;
		$this->customer_details = &$customer_details;
		$this->navig = &$navig;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'orderslist';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		//
		parent::display($tpl);
	}
}
