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
 * Class handler for conditional rule "rooms".
 * 
 * @since 	1.4.0
 */
class VikBookingConditionalRuleRooms extends VikBookingConditionalRule
{
	/**
	 * Class constructor will define the rule name, description and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->ruleName = JText::translate('VBPVIEWORDERSTHREE');
		$this->ruleDescr = JText::translate('VBO_CONDTEXT_RULE_ROOMS_DESCR');
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
		$rooms = $this->loadRooms();
		$current_rooms = $this->getParam('rooms', array());
		?>
		<div class="vbo-param-container">
			<div class="vbo-param-label"><?php echo JText::translate('VBOROOMSASSIGNED'); ?></div>
			<div class="vbo-param-setting">
				<select name="<?php echo $this->inputName('rooms', true); ?>" id="<?php echo $this->inputID('rooms'); ?>" multiple="multiple">
				<?php
				foreach ($rooms as $rdata) {
					?>
					<option value="<?php echo $rdata['id']; ?>"<?php echo is_array($current_rooms) && in_array($rdata['id'], $current_rooms) ? ' selected="selected"' : ''; ?>><?php echo $rdata['name']; ?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#<?php echo $this->inputID('rooms'); ?>').select2();
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
		$rooms_booked = $this->getProperty('rooms', array());
		if (!is_array($rooms_booked) || !count($rooms_booked)) {
			return false;
		}

		$allowed_rooms = $this->getParam('rooms', array());

		$one_found = false;
		foreach ($rooms_booked as $rb) {
			if (!isset($rb['idroom'])) {
				continue;
			}
			if (in_array($rb['idroom'], $allowed_rooms)) {
				$one_found = true;
				break;
			}
		}

		// return true if at least one room booked is in the parameters
		return $one_found;
	}

	/**
	 * Internal function for this rule only.
	 * 
	 * @return 	array
	 */
	protected function loadRooms()
	{
		$rooms = array();

		$dbo = JFactory::getDbo();
		$q = "SELECT `id`, `name` FROM `#__vikbooking_rooms` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$rooms = $dbo->loadAssocList();
		}

		return $rooms;
	}

}
