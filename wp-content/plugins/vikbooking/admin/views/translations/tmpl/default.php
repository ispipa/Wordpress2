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

$vbo_tn = $this->vbo_tn;

$editor = JEditor::getInstance(JFactory::getApplication()->get('editor'));
$langs = $vbo_tn->getLanguagesList();
$xml_tables = $vbo_tn->getTranslationTables();
$active_table = '';
$active_table_key = '';
if (!(count($langs) > 1)) {
	//Error: only one language is published. Translations are useless
	?>
	<p class="err"><?php echo JText::translate('VBTRANSLATIONERRONELANG'); ?></p>
	<form name="adminForm" id="adminForm" action="index.php" method="post">
		<input type="hidden" name="task" value="">
		<input type="hidden" name="option" value="com_vikbooking">
	</form>
	<?php
} elseif (!(count($xml_tables) > 0) || strlen($vbo_tn->getError())) {
	//Error: XML file not readable or errors occurred
	?>
	<p class="err"><?php echo $vbo_tn->getError(); ?></p>
	<form name="adminForm" id="adminForm" action="index.php" method="post">
		<input type="hidden" name="task" value="">
		<input type="hidden" name="option" value="com_vikbooking">
	</form>
	<?php
} else {
	$cur_langtab = VikRequest::getString('vbo_lang', '', 'request');
	$table = VikRequest::getString('vbo_table', '', 'request');
	if (!empty($table)) {
		$table = $vbo_tn->replacePrefix($table);
	}
?>
<script type="text/Javascript">
var vbo_tn_changes = false;
jQuery(document).ready(function(){
	jQuery('#adminForm input[type=text], #adminForm textarea').change(function() {
		vbo_tn_changes = true;
	});
});
function vboCheckChanges() {
	if (!vbo_tn_changes) {
		return true;
	}
	return confirm("<?php echo addslashes(JText::translate('VBTANSLATIONSCHANGESCONF')); ?>");
}
</script>
<form action="index.php?option=com_vikbooking&amp;task=translations" method="post" onsubmit="return vboCheckChanges();">
	<div style="width: 100%; display: inline-block;" class="btn-toolbar vbo-btn-toolbar" id="filter-bar">
		<div class="btn-group pull-right">
			<button class="btn" type="submit"><?php echo JText::translate('VBOGETTRANSLATIONS'); ?></button>
		</div>
		<div class="btn-group pull-right">
			<select name="vbo_table">
				<option value="">-----------</option>
			<?php
			foreach ($xml_tables as $key => $value) {
				$active_table = $vbo_tn->replacePrefix($key) == $table ? $value : $active_table;
				$active_table_key = $vbo_tn->replacePrefix($key) == $table ? $key : $active_table_key;
				?>
				<option value="<?php echo $key; ?>"<?php echo $vbo_tn->replacePrefix($key) == $table ? ' selected="selected"' : ''; ?>><?php echo $value; ?></option>
				<?php
			}
			?>
			</select>
		</div>
	</div>
	<input type="hidden" name="vbo_lang" class="vbo_lang" value="<?php echo $vbo_tn->default_lang; ?>">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="translations" />
</form>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-translation-langtabs">
<?php
foreach ($langs as $ltag => $lang) {
	$is_def = ($ltag == $vbo_tn->default_lang);
	$lcountry = substr($ltag, 0, 2);
	$flag = ''; // @wponly no flags for the language
		?><div class="vbo-translation-tab<?php echo $is_def ? ' vbo-translation-tab-default' : ''; ?>" data-vbolang="<?php echo $ltag; ?>">
		<?php
		if (!empty($flag)) {
			?>
			<span class="vbo-translation-flag"><?php echo $flag; ?></span>
			<?php
		}
		?>
			<span class="vbo-translation-langname"><?php echo $lang['name']; ?></span>
		</div><?php
}
?>		<!-- @wponly no tab for the .INI Status -->
	</div>
	<div class="vbo-translation-tabscontents">
<?php
$table_cols = !empty($active_table_key) ? $vbo_tn->getTableColumns($active_table_key) : array();
$table_def_dbvals = !empty($active_table_key) ? $vbo_tn->getTableDefaultDbValues($active_table_key, array_keys($table_cols)) : array();
if (!empty($active_table_key)) {
	echo '<input type="hidden" name="vbo_table" value="'.$active_table_key.'"/>'."\n";
}
foreach ($langs as $ltag => $lang) {
	$is_def = ($ltag == $vbo_tn->default_lang);
	?>
		<div class="vbo-translation-langcontent" style="display: <?php echo $is_def ? 'block' : 'none'; ?>;" id="vbo_langcontent_<?php echo $ltag; ?>">
	<?php
	if (empty($active_table_key)) {
		?>
			<p class="warn"><?php echo JText::translate('VBTRANSLATIONSELTABLEMESS'); ?></p>
		<?php
	} elseif (strlen($vbo_tn->getError()) > 0) {
		?>
			<p class="err"><?php echo $vbo_tn->getError(); ?></p>
		<?php
	} else {
		?>
			<fieldset class="adminform">
				<legend class="adminlegend"><?php echo $active_table; ?> - <?php echo $lang['name'].($is_def ? ' - '.JText::translate('VBTRANSLATIONDEFLANG') : ''); ?></legend>
				<div class="vbo-translations-tab-container">
	<?php
	if ($is_def) {
		//Values of Default Language to be translated
		foreach ($table_def_dbvals as $reference_id => $values) {
			?>
					<div class="vbo-translations-default-element">
						<div class="vbo-translations-element-title" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
							<div class="vbo-translate-element-cell"><?php echo $vbo_tn->getRecordReferenceName($table_cols, $values); ?></div>
						</div>
						<div class="vbo-translations-element-contents">
			<?php
			foreach ($values as $field => $def_value) {
				$title = $table_cols[$field]['jlang'];
				$type = $table_cols[$field]['type'];
				if ($type == 'html') {
					$def_value = strip_tags($def_value);
				}
				?>
							<div class="vbo-translations-element-row" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
								<div class="vbo-translations-element-lbl"><?php echo $title; ?></div>
								<div class="vbo-translations-element-val"><?php echo $type != 'json' ? $def_value : ''; ?></div>
							</div>
				<?php
				if ($type == 'json') {
					$tn_keys = $table_cols[$field]['keys'];
					$keys = !empty($tn_keys) ? explode(',', $tn_keys) : array();
					$json_def_values = json_decode($def_value, true);
					if (count($json_def_values) > 0) {
						foreach ($json_def_values as $jkey => $jval) {
							if ((!in_array($jkey, $keys) && count($keys) > 0) || empty($jval)) {
								continue;
							}
							?>
							<div class="vbo-translations-element-row vbo-translations-element-row-nested" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
								<div class="vbo-translations-element-lbl"><?php echo !is_numeric($jkey) ? ucwords($jkey) : '&nbsp;'; ?></div>
								<div class="vbo-translations-element-val"><?php echo $jval; ?></div>
							</div>
							<?php
						}
					}
				}
				?>
				<?php
			}
			?>
						</div>
					</div>
			<?php
		}
	} else {
		//Translation Fields for this language
		$lang_record_tn = $vbo_tn->getTranslatedTable($active_table_key, $ltag);
		foreach ($table_def_dbvals as $reference_id => $values) {
			?>
					<div class="vbo-translations-language-element">
						<div class="vbo-translations-element-title" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
							<div class="vbo-translate-element-cell"><?php echo $vbo_tn->getRecordReferenceName($table_cols, $values); ?></div>
						</div>
						<div class="vbo-translations-element-contents">
			<?php
			foreach ($values as $field => $def_value) {
				$title = $table_cols[$field]['jlang'];
				$type = $table_cols[$field]['type'];
				if ($type == 'skip') {
					continue;
				}
				$tn_value = '';
				$tn_class = ' vbo-missing-translation';
				if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content']) && strlen($lang_record_tn[$reference_id]['content'][$field])) {
					if (in_array($type, array('text', 'textarea', 'html'))) {
						$tn_class = ' vbo-field-translated';
					} else {
						$tn_class = '';
					}
				}
				?>
							<div class="vbo-translations-element-row<?php echo $tn_class; ?>" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
								<div class="vbo-translations-element-lbl"><?php echo $title; ?></div>
								<div class="vbo-translations-element-val">
						<?php
						if ($type == 'text') {
							if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
								$tn_value = $lang_record_tn[$reference_id]['content'][$field];
							}
							?>
									<input type="text" name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>]" value="<?php echo htmlentities($tn_value); ?>" size="40" placeholder="<?php echo htmlentities($def_value); ?>"/>
							<?php
						} elseif ($type == 'textarea') {
							if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
								$tn_value = $lang_record_tn[$reference_id]['content'][$field];
							}
							?>
									<textarea name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>]" rows="5" cols="40"><?php echo $tn_value; ?></textarea>
							<?php
						} elseif ($type == 'html') {
							if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
								$tn_value = $lang_record_tn[$reference_id]['content'][$field];
							}
							if (interface_exists('Throwable')) {
								/**
								 * With PHP >= 7 supporting throwable exceptions for Fatal Errors
								 * we try to avoid issues with third party plugins that make use
								 * of the WP native function get_current_screen().
								 * 
								 * @wponly
								 */
								try {
									echo $editor->display( "tn[".$ltag."][".$reference_id."][".$field."]", $tn_value, '100%', 350, 70, 20, true, "tn_".$ltag."_".$reference_id."_".$field );
								} catch (Throwable $t) {
									echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
								}
							} else {
								// we cannot catch Fatal Errors in PHP 5.x
								echo $editor->display( "tn[".$ltag."][".$reference_id."][".$field."]", $tn_value, '100%', 350, 70, 20, true, "tn_".$ltag."_".$reference_id."_".$field );
							}
						}
						?>
								</div>
							</div>
				<?php
				if ($type == 'json') {
					$tn_keys = $table_cols[$field]['keys'];
					$keys = !empty($tn_keys) ? explode(',', $tn_keys) : array();
					$json_def_values = json_decode($def_value, true);
					if (count($json_def_values) > 0) {
						$tn_json_value = array();
						if (array_key_exists($reference_id, $lang_record_tn) && array_key_exists($field, $lang_record_tn[$reference_id]['content'])) {
							$tn_json_value = json_decode($lang_record_tn[$reference_id]['content'][$field], true);
						}
						foreach ($json_def_values as $jkey => $jval) {
							if ((!in_array($jkey, $keys) && count($keys) > 0) || empty($jval)) {
								continue;
							}
							?>
							<div class="vbo-translations-element-row vbo-translations-element-row-nested" data-reference="<?php echo $ltag.'-'.$reference_id; ?>">
								<div class="vbo-translations-element-lbl"><?php echo !is_numeric($jkey) ? ucwords($jkey) : '&nbsp;'; ?></div>
								<div class="vbo-translations-element-val">
								<?php
								if (strlen($jval) > 40) {
								?>
									<textarea rows="5" cols="170" style="min-width: 60%;" name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>][<?php echo $jkey; ?>]"><?php echo isset($tn_json_value[$jkey]) ? $tn_json_value[$jkey] : ''; ?></textarea>
								<?php
								} else {
								?>
									<input type="text" name="tn[<?php echo $ltag; ?>][<?php echo $reference_id; ?>][<?php echo $field; ?>][<?php echo $jkey; ?>]" value="<?php echo isset($tn_json_value[$jkey]) ? $tn_json_value[$jkey] : ''; ?>" size="40"/>
								<?php
								}
								?>
								</div>
							</div>
							<?php
						}
					}
				}
			}
			?>
						</div>
					</div>
			<?php
		}
	}
	?>
				</div>
			</fieldset>
		<?php
		//echo '<pre>'.print_r($table_def_dbvals, true).'</pre><br/>';
		//echo '<pre>'.print_r($table_cols, true).'</pre><br/>';
	}
	?>
		</div>
	<?php
}
	?>
	</div>
	<input type="hidden" name="vbo_lang" class="vbo_lang" value="<?php echo $vbo_tn->default_lang; ?>">
	<input type="hidden" name="task" value="translations">
	<input type="hidden" name="option" value="com_vikbooking">
	<br/>
	<table align="center">
		<tr>
			<td align="center"><?php echo $vbo_tn->getPagination(); ?></td>
		</tr>
		<tr>
			<td align="center">
				<select name="limit" onchange="document.adminForm.limitstart.value = '0'; document.adminForm.submit();">
					<option value="2"<?php echo $vbo_tn->lim == 2 ? ' selected="selected"' : ''; ?>>2</option>
					<option value="5"<?php echo $vbo_tn->lim == 5 ? ' selected="selected"' : ''; ?>>5</option>
					<option value="10"<?php echo $vbo_tn->lim == 10 ? ' selected="selected"' : ''; ?>>10</option>
					<option value="20"<?php echo $vbo_tn->lim == 20 ? ' selected="selected"' : ''; ?>>20</option>
				</select>
			</td>
		</tr>
	</table>
</form>
<script type="text/Javascript">
jQuery(document).ready(function(){
	jQuery('.vbo-translation-tab').click(function() {
		var langtag = jQuery(this).attr('data-vbolang');
		if (jQuery('#vbo_langcontent_'+langtag).length) {
			jQuery('.vbo_lang').val(langtag);
			jQuery('.vbo-translation-tab').removeClass('vbo-translation-tab-default');
			jQuery(this).addClass('vbo-translation-tab-default');
			jQuery('.vbo-translation-langcontent').hide();
			jQuery('#vbo_langcontent_'+langtag).fadeIn();
		} else {
			jQuery('.vbo-translation-tab').removeClass('vbo-translation-tab-default');
			jQuery(this).addClass('vbo-translation-tab-default');
			jQuery('.vbo-translation-langcontent').hide();
			jQuery('#vbo_langcontent_ini').fadeIn();
		}
	});
<?php
if (!empty($cur_langtab)) {
	?>
	jQuery('.vbo-translation-tab').each(function() {
		var langtag = jQuery(this).attr('data-vbolang');
		if (langtag != '<?php echo $cur_langtab; ?>') {
			return true;
		}
		if (jQuery('#vbo_langcontent_'+langtag).length) {
			jQuery('.vbo_lang').val(langtag);
			jQuery('.vbo-translation-tab').removeClass('vbo-translation-tab-default');
			jQuery(this).addClass('vbo-translation-tab-default');
			jQuery('.vbo-translation-langcontent').hide();
			jQuery('#vbo_langcontent_'+langtag).fadeIn();
		}
	});
	<?php
}
?>
});
</script>
<?php
}
