<?php
/**
 * @version		3.5.11 plugins/content/buttons/tmpl/default.php
 * 
 * @package		Buttons
 * @subpackage	plg_content_buttons
 * @since		3.4
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2015, 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * Buttons is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

$config = JFactory::getConfig();
$offset = $config->get('offset');

?>
<table class="buttonsreport-report table">
<?php foreach($items as $item): ?>
	<tr>
		<td class="buttonsreport-name" style="width:25%"><?php echo $item->editor_name; ?></td>
		<td class="buttonsreport-date" style="width:25%"><?php echo $item->modified ? JHtml::_('date', $item->modified, JText::_('DATE_FORMAT_LC2'), $offset) : ''; ?></td>
		<td class="buttonsreport-value" style="text-align:right"><?php echo $item->toolbar; ?></td>
	</tr>
<?php endforeach; ?>
</table>