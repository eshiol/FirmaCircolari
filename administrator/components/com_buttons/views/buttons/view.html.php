<?php
/**
 * @version		3.5.11 administrator/components/com_buttons/views/buttons/view.html.php
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
 * View class for a list of buttons.
 */
class ButtonsViewButtons extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		ButtonsHelper::addSubmenu('buttons');

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

		JToolbarHelper::title(JText::_('COM_BUTTONS_MANAGER_BUTTONS'), 'buttons');

		if (count($user->getAuthorisedCategories('com_buttons', 'core.create')) > 0)
		{
			JToolbarHelper::addNew('button.add');
		}

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('button.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('buttons.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('buttons.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			JToolbarHelper::archiveList('buttons.archive');
			JToolbarHelper::checkin('buttons.checkin');
		}

		if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'buttons.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('buttons.trash');
		}

		// Add a batch button
		if ($user->authorise('core.create', 'com_buttons') && $user->authorise('core.edit', 'com_buttons')
			&& $user->authorise('core.edit.state', 'com_buttons'))
		{
			JHtml::_('bootstrap.modal', 'collapseModal');
			$title = JText::_('JTOOLBAR_BATCH');

			// Instantiate a new JLayoutFile instance and render the batch button
			$layout = new JLayoutFile('joomla.toolbar.batch');

			$dhtml = $layout->render(array('title' => $title));
			$bar->appendButton('Custom', $dhtml, 'batch');
		}

		if ($user->authorise('core.admin', 'com_buttons') || $user->authorise('core.options', 'com_buttons'))
		{
			JToolbarHelper::preferences('com_buttons');
		}

		JHtmlSidebar::setAction('index.php?option=com_buttons&view=buttons');

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_CATEGORY'),
			'filter_category_id',
			JHtml::_('select.options', JHtml::_('category.options', 'com_buttons'), 'value', 'text', $this->state->get('filter.category_id'))
		);

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_PUBLISHED'),
			'filter_state',
			JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.state'), true)
		);

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_LANGUAGE'),
			'filter_language',
			JHtml::_('select.options', JHtml::_('contentlanguage.existing', true, true), 'value', 'text', $this->state->get('filter.language'))
		);

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_TAG'),
			'filter_tag',
			JHtml::_('select.options', JHtml::_('tag.options', true, true), 'value', 'text', $this->state->get('filter.tag'))
		);

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
			'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'a.state' => JText::_('JSTATUS'),
			'a.title' => JText::_('JGLOBAL_TITLE'),
			'a.access' => JText::_('JGRID_HEADING_ACCESS'),
			'a.hits' => JText::_('JGLOBAL_HITS'),
			'a.language' => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
