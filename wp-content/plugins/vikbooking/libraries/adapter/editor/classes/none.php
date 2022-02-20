<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.editor
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.editor.editor');

/**
 * Editor class to handle a simple textarea.
 *
 * @since 10.0
 */
class JEditorNone extends JEditor
{
	/**
	 * @override
	 * Renders the editor area.
	 *
	 * @param   string   $name     The control name.
	 * @param   string   $html     The contents of the text area.
	 * @param   string   $width    The width of the text area (px or %).
	 * @param   string   $height   The height of the text area (px or %).
	 * @param   integer  $col      The number of columns for the textarea.
	 * @param   integer  $row      The number of rows for the textarea.
	 * @param   boolean  $buttons  True and the editor buttons will be displayed.
	 * @param   string   $id       An optional ID for the textarea (@since 10.1.20).
	 * @param   array    $params   Associative array of editor parameters (@since 10.1.35).
	 *
	 * @return  string 	 The editor.
	 */
	protected function render($name, $html, $width, $height, $col, $row, $buttons, $id, $params)
	{
		// only add "px" to width and height if they are not given as a percentage
		if (is_numeric($width))
		{
			$width .= 'px';
		}

		if (is_numeric($height))
		{
			$height .= 'px';
		}

		?>
		<textarea
			name="<?php echo $name; ?>"
			id="<?php echo $id; ?>"
			rows="<?php echo $row; ?>"
			cols="<?php echo $col; ?>"
			style="width: <?php echo $width; ?>; height: <?php echo $height; ?>;"
			<?php echo !empty($params['readonly']) ? 'readonly' : ''; ?>
			<?php echo !empty($params['disabled']) ? 'disabled' : ''; ?>
		><?php echo $html; ?></textarea>

		<?php

		/**
		 * Inject default editor instance within Joomla.editors pool.
		 *
		 * @since 10.1.17
		 */
		JFactory::getDocument()->addScriptDeclaration(
<<<JS
jQuery(document).ready(function() {
	Joomla.editors.instances['$id'] = {
		id: 	  '$id',
		getValue: function () {	
			return jQuery('textarea#' + this.id).val();
		},
		setValue: function (text) {
			return jQuery('textarea#' + this.id).val(text);
		},
	};
});
JS
		);
	}
}
