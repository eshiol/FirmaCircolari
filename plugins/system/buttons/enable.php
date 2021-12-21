<?php
/**
 * @package     Joomla.Plugins		
 * @subpackage  System.Buttons
 * 
 * @version     __DEPLOY_VERSION__
 * @since       3.4
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

class PlgSystemButtonsInstallerScript
{
	public function install($parent)
	{
		// Enable plugin
		$db  = JFactory::getDbo();
		$db->setQuery($db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('enabled')   . ' = 1')
			->where($db->qn('type')    . ' = ' . $db->q('plugin'))
			->where($db->qn('folder')  . ' = ' . $db->q('system'))
			->where($db->qn('element') . ' = ' . $db->q('buttons'))
		)->execute();
	}
}