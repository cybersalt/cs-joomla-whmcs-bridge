<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \CyberSalt\Component\Whmcsbridge\Administrator\View\Products\HtmlView $this */

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_whmcsbridge&view=products'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table itemList" id="productsList">
                        <thead>
                            <tr>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_PRODUCT_NAME', 'a.product_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_PRODUCT_GROUP', 'a.product_group', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('COM_WHMCSBRIDGE_FIELD_USER'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_DOMAIN', 'a.domain', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_STATUS', 'a.status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_NEXT_DUE', 'a.next_due_date', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo Text::_('COM_WHMCSBRIDGE_FIELD_AMOUNT'); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->product_name); ?>
                                        <br><small class="text-muted">WHMCS Service ID: <?php echo (int) $item->whmcs_service_id; ?></small>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->product_group); ?>
                                    </td>
                                    <td>
                                        <?php if ($item->whmcs_email) : ?>
                                            <?php echo $this->escape($item->whmcs_email); ?>
                                            <?php if ($item->joomla_username) : ?>
                                                <br><small class="text-muted"><?php echo $this->escape($item->joomla_username); ?></small>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $item->domain ? $this->escape($item->domain) : '-'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $statusClass = match($item->status) {
                                            'Active' => 'success',
                                            'Suspended' => 'warning',
                                            'Terminated', 'Cancelled' => 'danger',
                                            'Pending' => 'info',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo $this->escape($item->status); ?>
                                        </span>
                                    </td>
                                    <td class="text-center small">
                                        <?php echo $item->next_due_date ? HTMLHelper::_('date', $item->next_due_date, 'M d, Y') : '-'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->amount > 0) : ?>
                                            <?php echo number_format($item->amount, 2); ?> <?php echo $this->escape($item->currency_code); ?>
                                            <br><small class="text-muted"><?php echo $this->escape($item->billing_cycle); ?></small>
                                        <?php else : ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
