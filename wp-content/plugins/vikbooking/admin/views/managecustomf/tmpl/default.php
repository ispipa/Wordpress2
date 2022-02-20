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

$field = $this->field;

$vbo_app = new VboApplication();

$choose = "";
if (count($field) && $field['type'] == "select") {
	$x = explode(";;__;;", $field['choose']);
	if (@count($x) > 0) {
		foreach ($x as $y) {
			if (!empty($y)) {
				$choose .= '<div class="vbo-customf-sel-added"><input type="text" name="choose[]" value="'.$y.'" size="40"/></div>'."\n";
			}
		}
	}
}
?>
<script type="text/javascript">
function setCustomfChoose (val) {
	if (val == "text") {
		document.getElementById('customfchoose').style.display = 'none';
		document.getElementById('vbflag').style.display = 'flex';
	}
	if (val == "textarea") {
		document.getElementById('customfchoose').style.display = 'none';
		document.getElementById('vbflag').style.display = 'none';
	}
	if (val == "checkbox") {
		document.getElementById('customfchoose').style.display = 'none';
		document.getElementById('vbflag').style.display = 'none';
	}
	if (val == "date") {
		document.getElementById('customfchoose').style.display = 'none';
		document.getElementById('vbflag').style.display = 'none';
	}
	if (val == "select") {
		document.getElementById('customfchoose').style.display = 'block';
		document.getElementById('vbflag').style.display = 'none';
	}
	if (val == "country") {
		document.getElementById('customfchoose').style.display = 'none';
		document.getElementById('vbflag').style.display = 'none';
	}
	if (val == "separator") {
		document.getElementById('customfchoose').style.display = 'none';
		document.getElementById('vbflag').style.display = 'none';
	}
	return true;
}
function addElement() {
	var ni = document.getElementById('customfchooseadd');
	var numi = document.getElementById('theValue');
	var num = (document.getElementById('theValue').value -1)+ 2;
	numi.value = num;
	var newdiv = document.createElement('div');
	var divIdName = 'my'+num+'Div';
	newdiv.setAttribute('id',divIdName);
	newdiv.innerHTML = '<div class=\'vbo-customf-sel-added\'><input type=\'text\' name=\'choose[]\' value=\'\' size=\'40\'/></div>';
	ni.appendChild(newdiv);
}
</script>
<input type="hidden" value="0" id="theValue" />

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCUSTOMFONE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="name" value="<?php echo count($field) ? $field['name'] : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCUSTOMFTWO'); ?></div>
							<div class="vbo-param-setting">
								<select id="stype" name="type" onchange="setCustomfChoose(this.value);">
									<!-- @wponly lite -->
									<option value="checkbox"<?php echo (count($field) && $field['type'] == "checkbox" ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBNEWCUSTOMFFIVE'); ?></option>
								</select>
								<div id="customfchoose" style="display: <?php echo (count($field) && $field['type'] == "select" ? "block" : "none"); ?>;">
									<?php
									if ((count($field) && $field['type'] != "select") || !count($field)) {
									?>
									<div class="vbo-customf-sel-added"><input type="text" name="choose[]" value="" size="40"/></div>
									<?php
									} else {
										echo $choose;
									}
									?>
									<div id="customfchooseadd" style="display: block;"></div>
									<span><b><a href="javascript: void(0);" onclick="javascript: addElement();"><?php echo JText::translate('VBNEWCUSTOMFNINE'); ?></a></b></span>
								</div>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCUSTOMFSIX'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->printYesNoButtons('required', JText::translate('VBYES'), JText::translate('VBNO'), (count($field) && intval($field['required']) == 1 ? 1 : 0), 1, 0); ?>
							</div>
						</div>
						<div class="vbo-param-container" id="vbflag"<?php echo (count($field) && $field['type'] != "text" ? " style=\"display: none;\"" : ""); ?>>
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCUSTOMFFLAG'); ?> <?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWCUSTOMFFLAG'), 'content' => JText::translate('VBNEWCUSTOMFFLAGHELP'))); ?></div>
							<div class="vbo-param-setting">
								<select name="flag">
									<option value=""></option>
									<option value="isemail"<?php echo (count($field) && intval($field['isemail']) == 1 ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBNEWCUSTOMFSEVEN'); ?></option>
									<option value="isnominative"<?php echo (count($field) && intval($field['isnominative']) == 1 ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBISNOMINATIVE'); ?></option>
									<option value="isphone"<?php echo (count($field) && intval($field['isphone']) == 1 ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBISPHONENUMBER'); ?></option>
									<option value="isaddress"<?php echo (count($field) && stripos($field['flag'], 'address') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBISADDRESS'); ?></option>
									<option value="iscity"<?php echo (count($field) && stripos($field['flag'], 'city') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBISCITY'); ?></option>
									<option value="iszip"<?php echo (count($field) && stripos($field['flag'], 'zip') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBISZIP'); ?></option>
									<option value="iscompany"<?php echo (count($field) && stripos($field['flag'], 'company') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBISCOMPANY'); ?></option>
									<option value="isvat"<?php echo (count($field) && stripos($field['flag'], 'vat') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBISVAT'); ?></option>
									<option value="isfisccode"<?php echo (count($field) && stripos($field['flag'], 'fisccode') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBCUSTOMERFISCCODE'); ?></option>
									<option value="ispec"<?php echo (count($field) && stripos($field['flag'], 'pec') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBCUSTOMERPEC'); ?></option>
									<option value="isrecipcode"<?php echo (count($field) && stripos($field['flag'], 'recipcode') !== false ? " selected=\"selected\"" : ""); ?>><?php echo JText::translate('VBCUSTOMERRECIPCODE'); ?></option>
								</select>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCUSTOMFEIGHT'); ?></div>
							<div class="vbo-param-setting">
								<input type="text" name="poplink" value="<?php echo count($field) ? $field['poplink'] : ''; ?>" size="40"/>
								<br/>
								<!-- @wponly we suggest to use a permalink -->
								<small>Eg. <i><?php echo get_site_url(); ?>/link-to-your-terms-page</i></small>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="option" value="com_vikbooking">
<?php
if (count($field)) :
?>
	<input type="hidden" name="where" value="<?php echo $field['id']; ?>">
<?php
endif;
?>
	<?php echo JHtml::fetch('form.token'); ?>
</form>
