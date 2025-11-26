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

/** @var \CyberSalt\Component\Whmcsbridge\Administrator\View\Groupmaps\HtmlView $this */
?>

<form action="<?php echo Route::_('index.php?option=com_whmcsbridge&view=groupmaps'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">

                <?php if (!$this->apiConfigured) : ?>
                    <div class="alert alert-warning">
                        <h4 class="alert-heading"><?php echo Text::_('COM_WHMCSBRIDGE_API_NOT_CONFIGURED'); ?></h4>
                        <p><?php echo Text::_('COM_WHMCSBRIDGE_API_NOT_CONFIGURED_DESC'); ?></p>
                        <a href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_whmcsbridge'); ?>" class="btn btn-primary">
                            <?php echo Text::_('COM_WHMCSBRIDGE_CONFIGURE_API'); ?>
                        </a>
                    </div>
                <?php elseif (empty($this->products)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_WHMCSBRIDGE_NO_PRODUCTS_FOUND'); ?>
                    </div>
                <?php else : ?>
                    <div class="alert alert-info mb-3">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_WHMCSBRIDGE_GROUPMAPS_HELP'); ?>
                    </div>

                    <!-- Create New Usergroup Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                <span class="icon-plus" aria-hidden="true"></span>
                                <?php echo Text::_('COM_WHMCSBRIDGE_CREATE_USERGROUP'); ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label for="new_group_title" class="form-label"><?php echo Text::_('COM_WHMCSBRIDGE_USERGROUP_NAME'); ?></label>
                                    <input type="text" class="form-control" id="new_group_title" name="new_group_title" placeholder="<?php echo Text::_('COM_WHMCSBRIDGE_USERGROUP_NAME_PLACEHOLDER'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="new_group_parent" class="form-label"><?php echo Text::_('COM_WHMCSBRIDGE_PARENT_GROUP'); ?></label>
                                    <select class="form-select" id="new_group_parent" name="new_group_parent">
                                        <?php foreach ($this->usergroups as $group) : ?>
                                            <option value="<?php echo $group['id']; ?>" <?php echo $group['id'] == 2 ? 'selected' : ''; ?>>
                                                <?php echo $this->escape($group['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-success" id="createGroupBtn">
                                        <span class="icon-plus" aria-hidden="true"></span>
                                        <?php echo Text::_('COM_WHMCSBRIDGE_CREATE_GROUP'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <table class="table table-striped" id="productMappingsTable">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 5%"><?php echo Text::_('COM_WHMCSBRIDGE_PRODUCT_ID'); ?></th>
                                <th scope="col" style="width: 25%"><?php echo Text::_('COM_WHMCSBRIDGE_PRODUCT_NAME'); ?></th>
                                <th scope="col" style="width: 20%"><?php echo Text::_('COM_WHMCSBRIDGE_PRODUCT_GROUP'); ?></th>
                                <th scope="col" style="width: 50%"><?php echo Text::_('COM_WHMCSBRIDGE_ASSIGNED_USERGROUPS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->products as $i => $product) : ?>
                                <?php
                                $selectedGroups = array_map(function($m) {
                                    return $m['group_id'];
                                }, $product['mappings']);
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo $product['pid']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo $this->escape($product['name']); ?></strong>
                                        <?php if ($product['type']) : ?>
                                            <br><small class="text-muted"><?php echo ucfirst($this->escape($product['type'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($product['groupname']); ?>
                                    </td>
                                    <td>
                                        <select name="mappings[<?php echo $product['pid']; ?>][]"
                                                id="mapping_<?php echo $product['pid']; ?>"
                                                class="form-select"
                                                multiple
                                                size="4">
                                            <?php foreach ($this->usergroups as $group) : ?>
                                                <option value="<?php echo $group['id']; ?>" <?php echo in_array($group['id'], $selectedGroups) ? 'selected' : ''; ?>>
                                                    <?php echo $this->escape($group['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted"><?php echo Text::_('COM_WHMCSBRIDGE_CTRL_CLICK_SELECT'); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle create usergroup button
    var createBtn = document.getElementById('createGroupBtn');
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            var title = document.getElementById('new_group_title').value.trim();
            var parentId = document.getElementById('new_group_parent').value;

            if (!title) {
                alert('<?php echo Text::_('COM_WHMCSBRIDGE_ENTER_GROUP_NAME', true); ?>');
                return;
            }

            // Create form data
            var formData = new FormData();
            formData.append('task', 'groupmaps.createGroup');
            formData.append('title', title);
            formData.append('parent_id', parentId);
            formData.append('<?php echo Session::getFormToken(); ?>', '1');

            fetch('<?php echo Route::_('index.php?option=com_whmcsbridge', false); ?>', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('<?php echo Text::_('COM_WHMCSBRIDGE_GROUP_CREATED', true); ?>');
                    location.reload();
                } else {
                    alert(data.message || '<?php echo Text::_('COM_WHMCSBRIDGE_GROUP_CREATE_FAILED', true); ?>');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('<?php echo Text::_('COM_WHMCSBRIDGE_GROUP_CREATE_FAILED', true); ?>');
            });
        });
    }
});
</script>
