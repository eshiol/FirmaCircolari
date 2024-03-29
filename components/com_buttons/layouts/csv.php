<?php
/**
 * @package     Joomla.Site
 * @subpackage  Buttons
 *
 * @version     __DEPLOY_VERSION__
 * @since       3.4.7
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2015 - 2021 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * Buttons is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

use Joomla\Registry\Registry;

$temp = array();
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
		$temp[] = $item->title;
	}
}
if (count($displayData->buttons) && $default)
{
//	require_once JPATH_ADMINISTRATOR . '/components/com_buttons/helpers/category.php';
	$toolbar = JTable::getInstance('Category');
	$toolbar->load(array_values($displayData->buttons)[0]->catid);
	$tparams = new Registry;
	$tparams->loadString($toolbar->params);

	$temp[] = $tparams->get('toolbar_default');
}
echo implode(' | ', $temp);
