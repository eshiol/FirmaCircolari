<?php
/**
 * @version		3.5.11 administrator/components/com_buttons/forms/fields/button.php
 *
 * @package		Buttons
 * @subpackage	plg_content_buttons
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

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Framework.
 */
class JFormFieldButton extends JFormFieldList
{
	/**
	 * A flexible button list that respects access controls
	 *
	 * @var    string
	 *
	 * @since  3.4
	 */
	public $type = 'Button';

	/**
	 * com_buttons parameters
	 *
	 * @var    \Joomla\Registry\Registry
	 *
	 * @since  3.4
	 */
	protected $comParams = null;

	/**
	 * Constructor
	 *
	 * @since  3.4
	 */
	public function __construct()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		parent::__construct();

		// Load com_buttons config
		$this->comParams = JComponentHelper::getParams('com_buttons');
	}

	/**
	 * Method to get the field input for a button field.
	 *
	 * @return  string  The field input.
	 *
	 * @since   3.4
	 */
	protected function getInput()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Get the field id
		$id    = isset($this->element['id']) ? $this->element['id'] : null;
		$cssId = '#' . $this->getId($id, $this->element['name']);

		// Load the ajax-chosen customised field
		JHtml::_('tag.ajaxfield', $cssId, false);

		if (!is_array($this->value) && !empty($this->value))
		{
			// String in format 2,5,4
			if (is_string($this->value))
			{
				$this->value = explode(',', $this->value);
			}
		}

		$input = parent::getInput();

		return $input;
	}

	/**
	 * Method to get a list of buttons
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.4
	 */
	protected function getOptions()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$published = $this->element['published']? $this->element['published'] : array(0,1);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('DISTINCT a.id AS value, a.alias AS path, a.title AS text, a.state AS published')
			->from('#__buttons AS a');

		// Filter language
		if (!empty($this->element['language']))
		{
			$query->where('a.language = ' . $db->q($this->element['language']));
		}

		// Filter on the published state
		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif (is_array($published))
		{
			JArrayHelper::toInteger($published);
			$query->where('a.state IN (' . implode(',', $published) . ')');
		}

		$query->order('a.ordering ASC');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		// Block the possibility to set a button as it own parent
		if ($this->form->getName() == 'com_buttons.button')
		{
			$id   = (int) $this->form->getValue('id', 0);

			foreach ($options as $option)
			{
				if ($option->value == $id)
				{
					$option->disable = true;
				}
			}
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
