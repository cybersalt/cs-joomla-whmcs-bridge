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
use Joomla\CMS\Router\Route;

/** @var \CyberSalt\Component\Whmcsbridge\Administrator\View\Log\HtmlView $this */
?>

<form action="<?php echo Route::_('index.php?option=com_whmcsbridge&view=log'); ?>" method="post" name="adminForm" id="adminForm">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <span class="icon-file-alt" aria-hidden="true"></span>
                        <?php echo Text::_('COM_WHMCSBRIDGE_API_LOG'); ?>
                    </h4>
                    <small class="text-muted"><?php echo $this->logFile; ?></small>
                </div>
                <div class="card-body">
                    <?php if (!$this->logExists) : ?>
                        <div class="alert alert-info">
                            <span class="icon-info-circle" aria-hidden="true"></span>
                            <?php echo Text::_('COM_WHMCSBRIDGE_LOG_NOT_FOUND'); ?>
                        </div>
                    <?php elseif (empty(trim($this->logContent))) : ?>
                        <div class="alert alert-info">
                            <span class="icon-info-circle" aria-hidden="true"></span>
                            <?php echo Text::_('COM_WHMCSBRIDGE_LOG_EMPTY'); ?>
                        </div>
                    <?php else : ?>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <strong><?php echo Text::_('COM_WHMCSBRIDGE_LOG_HELP'); ?></strong>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="copyLogBtn" onclick="copyLogToClipboard()">
                                <span class="icon-copy" aria-hidden="true"></span>
                                <?php echo Text::_('COM_WHMCSBRIDGE_COPY_LOG'); ?>
                            </button>
                        </div>
                        <pre id="logContent" class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto; font-size: 12px; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($this->logContent); ?></pre>
                        <script>
                        function copyLogToClipboard() {
                            const logContent = document.getElementById('logContent').innerText;
                            navigator.clipboard.writeText(logContent).then(function() {
                                const btn = document.getElementById('copyLogBtn');
                                const originalHtml = btn.innerHTML;
                                btn.innerHTML = '<span class="icon-check" aria-hidden="true"></span> <?php echo Text::_('COM_WHMCSBRIDGE_COPIED'); ?>';
                                btn.classList.remove('btn-outline-secondary');
                                btn.classList.add('btn-success');
                                setTimeout(function() {
                                    btn.innerHTML = originalHtml;
                                    btn.classList.remove('btn-success');
                                    btn.classList.add('btn-outline-secondary');
                                }, 2000);
                            });
                        }
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><?php echo Text::_('COM_WHMCSBRIDGE_API_TROUBLESHOOTING'); ?></h4>
                </div>
                <div class="card-body">
                    <h5><?php echo Text::_('COM_WHMCSBRIDGE_403_ERROR_TITLE'); ?></h5>
                    <p><?php echo Text::_('COM_WHMCSBRIDGE_403_ERROR_DESC'); ?></p>
                    <ul>
                        <li><strong><?php echo Text::_('COM_WHMCSBRIDGE_403_FIX_1'); ?></strong> - <?php echo Text::_('COM_WHMCSBRIDGE_403_FIX_1_DESC'); ?></li>
                        <li><strong><?php echo Text::_('COM_WHMCSBRIDGE_403_FIX_2'); ?></strong> - <?php echo Text::_('COM_WHMCSBRIDGE_403_FIX_2_DESC'); ?></li>
                        <li><strong><?php echo Text::_('COM_WHMCSBRIDGE_403_FIX_3'); ?></strong> - <?php echo Text::_('COM_WHMCSBRIDGE_403_FIX_3_DESC'); ?></li>
                    </ul>

                    <h5 class="mt-4"><?php echo Text::_('COM_WHMCSBRIDGE_REQUIRED_PERMISSIONS'); ?></h5>
                    <p><small>Found under Setup > Staff Management > API Credentials > Edit > API Permissions</small></p>
                    <ul>
                        <li><strong>Clients:</strong> GetClients, GetClientsDetails</li>
                        <li><strong>Products:</strong> GetProducts, GetClientsProducts</li>
                        <li><strong>Authentication:</strong> ValidateLogin</li>
                    </ul>
                    <p><small class="text-muted">Permission names vary by WHMCS version. If you can't find specific permissions, try enabling all permissions in the relevant section or use "Full Administrator" access.</small></p>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
