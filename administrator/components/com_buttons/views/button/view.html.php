<?php
/**
 * @version		3.5.11 administrator/components/com_buttons/views/button/view.html.php
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

/**
 * View to edit a button.
 */
class ButtonsViewButton extends JViewLegacy
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$this->state	= $this->get('State');
		$this->item		= $this->get('Item');
		$this->form		= $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();
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

		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user		= JFactory::getUser();
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

		// Since we don't track these assets at the item level, use the category id.
		$canDo		= JHelperContent::getActions('com_buttons', 'category', $this->item->catid);

		JToolbarHelper::title(JText::_('COM_BUTTONS_MANAGER_BUTTON'), 'buttons');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit')||(count($user->getAuthorisedCategories('com_buttons', 'core.create')))))
		{
			JToolbarHelper::apply('button.apply');
			JToolbarHelper::save('button.save');
		}
		if (!$checkedOut && (count($user->getAuthorisedCategories('com_buttons', 'core.create'))))
		{
			JToolbarHelper::save2new('button.save2new');
		}
		// If an existing item, can save to a copy.
		if (!$isNew && (count($user->getAuthorisedCategories('com_buttons', 'core.create')) > 0))
		{
			JToolbarHelper::save2copy('button.save2copy');
		}
		if (empty($this->item->id))
		{
			JToolbarHelper::cancel('button.cancel');
		}
		else
		{
			if ($this->state->params->get('save_history', 0) && $user->authorise('core.edit'))
			{
				JToolbarHelper::versions('com_buttons.button', $this->item->id);
			}

			JToolbarHelper::cancel('button.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
