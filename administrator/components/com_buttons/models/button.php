<?php
/**
 * @version		3.5.11 administrator/components/com_buttons/models/button.php
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

use Joomla\Registry\Registry;
use Joomla\String\String;

/**
 * Button model.
 */
class ButtonsModelButton extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  3.4
	 */
	public $typeAlias = 'com_buttons.button';

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  3.4
	 */
	protected $text_prefix = 'COM_BUTTONS';

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   3.4
	 */
	protected function canDelete($record)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		if (!empty($record->id))
		{
			if ($record->state != -2)
			{
				return;
			}

			if ($record->catid)
			{
				return JFactory::getUser()->authorise('core.delete', 'com_buttons.category.' . (int) $record->catid);
			}

			return parent::canDelete($record);
		}
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
	 *
	 * @since   3.4
	 */
	protected function canEditState($record)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		if (!empty($record->catid))
		{
			return JFactory::getUser()->authorise('core.edit.state', 'com_buttons.category.' . (int) $record->catid);
		}

		return parent::canEditState($record);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   3.4
	 */
	public function getTable($type = 'Button', $prefix = 'ButtonsTable', $config = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since   3.4
	 */
	public function getForm($data = array(), $loadData = true)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Get the form.
		$form = $this->loadForm('com_buttons.button', 'button', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		// Determine correct permissions to check.
		if ($this->getState('button.id'))
		{
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit');
		}
		else
		{
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.create');
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 *
	 * @since   3.4
	 */
	protected function loadFormData()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_buttons.edit.button.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			// Prime some default values.
			if ($this->getState('button.id') == 0)
			{
				$app = JFactory::getApplication();
				$data->set('catid', $app->input->get('catid', $app->getUserState('com_buttons.buttons.filter.category_id'), 'int'));
			}
		}

		$this->preprocessData('com_buttons.button', $data);

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since   3.4
	 */
	public function getItem($pk = null)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		if ($item = parent::getItem($pk))
		{
			// Convert the metadata field to an array.
			$registry = new Registry;
			$registry->loadString($item->metadata);
			$item->metadata = $registry->toArray();

			// Convert the images field to an array.
			$registry = new Registry;
			$registry->loadString($item->images);
			$item->images = $registry->toArray();

			if (!empty($item->id))
			{
				$item->tags = new JHelperTags;
				$item->tags->getTagIds($item->id, 'com_buttons.button');
				$item->metadata['tags'] = $item->tags;
			}
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable  $table  A reference to a JTable object.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	protected function prepareTable($table)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$date = JFactory::getDate();
		$user = JFactory::getUser();

		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$table->alias = JApplicationHelper::stringURLSafe($table->alias);

		if (empty($table->alias))
		{
			$table->alias = JApplicationHelper::stringURLSafe($table->title);
		}

		if (empty($table->id))
		{
			// Set the values

			// Set ordering to the last item if not set
			if (empty($table->ordering))
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true)
					->select('MAX(ordering)')
					->from($db->quoteName('#__buttons'));

				$db->setQuery($query);
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
			else
			{
				// Set the values
				$table->modified    = $date->toSql();
				$table->modified_by = $user->id;
			}
		}

		// Increment the button version number.
		$table->version++;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   JTable  $table  A JTable object.
	 *
	 * @return  array  An array of conditions to add to ordering queries.
	 *
	 * @since   3.4
	 */
	protected function getReorderConditions($table)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$condition = array();
		$condition[] = 'catid = ' . (int) $table->catid;

		return $condition;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since	3.4
	 */
	public function save($data)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$app = JFactory::getApplication();

		// Alter the title for save as copy
		if ($app->input->get('task') == 'save2copy')
		{
			list($name, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
			$data['title']	= $name;
			$data['alias']	= $alias;
			$data['state']	= 0;
		}

		return parent::save($data);
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $category_id  The id of the parent.
	 * @param   string   $alias        The alias.
	 * @param   string   $name         The title.
	 *
	 * @return  array  Contains the modified title and alias.
	 *
	 * @since   3.4
	 */
	protected function generateNewTitle($category_id, $alias, $name)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('alias' => $alias, 'catid' => $category_id)))
		{
			if ($name == $table->title)
			{
				$name = String::increment($name);
			}

			$alias = String::increment($alias, 'dash');
		}

		return array($name, $alias);
	}
}
