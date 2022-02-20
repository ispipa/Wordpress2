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
 * Class handler for conditional rule "rate plans".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleRatePlans extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBMENURATEPLANS');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_RPL_DESCR');
		$this->ruleId = basename(__FILE__);
	}

	/**
	 * Displays the rule parameters.
	 * 
	 * @return 	void
	 */
	public function renderParams()
	{
		$this->vbo_app->loadSelect2();
		$rplans = $this->loadRatePlans();
		$current_rplans = $this->getParam('rplans', array());
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBOSPTYPESPRICE'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('rplans', true); ?>" id="<?php echo $this->inputID('rplans'); ?>" multiple="multiple">
				<?php
				foreach ($rplans as $rdata) {
					?>
					<option value="<?php echo $rdata['id']; ?>"<?php echo is_array($current_rplans) && in_array($rdata['id'], $current_rplans) ? ' selected="selected"' : ''; ?>><?php echo $rdata['name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#<?php echo $this->inputID('rplans'); ?>').select2();
			});
		</script>
		<?php
	}

	/**
	 * Tells whether the rule is compliant.
	 * 
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function isCompliant()
	{
		$rplans_booked = $this->getProperty('rooms', array());
		if (!is_array($rplans_booked) || !count($rplans_booked)) {
			return false;
		}

		$all_tariff_ids = array();
		foreach ($rplans_booked as $rplan_book) {
			if (!isset($rplan_book['idtar']) || in_array((int)$rplan_book['idtar'], $all_tariff_ids)) {
				continue;
			}
			array_push($all_tariff_ids, (int)$rplan_book['idtar']);
		}

		if (!count($all_tariff_ids)) {
			return false;
		}

		// get all rate plan IDs from tariffs
		$dbo = JFactory::getDbo();
		$q = "SELECT `idprice` FROM `#__vikbooking_dispcost` WHERE `id` IN (" . implode(', ', $all_tariff_ids) . ")";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$records = $dbo->loadAssocList();
		$all_price_ids = array();
		foreach ($records as $record) {
			array_push($all_price_ids, $record['idprice']);
		}

		$allowed_rplans = $this->getParam('rplans', array());

		$one_found = false;
		foreach ($all_price_ids as $idprice) {
			if (in_array($idprice, $allowed_rplans)) {
				$one_found = true;
				break;
			}
		}

		// return true if at least one rate plan booked is in the parameters
		return $one_found;
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function loadRatePlans()
	{
		$rplans = array();

		$dbo = JFactory::getDbo();
		$q = "SELECT `id`, `name` FROM `#__vikbooking_prices` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$rplans = $dbo->loadAssocList();
		}

		return $rplans;
	}

}
