<?php
/**
 * @package		Buttons
 * @subpackage	com_buttons
 * @version		3.6.13
 * @since		3.4
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2015, 2018 Helios Ciancio. All Rights Reserved
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
 * Installation class to perform additional changes during install/uninstall/update
 */
class FirmacircolariInstallerScript
{
	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string      $action     Which action is happening (install|uninstall|discover_install|update)
	 * @param   JInstaller  $installer  The class calling this method
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.6.13
	 */
	public function preflight($action, $installer)
	{
		if (($action === 'install') || ($action === 'update'))
		{
			if (!JFolder::exists(JPATH_ROOT.'/images/buttons'))
			{
				JFolder::create(JPATH_ROOT.'/images/buttons');
			}
		}
	}

	/**
	 * Method to run after the install routine.
	 *
	 * @param   string                      $type    The action being performed
	 * @param   JInstallerAdapterComponent  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   3.4.1
	 */
	public function postflight($type, $parent)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_firmacircolari'));

		if ($type == 'install')
		{
			// Firma circolari
			$category = $this->addCategory('Firma circolari', 1, 2);
			$this->addButton('Firmato',	1, $category->id, 1, 1, 'sign', 64, 64);

			// Sciopero
			$params = new Registry;
			$params->set('category_layout','');
			$params->set('image','');
			$params->set('toolbar_type','1');
			$params->set('toolbar_final',0);
			$params->set('show_categoryblog','');
			$params->set('show_featured','');
			$category = $this->addCategory('Sciopero', 1, 2, $params->toString('JSON'));
			$this->addButton('Dichiaro di aderire', 1, $category->id, 1, 1, 'yes', 64, 64);
			$this->addButton('Dichiaro di non aderire', 2, $category->id, 1, 1, 'no', 64, 64);
			$this->addButton('Non in servizio', 3, $category->id, 1, 1, 'home', 64, 64);
			$this->addButton('Non dichiaro le mie intenzioni', 4, $category->id, 1, 1, 'maybe', 64, 64);

			// Assemblea
			$category = $this->addCategory('Assemblea', 1, 2, $params->toString('JSON'));
			$this->addButton('Dichiaro di aderire', 1, $category->id, 1, 1, 'yes', 64, 64);
			$this->addButton('Dichiaro di non aderire', 2, $category->id, 1, 1, 'no', 64, 64);
			$this->addButton('Non in servizio', 3, $category->id, 1, 1, 'home', 64, 64);

			// Votazione
			$category = $this->addCategory('Votazione', 1, 2, $params->toString('JSON'));
			$this->addButton('Dichiaro di aderire', 1, $category->id, 1, 1, 'yes', 64, 64);
			$this->addButton('Dichiaro di non aderire', 2, $category->id, 1, 1, 'no', 64, 64);
			$this->addButton('Astenuto', 3, $category->id, 1, 1, 'maybe', 64, 64);

			// Generico
			$category = $this->addCategory('Generico', 1, 2, $params->toString('JSON'));
			$this->addButton('Dichiaro di aderire/approvare', 1, $category->id, 1, 1, 'yes', 64, 64);
			$this->addButton('Dichiaro di non aderire/non approvare', 2, $category->id, 1, 1, 'no', 64, 64);
		}

		if (($type == 'install') || ($type == 'update'))
		{
			$this->addOverride('it-IT', 1, 'COM_BUTTONS_CONFIGURATION', 'Firma Circolari: Opzioni');
			$this->addOverride('it-IT', 1, 'COM_BUTTONS_MANAGER_BUTTONS', 'Firma Circolari');
			$this->addOverride('it-IT', 1, 'COM_BUTTONS_MENU', 'Firma Circolari');
			$this->addOverride('it-IT', 1, 'COM_BUTTONS_COMPONENT_LABEL', 'Firma Circolari');
			$this->addOverride('it-IT', 1, 'COM_BUTTONS_SUBMENU_EXTRAS', 'Report');
		}
	}

	/**
	 * Function to create a new category
	 *
	 * @param   string   $title   The title of the category.
	 * @param   integer  $state   The published state of the category. [optional]
	 * @param   integer  $access  The access level of the category. [optional]
	 *
	 * @return  JTableCategory	The category
	 *
	 * @since   3.4
	 */
	private function addCategory($title, $state=0, $access=2, $params='{"category_layout":"","image":""}')
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_firmacircolari'));

		// Initialize a new category
		/** @type  JTableCategory  $category  */
		$category = JTable::getInstance('Category');

		// Check if the category already exists before adding it
		if (!$category->load(array('extension' => 'com_buttons', 'title' => $title)))
		{
			$category->extension = 'com_buttons';
			$category->title = $title;
			$category->description = '';
			$category->published = $state;
			$category->access = $access;
			$category->params = $params;
			$category->metadata = '{"author":"","robots":""}';
			$category->language = '*';

			// Set the location in the tree
			$category->setLocation(1, 'last-child');

			// Check to make sure our data is valid
			if (!$category->check())
			{
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_BUTTONS_ERROR_INSTALL_CATEGORY', $category->getError()));

				return;
			}

			// Now store the category
			if (!$category->store(true))
			{
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_BUTTONS_ERROR_INSTALL_CATEGORY', $category->getError()));

				return;
			}

			// Build the path for our category
			$category->rebuildPath($category->id);
		}
		return $category;
	}

	/**
	 * Function to create a new button
	 *
	 * @param   string   $title   The title of the button.
	 * @param   integer  $value   The value of the button.
	 * @param   integer  $catid   The id of the category of the button.
	 * @param   integer  $state   The published state of the button. [optional]
	 * @param   integer  $access  The access level of the button. [optional]
	 *
	 * @return  JTableCategory	The category
	 *
	 * @since   3.4
	 */
	private function addButton($title, $value, $catid, $state=1, $access=1, $img, $height=32, $width=32)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_firmacircolari'));

		// Initialize a new Button
		/** @type  ButtonsTableButton  $button  */
		require_once JPATH_ADMINISTRATOR.'/components/com_buttons/tables/button.php';

		$button = JTable::getInstance('Button', 'ButtonsTable');
		if (!$button->load(array('alias' => strtolower($title), 'catid' => $catid)))
		{
			$button->title = $title;
			$button->alias = strtolower($title);
			$button->value = $value;
			$button->catid = $catid;
			$button->state = $state;
			$button->ordering = $value;
			$images = new JRegistry;
			$images->set('image', 'images/buttons/'.$img.'.png');
			$images->set('height', $height);
			$images->set('width', $width);
			$button->images = $images->toString('JSON');
			$button->language = '*';

			// Check to make sure our data is valid
			if (!$button->check())
			{
//				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_BUTTONS_ERROR_INSTALL_BUTTON', $button->getError()));

				return;
			}

			// Now store the button
			if (!$button->store(true))
			{
//				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_BUTTONS_ERROR_INSTALL_BUTTON', $button->getError()));

				return;
			}
		}
	}

	/**
	 * Function to create a new Override
	 *
	 * @param   string	$language  Language tag.
	 * @param   string	$client_id	Application client id.
	 * @param   string  $key  The key name.
	 * @param   string  $override  The override string.
	 *
	 * @since   3.6
	 */
	private function addOverride($language, $client, $key, $override)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'firmacircolari'));
		
		$app = JFactory::getApplication();
		$app->setUserState('com_languages.overrides.filter.client', $client);
		$app->setUserState('com_languages.overrides.filter.language', $language);

		JModelLegacy::addIncludePath(JPATH_ROOT . '/administrator/components/com_languages/models');
		$model = JModelLegacy::getInstance('Override', 'LanguagesModel', array('ignore_request' => true));
		return $model->save(array('id' => null, 'key' => $key, 'override' => $override));
	}
}
