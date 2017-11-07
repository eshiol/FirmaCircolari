<?php
/**
 * @version		3.5.11 components/com_buttons/buttons.php
 *
 * @package		Buttons
 * @subpackage	com_buttons
 * @since		3.4
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2015, 2017 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * Buttons is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

$params = JComponentHelper::getParams('com_buttons');
if ($params->get('debug') || defined('JDEBUG') && JDEBUG)
{
	JLog::addLogger(array('text_file' => $params->get('log', 'eshiol.php'), 'extension' => 'com_buttons'), JLog::ALL, array('com_buttons'));
}
JLog::addLogger(array('logger' => 'messagequeue', 'extension' => 'com_buttons'), JLOG::ALL & ~JLOG::DEBUG, array('com_buttons'));

//require_once JPATH_COMPONENT . '/helpers/route.php';
JFactory::getDocument()->addScriptDeclaration("if (typeof(Joomla.JDEBUG) === 'undefined') {
	Joomla.JDEBUG = ".((defined('JDEBUG') && JDEBUG) ? true : false).";
}");

$controller	= JControllerLegacy::getInstance('Buttons');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
