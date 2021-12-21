<?php
/**
 * @package     Joomla.Site
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

use Joomla\Registry\Registry;
require_once JPATH_COMPONENT . '/controller.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/category.php';

/**
 * Button Controller (json version)
 */
class ButtonsControllerButton extends ButtonsController
{
    function click()
    {
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

    	// Get the application object.
        $app = JFactory::getApplication();
        $response = array();
        $user = JFactory::getUser();

        if ($user->id == 0)
		{
			$response["success"] = false;
			$response["error"] = JText::_('COM_BUTTONS_MSG_SESSION_EXPIRED');
		}
		elseif (
			($asset_id = $this->input->getInt('asset_id', 0)) &&
       		($catid = $this->input->getInt('catid', 0)) &&
       		(($id = $this->input->getInt('id', false)) !== false)
       	){
       		JLog::add(new JLogEntry('toolbar: '.$catid, JLOG::DEBUG, 'com_buttons'));

       		$toolbar = JTable::getInstance('Category');
       		$toolbar->load($catid);
       		$tparams = new Registry;
       		$tparams->loadString($toolbar->params);
       		$cparams = JComponentHelper::getParams('com_buttons');
       		$cparams->merge($tparams);
       		JLog::add(new JLogEntry('params: '.print_r($cparams, true), JLOG::DEBUG, 'com_buttons'));

       		$db    = JFactory::getDbo();
       		$state = $db->setQuery(
   				$db->getQuery(true)
   				->select($db->qn('state'))
   				->from($db->qn('#__buttons_extras'))
   				->where($db->qn('catid').' = '.$catid)
   				->where($db->qn('editor_user_id').' = '.$user->id)
   				->where($db->qn('asset_id').' = '.$asset_id)
   				)->loadResult();
       		if (!is_null($state) &&
       			(($cparams->get('toolbar_final', 0) == 1) && ($state == 2)
				|| ($cparams->get('toolbar_lockondelete', 1) == 1) && ($state == -2)
				|| ($state == 0)
				))
			{
				$response["success"] = false;
				$response["error"] = JText::_('COM_BUTTONS_MSG_READONLY_MODE');
			}
			else
			{
				$response["success"] = true;

				$response['asset_id'] = $asset_id;
				$response['catid'] = $catid;

	       		$v = (int)$db->setQuery(
	       			$db->getQuery(true)
	       			->select('power(2,'.$db->qn('value').'-1)')
	       			->from($db->qn('#__buttons'))
	       			->where($db->qn('id').' = '.$id)
	       			)->loadResult();

	       		if ($cparams->get('toolbar_type', 0) == 0)
	       			$nvalue = $v ^ (int)$db->setQuery(
		       			$db->getQuery(true)
		       			->select($db->qn('value'))
		       			->from($db->qn('#__buttons_extras'))
		       			->where($db->qn('catid').' = '.$catid)
		       			->where($db->qn('editor_user_id').' = '.$user->id)
		       			->where($db->qn('asset_id').' = '.$asset_id)
	       				->where($db->qn('state').' != -2')
		       		 	)->loadResult()
	       				;
	       		else
	       			$nvalue = $v;

	       		// Create and populate an object.
	       		$extra = new stdClass();
				$extra->catid = $catid;
				$extra->asset_id = $asset_id;
				$extra->editor_user_id = $user->id;
				$extra->value = $nvalue;
				$extra->modified = JFactory::getDate()->toSql();

				if ($cparams->get('toolbar_final', 0))
				{
					$response['readonly'] = 'readonly';
					$extra->state = 2;
				}
				else
				{
					$extra->state = 1;
				}

				try {
					$db->insertObject('#__buttons_extras', $extra);
				} catch (Exception $e) {
					$db->updateObject('#__buttons_extras', $extra, array('catid', 'asset_id', 'editor_user_id'));
				}

				$buttons = $db->setQuery(
					$db->getQuery(true)
					->select($db->qn('id'))
					->select('power(2,'.$db->qn('value').'-1) & '.$nvalue.' AS '.$db->qn('value'))
					->from($db->qn('#__buttons'))
					->where($db->qn('catid').' = '.$catid)
					->where($db->qn('state').' = 1')
					)->loadAssocList();
				$response['buttons'] = $buttons;
			}
		}
       	else
       	{
	        $response["success"] = false;
			$response["error"] = JText::_('COM_BUTTONS_MSG_UNKNOWN_ERROR');
       	}

       	if ($response["success"])
       	{
	       	$title = $db->setQuery(
	       		$db->getQuery(true)
	       		->select($db->qn('title'))
	       		->from($db->qn('#__assets'))
	       		->where($db->qn('id').' = '.$asset_id)
	       		)->loadResult();
	
	       	$button = $db->setQuery(
	       		$db->getQuery(true)
	       		->select($db->qn('title'))
	       		->from($db->qn('#__buttons'))
	       		->where($db->qn('id').' = '.$id)
	       		)->loadResult();
	
			JLog::add(new JLogEntry($user->username . '[id=' . $user->id . ']'
				. ' clicked the button ' . $button . '[id=' . $id . ']'
				. ' on ' . $title . ' [value=' . $nvalue . ']'
				. ' from ' . self::getAddr(),
				JLOG::INFO, 
				'com_buttons'));
       	}

       	// Get the document object.
       	$document = JFactory::getDocument();

       	// Set the MIME type for JSON output.
       	$document->setMimeEncoding('application/json');

       	// Change the suggested filename.
       	JResponse::setHeader('Content-Disposition','attachment;filename="result.json"');

       	echo json_encode($response);

        // Close the application.
        $app->close();
    }

    private static function check_ip($ip)
    {
    	// ranges know as private or virtual IP's (unused in public internet, see ieee.org)
    	static $ignore_ip = array (
    		array('0.0.0.0','2.255.255.255'),
    		array('10.0.0.0','10.255.255.255'),
    		array('127.0.0.0','127.255.255.255'),
    		array('169.254.0.0','169.254.255.255'),
    		array('172.16.0.0','172.31.255.255'),
    		array('192.0.0.0','192.0.0.255'),
    		array('192.0.2.0','192.0.2.255'),
    		array('192.88.99.0','192.88.99.255'),
    		array('192.168.0.0','192.168.255.255'),
    		array('198.18.0.0','198.19.255.255'),
    		array('198.51.100.0','198.51.100.255'),
    		array('203.0.113.0','203.0.113.255'),
    		array('224.0.0.0','255.255.255.255')
    	);
    	if (!empty($ip) && ip2long($ip) != -1) {
    		foreach ($ignore_ip as $r) {
    			$min = ip2long($r[0]);
    			$max = ip2long($r[1]);
    			if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
    		}
    		return true;
    	} else {
    		return false;
    	}
    }
    
    private static function getAddr()
    {
    	if (isset($_SERVER["HTTP_CLIENT_IP"]) && self::check_ip($_SERVER["HTTP_CLIENT_IP"])) {
    		return $_SERVER["HTTP_CLIENT_IP"];
    	}
    	if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
    		foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
    			if (self::check_ip(trim($ip))) {
    				return $ip;
    			}
    		}
    	}
    	if ( isset($_SERVER["HTTP_X_FORWARDED"]) && self::check_ip($_SERVER["HTTP_X_FORWARDED"])) {
    		return $_SERVER["HTTP_X_FORWARDED"];
    	} elseif ( isset($_SERVER["HTTP_FORWARDED_FOR"]) && self::check_ip($_SERVER["HTTP_FORWARDED_FOR"])) {
    		return $_SERVER["HTTP_FORWARDED_FOR"];
    	} elseif ( isset($_SERVER["HTTP_FORWARDED"]) && self::check_ip($_SERVER["HTTP_FORWARDED"])) {
    		return $_SERVER["HTTP_FORWARDED"];
    	} else {
    		return $_SERVER["REMOTE_ADDR"];
    	}
    }
}