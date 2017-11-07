<?php
/**
 * @version		3.5.11 administrator/components/com_buttons/views/extras/tmpl/default.php
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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/buttons.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/category.php';

use Joomla\Registry\Registry;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user		= JFactory::getUser();
$userId		= $user->get('id');
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$canOrder	= $user->authorise('core.edit.state', 'com_buttons.category');
$trashed   = $this->state->get('filter.state') == -2 ? true : false;
$sortFields = $this->getSortFields();

JFactory::getDocument()->addScriptDeclaration('
	Joomla.orderTable = function()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != "' . $listOrder . '")
		{
			dirn = "asc";
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, "");
	};
');
?>
<form action="<?php echo JRoute::_('index.php?option=com_buttons&view=extras'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search" class="element-invisible"><?php echo JText::_('COM_BUTTONS_SEARCH_IN_TITLE');?></label>
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" class="hasTooltip" title="<?php echo JHtml::tooltipText('COM_BUTTONS_SEARCH_IN_TITLE'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button type="button" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC');?></label>
				<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC');?></option>
					<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING');?></option>
					<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING');?></option>
				</select>
			</div>
			<div class="btn-group pull-right">
				<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY');?></label>
				<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
					<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder);?>
				</select>
			</div>
		</div>
		<div class="clearfix"> </div>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped" id="buttonList">
				<thead>
					<tr>
						<th width="1%" class="hidden-phone center">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="1%" style="min-width:55px" class="hidden-phone nowrap center">
							<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'b.title', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo JHtml::_('grid.sort', 'JAUTHOR', 'editor_name', $listDirn, $listOrder); ?>
						</th>
						<th class="title">
							<?php echo JHtml::_('grid.sort', 'JCATEGORY', 'category_title', $listDirn, $listOrder); ?>
						</th>
						<th class="center" style="min-width:300px;">
							<?php 
							echo JText::_('COM_BUTTONS_HEADING_VALUE');
							//echo JHtml::_('grid.sort', 'COM_BUTTONS_HEADING_VALUE', 'a.value', $listDirn, $listOrder); 
							?>
						</th>
						<th width="1%" class="nowrap center hidden-phone">
							<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="7">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
					$item->cat_link	= JRoute::_('index.php?option=com_categories&extension=com_buttons&task=edit&type=other&cid[]='. $item->catid);
					$canCreate  = $user->authorise('core.create',     'com_buttons.category.' . $item->catid);
					$canEdit    = $user->authorise('core.edit',       'com_buttons.category.' . $item->catid);
					$canChange  = $user->authorise('core.edit.state', 'com_buttons.category.' . $item->catid);
					?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid?>">
						<td class="center hidden-phone">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center">
							<div class="btn-group">
								<?php echo JHtml::_('jgrid.published', $item->state, $i, 'extras.', $canChange, 'cb'); ?>
								<?php 
								// Create dropdown items
								if ($item->state == 2)
									JHtml::_('actionsdropdown.publish', 'cb' . $i, 'buttons');
								else
									JHtml::_('actionsdropdown.archive', 'cb' . $i, 'buttons');
								if ($item->state != -2)
									JHtml::_('actionsdropdown.trash', 'cb' . $i, 'buttons');
								elseif ($trashed)
									JHtmlActionsDropdown::addCustomItem('delete', 'delete', 'cb' . $i, 'extras.delete');
								else
									JHtml::_('actionsdropdown.unpublish', 'cb' . $i, 'buttons');
	
								// Render dropdown list
								echo JHtml::_('actionsdropdown.render', $this->escape($item->title));
								?>
							</div>								
						</td>						
						<td class="nowrap has-context">
							<?php echo $this->escape($item->title); ?>
							<div class="small break-word">
								<?php 
								$i = strrpos($item->alias, '.');
								$type_alias = substr($item->alias, 0, $i);
								$id = substr($item->alias, $i+1);
								$ucmType = new JUcmType;
								$type = $ucmType->getTypeByAlias($type_alias);
								$registry = new Registry;
								$registry->loadString($type->table);
								$contentType = $registry->get('special')->type;
								$table = JTable::getInstance($contentType);
								$table->load($id);
								$item->alias = $table->alias;								
								echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
							</div>							
						</td>
						<td class="small">
							<a class="hasTooltip" href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->editor_user_id); ?>" title="<?php echo JText::_('JAUTHOR'); ?>">
							<?php echo $this->escape($item->editor_name); ?></a>
						</td>
						<td class="small">
							<?php echo $this->escape($item->category_title); ?>
						</td>						
						<td>
							<?php echo ButtonsHelper::getToolbar($item->catid, $item->asset_id, $item->editor_user_id, false, 'buttons'); ?>
						</td>
						<td class="center hidden-phone">
							<?php echo (int) $item->id; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
<?php echo ButtonsHelper::copyright(); ?>