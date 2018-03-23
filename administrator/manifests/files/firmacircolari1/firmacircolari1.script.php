<?php
/**
 * @package		Buttons
 * @subpackage	firmacircolari1
 * @version		3.6.12
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
class Firmacircolari1InstallerScript
{
	/**
	 * Called after any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, JAdapterInstance $adapter)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'firmacircolari1'));

		if (($route == 'install') || ($route == 'update'))
		{
			$this->addOverride('en-GB', 1, 'COM_CONTENT_CATEGORY_VIEW_DEFAULT_DESC', 'Displays a list of articles with Firma Circolari details in a category.');
			$this->addOverride('en-GB', 1, 'COM_CONTENT_CATEGORY_VIEW_DEFAULT_OPTION', 'List');
			$this->addOverride('en-GB', 1, 'COM_CONTENT_CATEGORY_VIEW_DEFAULT_TITLE', 'Category List (Firma Circolari)');

			$this->addOverride('it-IT', 1, 'COM_CONTENT_CATEGORY_VIEW_FIRMACIRCOLARI_DESC', 'Visualizza la lista degli articoli con dettaglio Firma Circolari appartenenti ad una categoria.');
			$this->addOverride('it-IT', 1, 'COM_CONTENT_CATEGORY_VIEW_FIRMACIRCOLARI_OPTION', 'Lista');
			$this->addOverride('it-IT', 1, 'COM_CONTENT_CATEGORY_VIEW_FIRMACIRCOLARI_TITLE', 'Lista di singola categoria (Firma Circolari)');
			
			// PKG_FIRMACIRCOLARI1_BUTTONS_STYLE_DESC
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
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'firmacircolari1'));

		$app = JFactory::getApplication();
		$app->setUserState('com_languages.overrides.filter.client', $client);
		$app->setUserState('com_languages.overrides.filter.language', $language);

		JModelLegacy::addIncludePath(JPATH_ROOT . '/administrator/components/com_languages/models');
		$model = JModelLegacy::getInstance('Override', 'LanguagesModel', array('ignore_request' => true));
		return $model->save(array('id' => null, 'key' => $key, 'override' => $override));
	}
}
