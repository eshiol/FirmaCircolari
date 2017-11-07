<?php
/**
 * @version		3.5.11 administrator/components/com_buttons/models/extras.php
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

/**
 * Methods supporting a list of button records.
 */
class ButtonsModelExtras extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  An optional associative array of configuration settings.
	 *
	 * @see     JControllerLegacy
	 * @since   3.4
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
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 *
	 * @note    Calling getState in this method will result in recursion.
	 * @since   3.4
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

		$contentId = $this->getUserStateFromRequest($this->context . '.filter.content_id', 'filter_content_id', '');
		$this->setState('filter.content_id', $contentId);

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
	 * @since   3.4
	 */
	protected function getStoreId($id = '')
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.content_id');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.editor_user_id');
		
		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   3.4
	 */
	protected function getListQuery()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.id, a.asset_id, a.catid, a.state, a.editor_user_id, a.value'
			)
		);
		$query->from($db->quoteName('#__buttons_extras') . ' AS a');

		// Join over the users for the checked out user.
		$query
			->select('b.title AS title, b.name AS alias')
			->join('LEFT', $db->quoteName('#__assets').' AS b ON '.$db->quoteName('a.asset_id').'='.$db->quoteName('b.id'));
		;
		
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor_name')
			->join('LEFT', '#__users AS uc ON uc.id=a.editor_user_id');

		// Join over the categories.
		$query->select('c.title AS category_title')
			->join('LEFT', '#__categories AS c ON c.id = a.catid');

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (1, 2))');
		}

		// Filter by content.
		$contentId = $this->getState('filter.content_id');

		if (is_numeric($contentId))
		{
			$article = JTable::getInstance('Content');
			$article->load($contentId);
			$asset_id = $article->asset_id;
			$query->where('a.asset_id = ' . (int) $contentId);
		}
		
		// Filter by category.
		$categoryId = $this->getState('filter.category_id');

		if (is_numeric($categoryId))
		{
			$query->where('a.catid = ' . (int) $categoryId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				//$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
				$query->where('(b.title LIKE ' . $search . ')');
			}
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
/*
		if ($orderCol == 'a.ordering' || $orderCol == 'category_title')
		{
			$orderCol = 'c.title ' . $orderDirn . ', a.ordering';
		}
*/
		if ($orderCol)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}
		
		JLog::add(new JLogEntry($query, JLOG::DEBUG, 'com_buttons'));
		return $query;
	}
}
