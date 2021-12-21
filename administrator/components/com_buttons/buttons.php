<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Buttons
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

$params = JComponentHelper::getParams('com_buttons');
if ($params->get('debug') || defined('JDEBUG') && JDEBUG)
{
	JLog::addLogger(array('text_file' => $params->get('log', 'eshiol.log.php'), 'extension' => 'com_buttons_file'), JLog::ALL, array('com_buttons'));
}
if (PHP_SAPI == 'cli')
{
	JLog::addLogger(array('logger' => 'echo', 'extension' => 'com_buttons'), JLOG::ALL & ~JLOG::DEBUG, array('com_buttons'));
}
else
{
	JLog::addLogger(array('logger' => (null !== $params->get('logger')) ?$params->get('logger') : 'messagequeue', 'extension' => 'com_buttons'), JLOG::ALL & ~JLOG::DEBUG, array('com_buttons'));
	if ($params->get('phpconsole') && class_exists('JLogLoggerPhpconsole'))
	{
		JLog::addLogger(['logger' => 'phpconsole', 'extension' => 'com_buttons_phpconsole'],  JLOG::DEBUG, array('com_buttons'));
	}
}
JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_buttons'));

JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_buttons'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

$controller	= JControllerLegacy::getInstance('Buttons');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
