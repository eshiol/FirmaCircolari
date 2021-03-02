<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\Registry\Registry;

			if (isset($displayData['item']->asset_id))
			{
				$asset_id = $displayData['item']->asset_id;
			}
			else
			{
				$article = JTable::getInstance('Content');
				$article->load($displayData['item']->id);
				$asset_id = $article->asset_id;
			}
			$catid = $displayData['item']->catid;
					
			$toolbars = ButtonsHelper::getToolbars($displayData['item'], 'both');
			foreach($toolbars as $catid)
			{
				$toolbar = JTable::getInstance('Category');
				$toolbar->load($catid);
				$tparams = new Registry;
				$tparams->loadString($toolbar->params);
				$cparams = clone($displayData['item']->params);
				$cparams->merge($tparams);
				echo '<dd class="buttons">';				
				echo ButtonsHelper::getToolbar($catid, $asset_id, JFactory::getUser()->id, false, 'text');
				echo '</dd>';
			}
?>