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

/** @var \CyberSalt\Component\Whmcsbridge\Administrator\View\Groupmap\HtmlView $this */

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<form action="<?php echo Route::_('index.php?option=com_whmcsbridge&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post"
      name="adminForm"
      id="groupmap-form"
      class="form-validate">

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><?php echo Text::_('COM_WHMCSBRIDGE_GROUPMAP_HELP_TITLE'); ?></h5>
                        <p><?php echo Text::_('COM_WHMCSBRIDGE_GROUPMAP_HELP_DESC'); ?></p>
                        <ul class="mb-0">
                            <li><strong><?php echo Text::_('COM_WHMCSBRIDGE_MAP_TYPE_PRODUCT'); ?>:</strong> <?php echo Text::_('COM_WHMCSBRIDGE_MAP_TYPE_PRODUCT_HELP'); ?></li>
                            <li><strong><?php echo Text::_('COM_WHMCSBRIDGE_MAP_TYPE_PRODUCT_GROUP'); ?>:</strong> <?php echo Text::_('COM_WHMCSBRIDGE_MAP_TYPE_PRODUCT_GROUP_HELP'); ?></li>
                            <li><strong><?php echo Text::_('COM_WHMCSBRIDGE_MAP_TYPE_STATUS'); ?>:</strong> <?php echo Text::_('COM_WHMCSBRIDGE_MAP_TYPE_STATUS_HELP'); ?></li>
                        </ul>
                    </div>

                    <?php echo $this->form->renderField('map_type'); ?>
                    <?php echo $this->form->renderField('whmcs_identifier'); ?>
                    <?php echo $this->form->renderField('whmcs_name'); ?>
                    <?php echo $this->form->renderField('joomla_group_id'); ?>
                    <?php echo $this->form->renderField('priority'); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderField('published'); ?>
                    <?php echo $this->form->renderField('id'); ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
