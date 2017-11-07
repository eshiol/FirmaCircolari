<?php
/**
 * @version		3.5.11 components/com_buttons/views/extras/view.csv.php
 *
 * @package		Buttons
 * @subpackage	com_buttons
 * @since		3.4.8
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

/**
 * HTML View class for the buttons component
 */
class ButtonsViewExtras extends JViewLegacy
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

		$params = JComponentHelper::getParams('com_buttons');

		$this->state = $this->get('State');
		$this->state->set('list.limit', 0);
		$id = JFactory::getApplication()->input->getInt('id', 0);
		$model = $this->getModel('Extras', 'ButtonsModel', array('ignore_request' => true));
		$article = JTable::getInstance('Content');
		$article->load($id);
		$asset_id = $article->asset_id;
		$model->setState('filter.asset_id', $asset_id);
		$this->items = $model->getItems();

		$document = JFactory::getDocument();
		$document->setMimeEncoding('text/csv', true);
		JResponse::setHeader('Content-disposition', 'attachment; filename="'.$article->alias.'.csv"', true);
		// print header
		echo JText::_('COM_BUTTONS_CSV_HEADER_NAME').';';
		echo JText::_('COM_BUTTONS_CSV_HEADER_GROUPS').';';
		echo JText::_('COM_BUTTONS_CSV_HEADER_DATE').';';
		echo JText::_('COM_BUTTONS_CSV_HEADER_TOOLBAR').';';
		echo JText::_('COM_BUTTONS_CSV_HEADER_VALUE').';';
		echo "\n";

		$db = JFactory::getDbo();

		$cparams = array();
		$authorisedViewLevels = JFactory::getUser()->getAuthorisedViewLevels();
		foreach ($this->items as $item)
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

			if (in_array($cparams[$catid]->get('report_access', 3), $authorisedViewLevels))
			{
				$str = $item->editor_name.';'.
					implode(
						$db->setQuery(
						$db->getQuery(true)
							->select($db->qn('title'))
							->from($db->qn('#__usergroups'))
							->where($db->qn('id').' IN (' . implode(JAccess::getGroupsByUser($item->editor_user_id, false), ', ').')')
							)->loadColumn(0)
						, ',').';'.
					$item->modified.';'.
					$item->category_title.';'.
					ButtonsHelper::getToolbar($catid, $asset_id, $item->editor_user_id, false, 'csv').';'
					;
				JLog::add(new JLogEntry($str, JLOG::DEBUG, 'com_buttons'));
				echo $str."\n";
			}
		}
	}
}