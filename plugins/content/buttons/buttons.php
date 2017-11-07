<?php
/**
 * @version		3.5.11 plugins/content/buttons/buttons.php
 *
 * @package		Buttons
 * @subpackage	plg_content_buttons
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

use Joomla\Registry\Registry;

if (file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
{
	require_once JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php';
	require_once JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/category.php';

	JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_buttons/models', 'ButtonsModel');
}

class plgContentButtons extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  3.3
	 */
	protected $db;

	/**
	 * A Registry object holding the global parameters for the plugin
	 *
	 * @var    Registry
	 * @since  3.5
	 */
	private $cparams = null;

	/**
	 * Constructor
	 *
	 * @param  object  $subject  The object to observe
	 * @param  array   $config   An array that holds the plugin configuration
	 *
	 * @since       2.5
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		if ($this->params->get('debug') || defined('JDEBUG') && JDEBUG)
		{
			JLog::addLogger(array('text_file' => $this->params->get('log', 'eshiol.php'), 'extension' => 'plg_content_buttons'), JLog::ALL, array('plg_content_buttons'));
		}
		JLog::addLogger(array('logger' => 'messagequeue', 'extension' => 'plg_content_buttons'), JLOG::ALL & ~JLOG::DEBUG, array('plg_content_buttons'));
		JLog::add(__METHOD__, JLOG::DEBUG, 'plg_content_buttons');

		//TODO: check com_buttons is installed and enabled
		$app = JFactory::getApplication();
		if ($app->getName() == 'administrator')
		{
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
			{
				JLog::add(JText::_('PLG_CONTENT_BUTTONS_MSG_REQUIREMENTS'),JLOG::WARNING,'plg_content_buttons');
			}
		}

		$this->cparams = JComponentHelper::getParams('com_buttons');
	}

	/**
	 * Plugin that prepare content for report
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property
	 * @param   mixed    $params   Additional parameters. See {@see PlgContentContent()}.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  boolean	True on success.
	 */
	public function onContentPrepare($context, &$row, $params, $page = 0)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_buttons'));

		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
			return;

		$allowed_contexts = array('com_content.article');
		if (!in_array($context, $allowed_contexts))
		{
			return true;
		}

		if (JFactory::getApplication()->input->getString('buttons') == 'report')
		{
			$row->text = $row->introtext;
			$row->params->set('access-edit',0);
		}
		return true;
	}


	/**
	 * Adds additional fields to the category editing form
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentPrepareForm($form, $data)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_buttons'));

		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
			return;

		$app = JFactory::getApplication();
		$formname = $form->getName();
		if ($formname == 'com_content.article')
		{
			JForm::addFormPath(__DIR__ . '/forms');
			//Show specific forms based on buttons
			$form->loadFile('article', false);
		}
		elseif ($app->isAdmin())
		{
			if ($formname == 'com_categories.categorycom_buttons')
			{
				JForm::addFormPath(__DIR__ . '/forms');
				//Show specific forms based on categories
				$form->loadFile('toolbar', false);
			}
			elseif ($formname == 'com_categories.categorycom_content')
			{
				JForm::addFormPath(__DIR__ . '/forms');
				//Show specific forms based on categories
				$form->loadFile('category', false);
			}
		}
		return true;
	}

	/**
	 * Displays the toolbar at the top of the article
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property
	 * @param   mixed    $params   Additional parameters. See {@see PlgContentContent()}.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  mixed  html string containing code for the buttons else boolean false
	 *
	 * @since   1.6
	 */
	public function onContentBeforeDisplay($context, &$row, &$params, $page=0)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_buttons'));
		JLog::add(new JLogEntry('context: '.$context, JLOG::DEBUG, 'plg_content_buttons'));

		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
			return;

		$allowed_contexts = array('com_content.article','com_content.featured','com_content.category');
		if (!in_array($context, $allowed_contexts))
		{
			return;
		}

		$html = '';
		if (JFactory::getApplication()->input->getString('buttons') != 'report')
		{
			JLog::add(new JLogEntry('processing article: '.$row->title, JLOG::DEBUG, 'plg_content_buttons'));
			$toolbars = ButtonsHelper::getToolbars($row, 'top');

			if (isset($row->asset_id))
			{
				$asset_id = $row->asset_id;
			}
			else
			{
				$article = JTable::getInstance('Content');
				$article->load($row->id);
				$asset_id = $article->asset_id;
			}

			$clearfix = '';
			foreach($toolbars as $catid)
			{
				JLog::add(new JLogEntry('toolbar: '.$catid, JLOG::DEBUG, 'plg_content_buttons'));

				$toolbar = JTable::getInstance('Category');
				$toolbar->load($catid);
				$tparams = new Registry;
				$tparams->loadString($toolbar->params);
				$cparams = clone($this->cparams);
				$cparams->merge($tparams);
				JLog::add(new JLogEntry('params: '.print_r($cparams, true), JLOG::DEBUG, 'plg_content_buttons'));

				if (($context == 'com_content.article')
					|| (($context == 'com_content.featured') && $cparams->get('show_featured'))
					|| (($context == 'com_content.category') && $cparams->get('show_categoryblog'))
				) {
					$html .= '<span id="buttons-top-'.$asset_id.'-'.$catid.'">';
					$style = ($cparams->get('report_style', 0) == 0 ? 'buttons' : 'text');
					$html .= ButtonsHelper::getToolbar($catid, $asset_id, JFactory::getUser()->id, true, $style);
					$html .= '</span>';
					$clearfix = '<div class="clearfix"></div>';
				}
			}
			$html .= $clearfix;
		}
		return $html;
	}


	/**
	 * Displays the toolbar at the bottom of the article
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property
	 * @param   mixed    $params   Additional parameters. See {@see PlgContentContent()}.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  mixed  html string containing code for the buttons else boolean false
	 *
	 * @since   1.6
	 */
	public function onContentAfterDisplay($context, &$row, &$params, $page=0)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_buttons'));

		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
		{
			return;
		}

		$allowed_contexts = array('com_content.article','com_content.featured','com_content.category');
		if (!in_array($context, $allowed_contexts))
		{
			return;
		}

		JLog::add(new JLogEntry('processing article: '.$row->title, JLOG::DEBUG, 'plg_content_buttons'));
		$html = '';
		if (JFactory::getApplication()->input->getString('buttons') != 'report')
		{
			$toolbars = ButtonsHelper::getToolbars($row, 'bottom');

			if (isset($row->asset_id))
			{
				$asset_id = $row->asset_id;
			}
			else
			{
				$article = JTable::getInstance('Content');
				$article->load($row->id);
				$asset_id = $article->asset_id;
			}

			$html = '';
			$clearfix = '';
			foreach($toolbars as $catid)
			{
				JLog::add(new JLogEntry('toolbar: '.$catid, JLOG::DEBUG, 'plg_content_buttons'));

				$toolbar = JTable::getInstance('Category');
				$toolbar->load($catid);
				$tparams = new Registry;
				$tparams->loadString($toolbar->params);
				$cparams = clone($this->cparams);
				$cparams->merge($tparams);
				JLog::add(new JLogEntry('params: '.print_r($cparams, true), JLOG::DEBUG, 'plg_content_buttons'));

				if (($context == 'com_content.article')
					|| (($context == 'com_content.featured') && $cparams->get('show_featured'))
					|| (($context == 'com_content.category') && $cparams->get('show_categoryblog'))
				) {
					$html .= '<span id="buttons-bottom-'.$asset_id.'-'.$catid.'">';
					$style = ($cparams->get('report_style', 0) == 0 ? 'buttons' : 'text');
					$html .= ButtonsHelper::getToolbar($catid, $asset_id, JFactory::getUser()->id, true, $style);
					$html .= '</span>';
					$clearfix = '<div class="clearfix"></div>';
				}
			}
			$html .= $clearfix;
		}
		else
		{
			$authorisedViewLevels = JFactory::getUser()->getAuthorisedViewLevels();
			$report_access = false;
			$toolbars = ButtonsHelper::getToolbars($row);
			foreach($toolbars as $i => $catid)
			{
				$toolbar = JTable::getInstance('Category');
				$toolbar->load($catid);
				$tparams = new Registry;
				$tparams->loadString($toolbar->params);

				if (in_array($tparams->get('report_access', 3), $authorisedViewLevels))
				{
					$report_access = true;
				}
				else
				{
					unset($toolbars[$i]);
				}
				/*
				 $report_access = $report_access ||
				 in_array($tparams->get('report_access', 3), $authorisedViewLevels);
				 */
			}
			if (!$report_access)
			{
				return;
			}
			if ($report_access)
			{
				$asset_id = $row->asset_id;
				// TODO remove code
				/*
				if (isset($row->asset_id))
				{
					$asset_id = $row->asset_id;
				}
				else
				{
					$article = JTable::getInstance('Content');
					$article->load($row->id);
					$asset_id = $article->asset_id;
				}
				*/

				if ($this->cparams->get('debug') || defined('JDEBUG') && JDEBUG)
				{
					JLog::addLogger(array('text_file' => $this->cparams->get('log', 'eshiol.php'), 'extension' => 'com_buttons'), JLog::ALL, array('com_buttons'));
				}
				JLog::addLogger(array('logger' => 'messagequeue', 'extension' => 'com_buttons'), JLOG::ALL & ~JLOG::DEBUG, array('com_buttons'));

				JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_buttons/models', 'ButtonsModel');

				/** @var ButtonsModelExtras $model */
				$model = JModelLegacy::getInstance('Extras', 'ButtonsModel', array('ignore_request' => true));

				$model->setState('filter.asset_id', $asset_id);
				if ($items = $model->getItems())
				{
					$style = (JFactory::getApplication()->input->getString('print') ? 'text' : 'buttons');
					$cparams = array();
					$authorisedViewLevels = JFactory::getUser()->getAuthorisedViewLevels();
					foreach ($items as $item)
					{
						JLog::add(new JLogEntry(print_r($item, true), JLOG::DEBUG, 'com_buttons'));

						$catid = $item->catid;
						if (!isset($cparams[$catid]))
						{
							$toolbar = JTable::getInstance('Category');
							$toolbar->load($catid);
							$tparams = new Registry;
							$tparams->loadString($toolbar->params);
							$cparams[$catid] = clone($params);
							$cparams[$catid]->merge($tparams);
							JLog::add(new JLogEntry('params: '.print_r($cparams[$catid], true), JLOG::DEBUG, 'com_buttons'));
						}

						$style = (JFactory::getApplication()->input->getString('print')
							? 'text'
								: $cparams[$catid]->get('report_style', 0)
								? 'text'
								: 'buttons'
							);

						if (in_array($cparams[$catid]->get('report_access', 3), $authorisedViewLevels))
						{
							$item->toolbar = ButtonsHelper::getToolbar($catid, $asset_id, $item->editor_user_id, false, $style);
						}
					}
					// Get the path for the layout file
					$path = JPluginHelper::getLayoutPath('content', 'buttons');
					// Render the toolbar
					ob_start();
					include $path;
					$html .= ob_get_clean();
				}
			}
		}
		return $html;
	}
}