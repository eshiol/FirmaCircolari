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

/**
 * View class for a list of buttons.
 */
class ButtonsViewExtras extends JViewLegacy
{
	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;
	
	/**
	 * The pagination object
	 *
	 * @var  JPagination
	 */
	protected $pagination;
	
	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;
	
	/**
	 * Form object for search filters
	 *
	 * @var  JForm
	 */
	public $filterForm;
	
	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;
	
	/**
	 * The sidebar markup
	 *
	 * @var  string
	 */
	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		ButtonsHelper::addSubmenu('extras');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since   3.4
	 */
	protected function addToolbar()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		require_once JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php';

		$state = $this->get('State');
		$canDo = JHelperContent::getActions('com_buttons', 'category', $state->get('filter.category_id'));
		$user  = JFactory::getUser();

		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');

		JToolbarHelper::title(JText::_('COM_BUTTONS_MANAGER_BUTTONS'), 'extras');

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('extras.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('extras.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			JToolbarHelper::archiveList('extras.archive');
		}

		if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'extras.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('extras.trash');
		}

		if ($user->authorise('core.admin', 'com_buttons') || $user->authorise('core.options', 'com_buttons'))
		{
			JToolbarHelper::preferences('com_buttons');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.4
	 */
	protected function getSortFields()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		return array(
			'a.state' => JText::_('JSTATUS'),
			'b.title' => JText::_('JGLOBAL_TITLE'),
			'editor_name' => JText::_('JAUTHOR'),
			'category_title' => JText::_('JCATEGORY'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}

	/**
	 * Build a list of authors
	 *
	 * @return  JDatabaseQuery
	 */
	public function getAuthors()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$authors = array();

		$db = JFactory::getDbo();

		return $db->setQuery(
			$db->getQuery(true)
				->select('DISTINCT '.$db->qn('editor_user_id').' AS '.$db->qn('value'))
				->from($db->qn('#__buttons_extras').' a')
				// Join over the users
				->select($db->qn('name').' AS '.$db->qn('text'))
				->join('LEFT', $db->qn('#__users').' AS '.$db->qn('uc').' ON '.$db->qn('uc.id').'='.$db->qn('a.editor_user_id'))
			)->loadObjectList()
			;
	}

	/**
	 * Build a list of content
	 *
	 * @return  JDatabaseQuery
	 */
	public function getContents()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$authors = array();

		$db = JFactory::getDbo();

		return $db->setQuery(
			$db->getQuery(true)
				->select('DISTINCT '.$db->qn('asset_id').' AS '.$db->qn('value'))
				->from($db->qn('#__buttons_extras').' a')
				// Join over the assets
				->select($db->qn('title').' AS '.$db->qn('text'))
				->join('LEFT', $db->qn('#__assets').' AS '.$db->qn('as').' ON '.$db->qn('as.id').'='.$db->qn('a.asset_id'))
			)->loadObjectList()
			;
	}

}
