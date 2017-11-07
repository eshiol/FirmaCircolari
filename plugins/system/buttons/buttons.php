<?php
/**
 * @version		3.5.11 plugins/system/buttons/buttons.php
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
}

class plgSystemButtons extends JPlugin
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
			JLog::addLogger(array('text_file' => $this->params->get('log', 'eshiol.php'), 'extension' => 'plg_system_buttons'), JLog::ALL, array('plg_system_buttons'));
		}
		JLog::addLogger(array('logger' => 'messagequeue', 'extension' => 'plg_system_buttons'), JLOG::ALL & ~JLOG::DEBUG, array('plg_system_buttons'));
		JLog::add(__METHOD__, JLOG::DEBUG, 'plg_system_buttons');
		
		$app = JFactory::getApplication();
		if ($app->getName() == 'administrator') 
		{
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
			{
				JLog::add(JText::_('PLG_SYSTEM_BUTTONS_MSG_REQUIREMENTS'),JLOG::WARNING,'plg_system_buttons');
			}
		}
	}
	
	/**
	 * Method is called by index.php and administrator/index.php
	 *
	 * @access	public
	 */
	public function onAfterDispatch()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_system_buttons'));

		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
			return;
		
		$app 	= JFactory::getApplication();
		if ($app->getName() != 'site') { return true;}
		
		$format = $app->input->get('format', '', 'string');
		if ($format == 'feed') { return true;}
		
		$option = $app->input->get('option', '', 'string');
		if ($option != 'com_content') { return true;}
		
		$view = $app->input->get('view', '', 'string');
		if ($view != 'article') { return true;}
		
		$buttons = $app->input->get('buttons', '', 'string');
		if ($buttons != 'report') { return true;}
		
		$authorisedViewLevels = JFactory::getUser()->getAuthorisedViewLevels();
		$report_access = false;
		$id = $app->input->get('id', '', 'string');
		$row = JTable::getInstance('Content');
		$row->load($id);
		$toolbars = ButtonsHelper::getToolbars($row);
		foreach($toolbars as $catid)
		{
			$toolbar = JTable::getInstance('Category');
			$toolbar->load($catid);
			$tparams = new Registry;
			$tparams->loadString($toolbar->params);
			$report_access = $report_access ||
			in_array($tparams->get('report_access', 3), $authorisedViewLevels);
		}
		if (!$report_access)
		{
			$app->redirect($this->urlRemoveVar(rawurldecode(JUri::getInstance()->toString(array('scheme', 'host', 'port', 'path', 'query', 'fragment'))), 'buttons'));	
		}
	}
	
	
	/**
	 * Add report icon
	 *
	 * @return  void
	 */
	function onAfterRender() 
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_system_buttons'));
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_buttons/helpers/buttons.php'))
			return;
		
		$app 	= JFactory::getApplication();
		if ($app->getName() != 'site') { return true;}
		
		$format = $app->input->get('format', '', 'string');
		if ($format == 'feed') { return true;}
		
		$option = $app->input->get('option', '', 'string');
		if ($option != 'com_content') { return true;}
		
		$view = $app->input->get('view', '', 'string');
		if ($view != 'article') { return true;}
		
		$id = $app->input->get('id', '', 'string');
		$item = new StdClass();
		if ((int)$id > 0) 
		{
			$query = $this->db->getQuery(true)
				->select($this->db->qn(array('a.id','a.alias','a.attribs','a.catid','a.language')))
				->from($this->db->qn('#__content').' a')
				// Join over the categories
				->select($this->db->qn('c.alias').' as '.$this->db->qn('category_alias'))
				->join('LEFT', $this->db->qn('#__categories').' AS '.$this->db->qn('c').' ON '.$this->db->qn('c.id').'='.$this->db->qn('a.catid'))
				// Filter by content
				->where($this->db->qn('a.id').'='.(int)$id)
				;
			$item = $this->db->setQuery($query)->loadObject();
			
			if (JPluginHelper::isEnabled('content', 'buttons')) 
			{
				if (!empty($item)) 
				{
					$authorisedViewLevels = JFactory::getUser()->getAuthorisedViewLevels();
					$report_access = false;
					$toolbars = ButtonsHelper::getToolbars($item);
					foreach($toolbars as $catid)
					{
						$toolbar = JTable::getInstance('Category');
						$toolbar->load($catid);
						$tparams = new Registry;
						$tparams->loadString($toolbar->params);
						$report_access = $report_access ||
						in_array($tparams->get('report_access', 3), $authorisedViewLevels);
					}
					$params = new JRegistry();
					$params->loadString($item->attribs);
					
					$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
					$item->catslug = $item->category_alias ? ($item->catid . ':' . $item->category_alias) : $item->catid;
	
					$buffer = JResponse::getBody();
					
					$template = $this->params->get('template', 'TEXT');
					//$legacy = $this->params->get('legacy', 0);
					$buffer = JResponse::getBody();
					if (JFactory::getApplication()->input->getString('buttons') != 'report')
					{
						if (!$report_access)
						{
							return;
						}
						$html = '<li class="print-icon">';
						
						$app = JFactory::getApplication();
						$input = $app->input;
						$request = $input->request;
						
						$url  = ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language);
						$url .= '&buttons=report';

						if ($template == 'LEGACY')
							$text = '<span class="icon-chart"></span>&nbsp;<span class="hidden">'.JText::_('PLG_SYSTEM_BUTTONS_REPORT').'</span>';
						elseif ($template == 'DEFAULT')
							$text = '<span class="hasTooltip icon-chart tip"></span>'.JText::_('PLG_SYSTEM_BUTTONS_REPORT');
						else 
							$text = JText::_('PLG_SYSTEM_BUTTONS_REPORT');
						// $text = JText::_('PLG_SYSTEM_BUTTONS_REPORT_TEXT_'.$template);
						/**
						// Checks template image directory for image, if non found default are loaded
						if ($params->get('show_icons', JComponentHelper::getParams('com_content')->get('show_icons')))
						{
							if ($legacy)
							{
								$text = JHtml::_('image', 'com_buttons/report.png', JText::_('PLG_SYSTEM_BUTTONS_REPORT'), null, true);
							}
							else
							{
								$text = '<span class="hasTooltip icon-chart tip"></span>'.JText::_('PLG_SYSTEM_BUTTONS_REPORT');
							}
						}
						else
						{
							$text = JText::_('PLG_SYSTEM_BUTTONS_REPORT');
						}
						*/
						
						$attribs['title']   = JText::_('PLG_SYSTEM_BUTTONS_REPORT');
						$attribs['rel']     = 'nofollow';
						
						$html .= JHtml::_('link', JRoute::_($url), $text, $attribs);
						$html .= '</li>';
						
						if ($template == 'LEGACY')
						{
							$pattern = '/<ul class="actions">(.*?)<\/ul>/s';
							$replacement = '<ul class="actions">$1'.$html.'</ul>';
						}
						elseif ($template == 'DEFAULT')
						{
							$pattern = '/<ul class="dropdown-menu(.*?)">(.*?)<\/ul>/s';
							$replacement = '<ul class="dropdown-menu$1">$2'.$html.'</ul>';
						}
						else 
						{
							$pattern = '/<ul class="dropdown-menu(.*?)">(.*?)<\/ul>/s';
							$replacement = '<ul class="dropdown-menu$1">$2'.$html.'</ul>';
						}
						// $pattern = JText::_('PLG_SYSTEM_BUTTONS_REPORT_PATTERN_'.$template);
						// $replacement = JText::sprintf('PLG_SYSTEM_BUTTONS_REPORT_REPLACEMENT_'.$template, $html);
						/*
						if ($legacy)
						{
							$pattern = '/<ul class="actions">(.*?)<\/ul>/s';
							$replacement = '<ul class="actions">$1'.$html.'</ul>';
						}
						else
						{
							$pattern = '/<ul class="dropdown-menu(.*?)">(.*?)<\/ul>/s';
							$replacement = '<ul class="dropdown-menu$1">$2'.$html.'</ul>';
						}
						*/
						$buffer = preg_replace($pattern, $replacement, $buffer);

					}
					else 
					{
						if ($template == 'LEGACY')
						{
							$pattern = '/<ul class="actions.*?">.*?<li class="print-icon">.*?href="(.*?)".*?<\/li>.*?<\/ul>/s';
						}
						elseif ($template == 'DEFAULT')
						{
							$pattern = '/<ul class="dropdown-menu.*?">.*?<li class="print-icon">.*?href="(.*?)".*?<\/li>.*?<\/ul>/s';
						}
						else 
						{
							$pattern = '/<ul class="dropdown-menu.*?">.*?<li class="print-icon">.*?href="(.*?)".*?<\/li>.*?<\/ul>/s';
						}
						// $pattern = JText::_('PLG_SYSTEM_BUTTONS_EXIT_PATTERN_'.$template);
						/*
						if ($legacy)
						{
							$pattern = '/<ul class="actions.*?">.*?<li class="print-icon">.*?href="(.*?)".*?<\/li>.*?<\/ul>/s';
						}
						else 
						{
							$pattern = '/<ul class="dropdown-menu.*?">.*?<li class="print-icon">.*?href="(.*?)".*?<\/li>.*?<\/ul>/s';
						}
						*/
						preg_match($pattern, $buffer, $matches);
						if ($matches)
						{
							$html = '<li class="csv-icon">';
							$url = 'index.php?option=com_buttons&view=extras&id='.JFactory::getApplication()->input->get('id').'&buttons=report&format=csv';
							if ($template == 'LEGACY')
								$text = '<span class="icon-download"></span>&nbsp;<span class="hidden">'.JText::_('PLG_SYSTEM_BUTTONS_CSV').'</span>';
							elseif ($template == 'DEFAULT')
								$text = '<span class="hasTooltip icon-download tip"></span>'.JText::_('PLG_SYSTEM_BUTTONS_CSV');
							else
								$text = JText::_('PLG_SYSTEM_BUTTONS_CSV');
				
							$attribs = array('rel' => 'nofollow', 'title'=> JText::_('PLG_SYSTEM_BUTTONS_CSV'));
								
							$html .= JHtml::_('link', JRoute::_($url), $text, $attribs);
							$html .= '</li>';

							$html .= '<li class="exit-icon">';
							$url = $this->urlRemoveVar(rawurldecode(JUri::getInstance()->toString(array('scheme', 'host', 'port', 'path', 'query', 'fragment'))), 'buttons');
							if ($template == 'LEGACY')
								$text = '<span class="icon-exit"></span>&nbsp;<span class="hidden">'.JText::_('PLG_SYSTEM_BUTTONS_CLOSE').'</span>';
							elseif ($template == 'DEFAULT')
								$text = '<span class="hasTooltip icon-cancel tip"></span>'.JText::_('PLG_SYSTEM_BUTTONS_CLOSE');
							else
								$text = JText::_('PLG_SYSTEM_BUTTONS_CLOSE');
							
							$attribs = array('rel' => 'nofollow', 'title'=> JText::_('PLG_SYSTEM_BUTTONS_CLOSE'));
										
							$html .= JHtml::_('link', JRoute::_($url), $text, $attribs);
							$html .= '</li>';
										
							$replacement = str_replace($matches[1], $matches[1].'&buttons=report', $matches[0]);
							$replacement = str_replace('</ul>', $html.'</ul>', $replacement); 
							$buffer = preg_replace($pattern, $replacement, $buffer);
						}
					}
					JResponse::setBody($buffer);
				}
			}
		}
		return true;
	}
	
	static function urlRemoveVar($pageURL, $key)
	{
		JLog::add(__METHOD__, JLOG::DEBUG, 'plg_system_buttons');
		
		$url = parse_url($pageURL);
		if (!isset($url['query'])) return $pageURL;
		parse_str($url['query'], $query_data);
		if (!isset($query_data[$key])) return $pageURL;
		unset($query_data[$key]);
		$url['query'] = http_build_query($query_data);
		$scheme   = isset($url['scheme']) ? $url['scheme'] . '://' : '';
		$host     = isset($url['host']) ? $url['host'] : '';
		$port     = isset($url['port']) ? ':' . $url['port'] : '';
		$user     = isset($url['user']) ? $url['user'] : '';
		$pass     = isset($url['pass']) ? ':' . $url['pass']  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($url['path']) ? $url['path'] : '';
		$query    = isset($url['query']) && ($url['query']!='') ? '?' . $url['query'] : '';
		$fragment = isset($url['fragment']) ? '#' . $url['fragment'] : '';
		return "$scheme$user$pass$host$port$path$query$fragment";
	}
}
?>