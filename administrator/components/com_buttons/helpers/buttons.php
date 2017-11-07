<?php
/**
 * @version		3.5.11 administrator/components/com_buttons/helpers/buttons.php
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

use Joomla\Registry\Registry;

/**
 * Buttons helper.
 */
class ButtonsHelper
{
	static private $_buttons = array();
	
	static function getToolbar($catid, $asset_id, $userid, $writable, $style="buttons")
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		JLog::add(new JLogEntry('toolbar: '.$catid, JLOG::DEBUG, 'com_buttons'));
		
		$db = JFactory::getDbo();
		$basepath = rtrim(JURI::root(true), '/') . '/';
	
		require_once JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/category.php';
		require_once JPATH_SITE.'/components/com_buttons/models/category.php';
		$model = JModelLegacy::getInstance('Category', 'ButtonsModel', array('ignore_request' => true));
		$model->setState('category.id', $catid);
		$model->setState('filter.c.published', 1);
		$model->setState('filter.state', 1);
		
		$app    = JFactory::getApplication();
		$isAdmin = $app->isAdmin();
		$isReport =  $app->input->getString('buttons') == 'report';
		$model->setState('filter.report', $isAdmin || $isReport);
		
		$items = $model->getItems();
		if (!$items)
		{
			return;
		}
		
		$params = JComponentHelper::getParams('com_buttons');
		
		$toolbar = JTable::getInstance('Category');
		$toolbar->load($catid);
		$tparams = new Registry;
		$tparams->loadString($toolbar->params);
		$params->merge($tparams);
		JLog::add(new JLogEntry('params: '.print_r($params, true), JLOG::DEBUG, 'plg_content_buttons'));
		$toolbar = null;

		if ($params->get('audit', 0) && !$isAdmin && !$isReport)
		{
			try {
				// Create and populate an object.
				$extra = new stdClass();
				$extra->catid = $catid;
				$extra->asset_id = $asset_id;
				$extra->editor_user_id = $userid;
				$extra->value = 0;
				$extra->modified = JFactory::getDate()->toSql();
				$extra->state = 1;
				$db->insertObject('#__buttons_extras', $extra);
			} catch (Exception $e) {
			}
		}
		
		$query = $db->getQuery(true)
			->select($db->qn('state'))
			->from($db->qn('#__buttons_extras'))
			->where($db->qn('catid').' = '.$catid)
			->where($db->qn('editor_user_id').' = '.$userid)
			->where($db->qn('asset_id').' = '.$asset_id)
			;
		if (!$isAdmin)
			$query->where($db->qn('state').' IN (1, 2)');
		$toolbar_final = ($params->get('toolbar_final',0) &&
			(2 == (int)$db->setQuery($query)->loadResult())
			);

		$query =
			$db->getQuery(true)
			->select($db->qn('value'))
			->from($db->qn('#__buttons_extras'))
			->where($db->qn('catid').' = '.$catid)
			->where($db->qn('editor_user_id').' = '.$userid)
			->where($db->qn('asset_id').'='.$asset_id)
			;
		if (!$isAdmin)
			$query->where($db->qn('state').' IN (1, 2)');
		$value = $db->setQuery($query)->loadObject();
		
		$toolbar = new StdClass();
		$toolbar->catid=$catid;
		$toolbar->asset_id=$asset_id;
		$toolbar->toolbar_type=$params->get('toolbar_type', 0);
		$toolbar->toolbar_final = (!$writable | $toolbar_final ? ' readonly' : '');

		// prepare toolbar
		$doc = JFactory::getDocument();
		foreach ($items as $item)
		{
			$temp = $item->images;
			$item->images = new Registry;
			$item->images->loadString($temp, 'JSON');
				
			if (!in_array($item->id, self::$_buttons))
			{
				self::$_buttons[] = $item->id;
				$doc->addStyleDeclaration(
					'.button-'.$item->id
					.'{'
					.'display:inline-block;'
					.'width:'.$item->images->get('width',32).'px;'
					.'height:'.$item->images->get('height',32).'px;'
					.'padding: 0 5px;'
					.'border:0;'
					.'background: url('.$basepath.$item->images->get('image').') no-repeat;'
					.'background-position:0px 0px;'
					.'}'
					.'.button-'.$item->id.'.active'
					.'{background-position:0px -'.(2*$item->images->get('height',32)).'px;}'
					.(!$toolbar->toolbar_final
						? '.button-'.$item->id.':hover'
						.'{background-position:0px -'.$item->images->get('height',32).'px;}'
						.'.button-'.$item->id.'.active:hover'
						.'{background-position:0px -'.$item->images->get('height',32).'px;}'
						.'.button-'.$item->id.'.pressing:hover'
						.'{background-position:0px 0px;}'
						.'.button-'.$item->id.'.active.pressing:hover'
						.'{background-position:0px -'.(2*$item->images->get('height',32)).'px;}'
						: '')
					);
			}
		}

		$toolbar->buttons = array();
		// Prepare the data.
		foreach ($items as $item)
		{
			if (is_null($value))
				$item->value = ' null';
			else
				$item->value = ((pow(2, ($item->value-1)) & $value->value) ? ' active' : '');
			$toolbar->buttons[$item->id] = $item;
		}
		$layout = new JLayoutFile(($writable ? 'toolbar' : $style), JPATH_SITE.'/components/com_buttons/layouts');
		return $layout->render($toolbar);
	}
	
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public static function addSubmenu($vName = 'buttons')
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		JHtmlSidebar::addEntry(
			JText::_('COM_BUTTONS_SUBMENU_BUTTONS'),
			'index.php?option=com_buttons&view=buttons',
			$vName == 'buttons'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_BUTTONS_SUBMENU_CATEGORIES'),
			'index.php?option=com_categories&extension=com_buttons',
			$vName == 'categories'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_BUTTONS_SUBMENU_EXTRAS'),
			'index.php?option=com_buttons&view=extras',
			$vName == 'extras'
		);
	}

	/*
	 * where top, bottom, both
	 */
	static function getToolbars($row, $where = 'both')
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		JLog::add(new JLogEntry('where: '.$where, JLOG::DEBUG, 'com_buttons'));
		
		$registry = new Registry;
		if (isset($row->attribs))
		{
			$registry->loadString($row->attribs);
		}
		else
		{
			return;
		}
		$arr = $registry->toArray();
		
		$toolbars = array();
		if (in_array($where, array('top', 'both')))
		{
			if (isset($arr['toolbar_top']))
			{
				JLog::add(new JLogEntry('toolbar_top: '.print_r($arr['toolbar_top'], true), JLOG::DEBUG, 'com_buttons'));
				$toolbars = array_merge($toolbars, $arr['toolbar_top']);
			}
			$parent_id = $row->catid;
			while ($parent_id)
			{
				$category = JTable::getInstance('Category');
				$category->load($parent_id);
				$cparams = new Registry;
				$cparams->loadString($category->params);
				$carr = $cparams->toArray();
				if (isset($carr['toolbar_top']))
				{
					$toolbars = array_merge($toolbars, $carr['toolbar_top']);
				}
				$parent_id = $category->parent_id;
			}
		}
		
		if (in_array($where, array('bottom', 'both')))
		{
			if (isset($arr['toolbar_bottom']))
			{
				JLog::add(new JLogEntry('toolbar_bottom: '.print_r($arr['toolbar_bottom'], true), JLOG::DEBUG, 'com_buttons'));
				$toolbars = array_merge($toolbars, $arr['toolbar_bottom']);
			}
			$parent_id = $row->catid;
			while ($parent_id)
			{
				$category = JTable::getInstance('Category');
				$category->load($parent_id);
				$cparams = new Registry;
				$cparams->loadString($category->params);
				$carr = $cparams->toArray();
				if (isset($carr['toolbar_bottom']))
				{
					$toolbars = array_merge($toolbars, $carr['toolbar_bottom']);
				}
				$parent_id = $category->parent_id;
			}
		}
		$toolbars = array_unique($toolbars);
		JLog::add(new JLogEntry('toolbars: '.print_r($toolbars, true), JLOG::DEBUG, 'com_buttons'));
		
		//TODO: remove not published toolbars
		
		
		return $toolbars;
	}
	
	static function urlRemoveVar($pageURL, $key)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		$url = parse_url($pageURL);
		if (!isset($url['query'])) return $pageURL;
		parse_str($url['query'], $query_data);
		if (!isset($query_data[$key])) return $pageURL;
		unset($query_data[$key]);
		$url['query'] = http_build_query($query_data);
		$scheme   = isset($url['scheme']) ? $url['scheme'] . '://' : '';
		$host     = isset($url['host']) ? $url['host'] : '';
		$port     = isset($url['port']) ? ':' . $url['port'] : '';
		$user     = isset($url['user']) ? $url['user'] : '';
		$pass     = isset($url['pass']) ? ':' . $url['pass']  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($url['path']) ? $url['path'] : '';
		$query    = isset($url['query']) && ($url['query']!='') ? '?' . $url['query'] : '';
		$fragment = isset($url['fragment']) ? '#' . $url['fragment'] : '';
		return "$scheme$user$pass$host$port$path$query$fragment";
	}
	
	public static function copyright()
	{
		JLog::add(__METHOD__, JLog::DEBUG, 'com_buttons');
	
		if ($xml = JFactory::getXML(JPATH_COMPONENT_ADMINISTRATOR.'/buttons.xml'))
		{
			return
			'<div class="clearfix"> </div>'.
			'<div style="text-align:center;font-size:xx-small">'.
			JText::_($xml->name).' '.$xml->version.' '.str_replace('(C)', '&copy;', $xml->copyright).
			'</div>';
		}
	}	
}
