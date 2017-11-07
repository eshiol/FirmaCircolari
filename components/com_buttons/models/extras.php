<?php
/**
 * @version		3.5.11 components/com_buttons/models/extras.php
 * 
 * @package		Buttons
 * @subpackage	com_buttons
 * @since		3.4.7
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

use Joomla\Registry\Registry;

/**
 * Methods supporting a list of button records.
 */
class ButtonsModelExtras extends JModelList
{
	/**
	 * A Registry object holding the global parameters for the plugin
	 *
	 * @var    Registry
	 * @since  3.5
	 */
	private $cparams = null;

	/**
	 * Constructor.
	 *
	 * @param   array  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 * @since   3.4.7
	 */
	public function __construct($config = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'asset_id', 'a.asset_id', 
				'title', 'b.title',
				'catid', 'a.catid', 'category_title',
				'editor_user_id', 'a.editor_user_id', 'editor_name',
				'value', 'a.value',
				'state', 'a.state',
			);
		}

		parent::__construct($config);
		$this->cparams = JComponentHelper::getParams('com_buttons');
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 *
	 * @note    Calling getState in this method will result in recursion.
	 * @since   3.4.7
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		// Load the filter state.
		$author = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $author);

		$content = $this->getUserStateFromRequest($this->context . '.filter.content_id', 'filter_content_id');
		$this->setState('filter.content_id', $content);

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', '');
		$this->setState('filter.category_id', $categoryId);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_buttons');
		$this->setState('params', $params);

		// List state information.
		parent::populateState();
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   3.4.7
	 */
	protected function getStoreId($id = '')
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.editor_user_id');
		
		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   3.4.7
	 */
	protected function getListQuery()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		// Filter by asset id
		if (!($asset_id = $this->getState('filter.asset_id')))
		{
			return;
		}
		JLog::add(new JLogEntry('asset_id: '.$asset_id, JLOG::DEBUG, 'com_buttons'));
		
		if ($catid = $this->getState('filter.category_id'))
		{
			$toolbars = array($catid);
		}
		else 
		{
			$asset = JTable::getInstance('Asset');
			$asset->load($asset_id);
			$asset_name = $asset->name;
			JLog::add(new JLogEntry('asset_name: '.$asset_name, JLOG::DEBUG, 'com_buttons'));
			
			$i = strrpos($asset_name, '.');
			$type_alias = substr($asset_name, 0, $i);
			JLog::add(new JLogEntry('type_alias: '.$type_alias, JLOG::DEBUG, 'com_buttons'));
			$id = substr($asset_name, $i + 1);	
			JLog::add(new JLogEntry('id: '.$id, JLOG::DEBUG, 'com_buttons'));
			
			$contenttype = JTable::getInstance('Contenttype');
			$type_id = $contenttype->getTypeId($type_alias);
			$contenttype->load($type_id);
			
			$row = $contenttype->getContentTable();
			$row->load($id);
			
			$toolbars = ButtonsHelper::getToolbars($row);
			
			JLog::add(new JLogEntry('toolbars: '.print_r($toolbars, true), JLOG::DEBUG, 'com_buttons'));
		}
		
		// Create a new query object.
		$db = $this->getDbo();
		$gquery = null;
		$user = JFactory::getUser();
		
		JLog::add(new JLogEntry('cparams: '.print_r($this->cparams, true), JLOG::DEBUG, 'com_buttons'));		
		foreach($toolbars as $catid)
		{
			JLog::add(new JLogEntry('toolbar: '.$catid, JLOG::DEBUG, 'com_buttons'));
		
			$toolbar = JTable::getInstance('Category');
			$toolbar->load($catid);
			$tparams = new Registry;
			$tparams->loadString($toolbar->params);
			JLog::add(new JLogEntry('tparams: '.print_r($tparams, true), JLOG::DEBUG, 'com_buttons'));
			$cparams = clone($this->cparams);
			$cparams->merge($tparams);
			JLog::add(new JLogEntry('params: '.print_r($cparams, true), JLOG::DEBUG, 'com_buttons'));

			$groups = null;
			$query = null;
			if ($cparams->get('report_extended', 0))
			{
				$query = $db->getQuery(true)
					->select($db->qn('rules'))
					->from($db->qn('#__categories').'AS '.$db->qn('c'))
					->where($db->qn('c.published').'=1')
					// Join over the view levels
					->join('LEFT', $db->qn('#__viewlevels').' AS '.$db->qn('vl').' ON '.$db->qn('c.access').'='.$db->qn('vl.id'))
					;
				
				// Filter by toolbar
				if (is_numeric($catid))
				{
					$query->where($db->qn('c.id').'='.$catid);
				}
				JLog::add(new JLogEntry('query: '.$query, JLOG::DEBUG, 'com_buttons'));
				$groups = $db->setQuery($query)->loadResult();
				JLog::add(new JLogEntry('groups: '.$groups, JLOG::DEBUG, 'com_buttons'));
			}

			if ($groups)
			{
				$fieldlist = $db->qn('ug.user_id','editor_user_id');
				$fieldlist = 'distinct ' . $fieldlist;
				$query = $db->getQuery(true)
					->select($fieldlist)
					->from($db->qn('#__user_usergroup_map', 'ug'))
					// Filter by groups
					->where('(ISNULL('.$db->qn('ug.group_id').') OR '.$db->qn('ug.group_id').' IN ('.substr($groups,1,-1).'))')
					// Join over the users
					->select($db->qn('uc.name','editor_name'))
					->join('LEFT', $db->qn('#__users', 'uc').' ON '.$db->qn('uc.id').'='.$db->qn('ug.user_id'))
					->where('uc.block = 0')
					// Join over the buttons extras
					->select($db->qn('a.value'))
					->select($db->qn('a.modified'))
					->select($catid.' '.$db->qn('catid'))
					->join('LEFT', $db->qn('#__buttons_extras', 'a').' ON '.$db->qn('a.editor_user_id').'='.$db->qn('ug.user_id')
						// Filter by asset id
						.(($asset_id = $this->getState('filter.asset_id')) ? ' AND '.$db->qn('a.asset_id').'='.$asset_id : '')
						// Filter by toolbar
						.(is_numeric($catid) ? ' AND '.$db->qn('a.catid').'='.$catid : '')
						// do not show trashed or disabled
						.' AND '.$db->qn('a.state').' IN (1, 2)'
						)
					// Join over the categories
					->select($db->qn('c.title', 'category_title'))
					->join('LEFT', $db->qn('#__categories', 'c').' ON '.$db->qn('c.id').'='.$db->qn('a.catid').' AND '.$db->qn('c.published').'=1')
					;
			}
			if (!$query)
			{
				$fieldlist = $db->qn(array('a.editor_user_id'));
				$fieldlist[0] = 'distinct ' . $fieldlist[0];
				$query = $db->getQuery(true)
					->select($fieldlist)
					->select($db->qn('uc.name','editor_name'))
					->select($db->qn(array('a.value','a.modified','a.catid')))				
					->from($db->qn('#__buttons_extras').' a')
					// do not show trashed or disabled
					->where('a.state IN (1, 2)')
					// Join over the users
					->join('LEFT', $db->qn('#__users', 'uc').' ON '.$db->qn('uc.id').'='.$db->qn('a.editor_user_id'))
					->where('uc.block = 0')
					// Join over the categories
					->select($db->qn('c.title', 'category_title'))
					->join('LEFT', $db->qn('#__categories', 'c').' ON '.$db->qn('c.id').'='.$db->qn('a.catid').' AND '.$db->qn('c.published').'=1')
					;
				// Filter by asset id
				if ($asset_id = $this->getState('filter.asset_id'))
				{
					$query->where('a.asset_id = ' . (int) $asset_id);
				}
				// Filter by toolbar
				if (is_numeric($catid))
				{
					$query->where('a.catid = ' . (int) $catid);
				}
			}

			if ($gquery)
			{
				$gquery->unionDistinct($query);
			}
			else 
			{
				$gquery = $query;
			}
		}

		// Sort by
		$sort = $this->cparams->get('sort', 'category_title, modified desc');
		if ($sort)
		{
			//$gquery->order(explode(',', $sort));
			/* workaroud to fix UNION and ORDER bug #4127 */
			$gquery = $db->getQuery(true)->setQuery((string)$gquery.' ORDER BY '.$sort);
		}

		JLog::add(new JLogEntry('gquery: '.$gquery, JLOG::DEBUG, 'com_buttons'));
		return $gquery;
	}
}
