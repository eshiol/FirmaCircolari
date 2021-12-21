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
 * Installation class to perform additional changes during install/uninstall/update
 */
class Com_ButtonsInstallerScript
{
	/**
	 * Function to perform changes during install
	 *
	 * @param   JInstallerAdapterComponent  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public function install($parent)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Enable plugins
		$db  = JFactory::getDbo();

		$query = $db->getQuery(true)
			->update('#__extensions')
			->set($db->qn('enabled') . ' = 1')
			->where($db->qn('type') . ' = ' . $db->quote('plugin'))
			->where($db->qn('folder') . ' = ' . $db->quote('content'))
			->where($db->qn('element') . ' = ' . $db->quote('buttons'))
			;
		$db->setQuery($query);
		$db->execute();

		$query = $db->getQuery(true)
			->update('#__extensions')
			->set($db->qn('enabled') . ' = 1')
			->where($db->qn('type') . ' = ' . $db->quote('plugin'))
			->where($db->qn('folder') . ' = ' . $db->quote('system'))
			->where($db->qn('element') . ' = ' . $db->quote('buttons'))
			;
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$db = JFactory::getDbo();

		if (!$db->setQuery(
				$db->getQuery(true)
					->select('count(*)')
					->from('#__categories')
					->where('extension='.$db->q('com_buttons'))
				)->LoadResult()
			&&
			!$db->setQuery(
				$db->getQuery(true)
					->select('count(*)')
					->from('#__buttons')
				)->LoadResult()
			&&
			!$db->setQuery(
				$db->getQuery(true)
					->select('count(*)')
					->from('#__buttons_extras')
				)->LoadResult()
			)
		{
			$db->setQuery("DELETE FROM `#__content_types` WHERE `type_alias` IN ('com_buttons.button', 'com_buttons.category');")->execute();
			$db->setQuery("DROP TABLE IF EXISTS `#__buttons`;")->execute();
			$db->setQuery("DROP TABLE IF EXISTS `#__buttons_extras`;")->execute();
		}
		else
		{
			// Preserve categories for this component bugs #11490
			$db->setQuery(
				$db->getQuery(true)
					->update('#__categories')
					->set('extension=CONCAT('.$db->q('!').',extension,'.$db->q('!').')')
					->where('extension='.$db->q('com_buttons'))
			)->execute();
		}

		// Disable plugins
		$db->setQuery(
			$db->getQuery(true)
				->update('#__extensions')
				->set($db->qn('enabled') . ' = 0')
				->where($db->qn('type') . ' = ' . $db->quote('plugin'))
				->where($db->qn('folder') . ' = ' . $db->quote('content'))
				->where($db->qn('element') . ' = ' . $db->quote('buttons'))
		)->execute();

		$db->setQuery(
			$db->getQuery(true)
				->update('#__extensions')
				->set($db->qn('enabled') . ' = 0')
				->where($db->qn('type') . ' = ' . $db->quote('plugin'))
				->where($db->qn('folder') . ' = ' . $db->quote('system'))
				->where($db->qn('element') . ' = ' . $db->quote('buttons'))
		)->execute();
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
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Initialize a new category
		/** @type  JTableCategory  $category  */
		$category = JTable::getInstance('Category');

		// Check if the Uncategorised category exists before adding it
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
	 * method to run before an install/update/uninstall method
	 *
	 *
	 * @param   string                      $type    The action being performed (install, update or discover_install)
	 * @param   JInstallerAdapterComponent  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   3.5.11
	 */
	function preflight($type, $parent)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		if ($type == 'install')
		{
			$db = JFactory::getDbo();

			// Recovery categories for this component bugs #11490
			$db->setQuery(
				$db->getQuery(true)
					->update('#__categories')
					->set('extension=SUBSTR(extension, 2, CHAR_LENGTH(extension) -2)')
					->where('extension='.$db->q('!com_buttons!'))
			)->execute();
		}
		/*
		elseif ($type == 'update')
		{
			foreach(array(
				'en-GB/en-GB.plg_content_buttons.ini',
				'en-GB/en-GB.plg_content_buttons.sys.ini',
				'it-IT/it-IT.plg_content_buttons.ini',
				'it-IT/it-IT.plg_content_buttons.sys.ini',
				'en-GB/en-GB.plg_system_buttons.ini',
				'en-GB/en-GB.plg_system_buttons.sys.ini',
				'it-IT/it-IT.plg_system_buttons.ini',
				'it-IT/it-IT.plg_system_buttons.sys.ini',
			) as $file)
			{
				JFile::delete(JPATH_ROOT . '/administrator/language/'.$file);
			}
		}
		*/
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
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Only execute database changes on MySQL databases
		$dbName = JFactory::getDbo()->name;

		if (strpos($dbName, 'mysql') !== false)
		{
			// Add Missing Table Colums if needed
			$this->addColumnsIfNeeded();
		}

		if (($type == 'install') or ($type == 'update'))
		{
			$category = $this->addCategory('Uncategorised', 1, 2);
		}

		if ($type == 'install')
		{
			// Copy images
			JFolder::copy(__DIR__.'/admin/images', JPATH_ROOT.'/images/buttons', '', true);

			$this->addButton('Demo',	1, $category->id, 1, 1, 'demo', 64, 64);
		}
	}

	/**
	 * Method to add colums from #__buttons_extra if they are missing.
	 *
	 * @return  void
	 *
	 * @since   3.4.1
	 */
	private function addColumnsIfNeeded()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		$db    = JFactory::getDbo();
		$table = $db->getTableColumns('#__buttons_extras');

		if (!array_key_exists('modified', $table))
		{
			$sql = 'ALTER TABLE ' . $db->qn('#__buttons_extras') . ' ADD COLUMN ' . $db->qn('modified') . "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'";
			$db->setQuery($sql);
			$db->execute();
		}
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
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'com_buttons'));

		// Register the class aliases for Framework classes that have replaced their Platform equivilents
		require_once JPATH_ADMINISTRATOR . '/components/com_buttons/tables/button.php';
		// Initialize a new Button
		/** @type  ButtonsTableButton  $button  */
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
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_BUTTONS_ERROR_INSTALL_BUTTON', $button->getError()));

				return;
			}

			// Now store the button
			if (!$button->store(true))
			{
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_BUTTONS_ERROR_INSTALL_BUTTON', $button->getError()));

				return;
			}
		}
	}
}
