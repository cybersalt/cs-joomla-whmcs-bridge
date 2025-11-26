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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CyberSalt\Component\Whmcsbridge\Administrator\View\Dashboard\HtmlView $this */
?>

<div class="row">
    <?php if (!$this->apiConfigured) : ?>
        <div class="col-12">
            <div class="alert alert-warning">
                <h4 class="alert-heading"><?php echo Text::_('COM_WHMCSBRIDGE_API_NOT_CONFIGURED'); ?></h4>
                <p><?php echo Text::_('COM_WHMCSBRIDGE_API_NOT_CONFIGURED_DESC'); ?></p>
                <a href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_whmcsbridge'); ?>" class="btn btn-primary">
                    <?php echo Text::_('COM_WHMCSBRIDGE_CONFIGURE_API'); ?>
                </a>
            </div>
        </div>
    <?php else : ?>
        <div class="col-12">
            <div class="alert <?php echo $this->apiStatus['connected'] ? 'alert-success' : 'alert-danger'; ?>">
                <strong><?php echo Text::_('COM_WHMCSBRIDGE_API_STATUS'); ?>:</strong>
                <?php echo $this->apiStatus['connected'] ? Text::_('COM_WHMCSBRIDGE_API_CONNECTED') : Text::sprintf('COM_WHMCSBRIDGE_API_DISCONNECTED', $this->apiStatus['error']); ?>
                <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&task=dashboard.viewLog'); ?>" class="btn btn-sm btn-outline-secondary ms-3">
                    <span class="icon-file-alt" aria-hidden="true"></span>
                    <?php echo Text::_('COM_WHMCSBRIDGE_VIEW_API_LOG'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-lg-3 col-md-6">
        <div class="card mb-3">
            <div class="card-body text-center">
                <h2 class="display-4"><?php echo $this->statistics->syncedUsers; ?></h2>
                <p class="text-muted mb-0"><?php echo Text::_('COM_WHMCSBRIDGE_SYNCED_USERS'); ?></p>
            </div>
            <div class="card-footer">
                <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&view=users'); ?>" class="btn btn-sm btn-outline-primary w-100">
                    <?php echo Text::_('COM_WHMCSBRIDGE_VIEW_USERS'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card mb-3">
            <div class="card-body text-center">
                <h2 class="display-4"><?php echo $this->statistics->activeProducts; ?></h2>
                <p class="text-muted mb-0"><?php echo Text::_('COM_WHMCSBRIDGE_ACTIVE_PRODUCTS'); ?></p>
                <small class="text-muted"><?php echo Text::sprintf('COM_WHMCSBRIDGE_OF_TOTAL', $this->statistics->totalProducts); ?></small>
            </div>
            <div class="card-footer">
                <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&view=products'); ?>" class="btn btn-sm btn-outline-primary w-100">
                    <?php echo Text::_('COM_WHMCSBRIDGE_VIEW_PRODUCTS'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card mb-3">
            <div class="card-body text-center">
                <h2 class="display-4"><?php echo $this->statistics->groupMappings; ?></h2>
                <p class="text-muted mb-0"><?php echo Text::_('COM_WHMCSBRIDGE_GROUP_MAPPINGS'); ?></p>
            </div>
            <div class="card-footer">
                <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&view=groupmaps'); ?>" class="btn btn-sm btn-outline-primary w-100">
                    <?php echo Text::_('COM_WHMCSBRIDGE_VIEW_MAPPINGS'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card mb-3">
            <div class="card-body text-center">
                <?php if ($this->statistics->lastSync) : ?>
                    <h5><?php echo HTMLHelper::_('date', $this->statistics->lastSync->started, 'M d, H:i'); ?></h5>
                    <span class="badge bg-<?php echo $this->statistics->lastSync->status === 'completed' ? 'success' : ($this->statistics->lastSync->status === 'failed' ? 'danger' : 'warning'); ?>">
                        <?php echo ucfirst($this->statistics->lastSync->status); ?>
                    </span>
                <?php else : ?>
                    <h5><?php echo Text::_('COM_WHMCSBRIDGE_NEVER'); ?></h5>
                <?php endif; ?>
                <p class="text-muted mb-0"><?php echo Text::_('COM_WHMCSBRIDGE_LAST_SYNC'); ?></p>
            </div>
            <div class="card-footer">
                <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&task=sync.users&' . Session::getFormToken() . '=1'); ?>" class="btn btn-sm btn-success w-100">
                    <?php echo Text::_('COM_WHMCSBRIDGE_SYNC_NOW'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><?php echo Text::_('COM_WHMCSBRIDGE_QUICK_ACTIONS'); ?></h4>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&task=sync.users&' . Session::getFormToken() . '=1'); ?>" class="btn btn-primary">
                        <span class="icon-refresh" aria-hidden="true"></span>
                        <?php echo Text::_('COM_WHMCSBRIDGE_SYNC_ALL_USERS'); ?>
                    </a>
                    <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&task=sync.products&' . Session::getFormToken() . '=1'); ?>" class="btn btn-primary">
                        <span class="icon-refresh" aria-hidden="true"></span>
                        <?php echo Text::_('COM_WHMCSBRIDGE_SYNC_ALL_PRODUCTS'); ?>
                    </a>
                    <a href="<?php echo Route::_('index.php?option=com_whmcsbridge&view=groupmaps&layout=edit'); ?>" class="btn btn-secondary">
                        <span class="icon-plus" aria-hidden="true"></span>
                        <?php echo Text::_('COM_WHMCSBRIDGE_ADD_GROUP_MAPPING'); ?>
                    </a>
                    <a href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_whmcsbridge'); ?>" class="btn btn-outline-secondary">
                        <span class="icon-cog" aria-hidden="true"></span>
                        <?php echo Text::_('COM_WHMCSBRIDGE_SETTINGS'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sync Logs -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><?php echo Text::_('COM_WHMCSBRIDGE_RECENT_SYNCS'); ?></h4>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($this->syncLogs)) : ?>
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_WHMCSBRIDGE_SYNC_TYPE'); ?></th>
                                <th><?php echo Text::_('COM_WHMCSBRIDGE_SYNC_DATE'); ?></th>
                                <th><?php echo Text::_('COM_WHMCSBRIDGE_SYNC_STATUS'); ?></th>
                                <th><?php echo Text::_('COM_WHMCSBRIDGE_SYNC_RECORDS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->syncLogs as $log) : ?>
                                <tr>
                                    <td><?php echo ucfirst($log->sync_type); ?></td>
                                    <td><?php echo HTMLHelper::_('date', $log->started, 'M d, H:i'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($log->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo (int) $log->created_records; ?> / <?php echo (int) $log->updated_records; ?> / <?php echo (int) $log->failed_records; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="p-3 text-center text-muted">
                        <?php echo Text::_('COM_WHMCSBRIDGE_NO_SYNC_LOGS'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
