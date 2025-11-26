<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CyberSalt\Component\Whmcsbridge\Administrator\View\Users\HtmlView $this */

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_whmcsbridge&view=users'); ?>" method="post" name="adminForm" id="adminForm">
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
                    <table class="table itemList" id="usersList">
                        <thead>
                            <tr>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_WHMCS_EMAIL', 'a.whmcs_email', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('COM_WHMCSBRIDGE_FIELD_WHMCS_NAME'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_JOOMLA_USER', 'u.username', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_WHMCS_STATUS', 'a.whmcs_status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_SYNC_STATUS', 'a.sync_status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_WHMCSBRIDGE_FIELD_LAST_SYNC', 'a.last_sync', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo Text::_('COM_WHMCSBRIDGE_ACTIONS'); ?>
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
                                        <?php echo $this->escape($item->whmcs_email); ?>
                                        <br><small class="text-muted">WHMCS ID: <?php echo (int) $item->whmcs_client_id; ?></small>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->whmcs_firstname . ' ' . $item->whmcs_lastname); ?>
                                    </td>
                                    <td>
                                        <?php if ($item->joomla_username) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . $item->joomla_user_id); ?>">
                                                <?php echo $this->escape($item->joomla_username); ?>
                                            </a>
                                            <?php if ($item->joomla_blocked) : ?>
                                                <span class="badge bg-danger"><?php echo Text::_('COM_WHMCSBRIDGE_BLOCKED'); ?></span>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="text-muted"><?php echo Text::_('COM_WHMCSBRIDGE_NO_JOOMLA_USER'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $item->whmcs_status === 'Active' ? 'success' : ($item->whmcs_status === 'Inactive' ? 'warning' : 'secondary'); ?>">
                                            <?php echo $this->escape($item->whmcs_status); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $item->sync_status === 'synced' ? 'success' : ($item->sync_status === 'failed' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($this->escape($item->sync_status)); ?>
                                        </span>
                                    </td>
                                    <td class="text-center small">
                                        <?php echo $item->last_sync ? HTMLHelper::_('date', $item->last_sync, 'M d, H:i') : '-'; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&task=sync.syncuser&whmcs_id=' . $item->whmcs_client_id . '&' . Session::getFormToken() . '=1'); ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="<?php echo Text::_('COM_WHMCSBRIDGE_SYNC_USER'); ?>">
                                            <span class="icon-refresh" aria-hidden="true"></span>
                                        </a>
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
