<?php
/**
 * @version		3.5.11 components/com_buttons/layouts/text.php
 * 
 * @package		Buttons
 * @subpackage	com_buttons
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

JHtml::_('behavior.framework');
JHtml::_('jquery.framework');
//JFactory::getDocument()->addScript(rtrim(JURI::base(true), '/') . '/'.'media/com_buttons/js/ajax.js');

use Joomla\Registry\Registry;

$default = true;
foreach($displayData->buttons as $id => $item)
{
	if ($item->value == ' null')
	{
		$default = false;
	}
	elseif ($item->value)
	{
		$default = false;
		echo '<div>'.$item->title.'</div>';
	}
}
if (count($displayData->buttons) && $default)
{
//	require_once JPATH_ADMINISTRATOR . '/components/com_buttons/helpers/category.php';
	$toolbar = JTable::getInstance('Category');
	$toolbar->load(array_values($displayData->buttons)[0]->catid);
	$tparams = new Registry;
	$tparams->loadString($toolbar->params);
	
	echo '<div>'.$tparams->get('toolbar_default').'</div>';
}