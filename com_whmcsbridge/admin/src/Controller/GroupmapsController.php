<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

\defined('_JEXEC') or die;

/**
 * Groupmaps controller class.
 *
 * @since  1.0.0
 */
class GroupmapsController extends BaseController
{
    /**
     * Save all product to usergroup mappings
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function saveAll(): void
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        // Check permissions
        if (!Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_whmcsbridge')) {
            Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=groupmaps', false));
            return;
        }

        $app = Factory::getApplication();
        $mappings = $app->input->get('mappings', [], 'array');

        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\GroupmapsModel $model */
        $model = $this->getModel('Groupmaps');

        $saved = 0;
        $errors = 0;

        foreach ($mappings as $productId => $groupIds) {
            // Filter and validate group IDs
            $groupIds = array_filter(array_map('intval', $groupIds));

            try {
                if ($model->saveProductMappings((int) $productId, $groupIds)) {
                    $saved++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                $errors++;
                $app->enqueueMessage(Text::sprintf('COM_WHMCSBRIDGE_SAVE_MAPPING_ERROR', $productId, $e->getMessage()), 'error');
            }
        }

        if ($saved > 0) {
            $app->enqueueMessage(Text::sprintf('COM_WHMCSBRIDGE_MAPPINGS_SAVED', $saved), 'success');
        }

        if ($errors > 0) {
            $app->enqueueMessage(Text::sprintf('COM_WHMCSBRIDGE_MAPPINGS_ERRORS', $errors), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=groupmaps', false));
    }

    /**
     * Create a new usergroup via AJAX
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function createGroup(): void
    {
        // Check for request forgeries
        Session::checkToken() or die(json_encode(['success' => false, 'message' => Text::_('JINVALID_TOKEN')]));

        $app = Factory::getApplication();

        // Set JSON response
        $app->mimeType = 'application/json';
        $app->setHeader('Content-Type', 'application/json; charset=utf-8');

        // Check permissions
        if (!$app->getIdentity()->authorise('core.create', 'com_users')) {
            echo json_encode(['success' => false, 'message' => Text::_('JLIB_APPLICATION_ERROR_CREATE_NOT_PERMITTED')]);
            $app->close();
            return;
        }

        $title = $app->input->getString('title', '');
        $parentId = $app->input->getInt('parent_id', 2);

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => Text::_('COM_WHMCSBRIDGE_ENTER_GROUP_NAME')]);
            $app->close();
            return;
        }

        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\GroupmapsModel $model */
        $model = $this->getModel('Groupmaps');

        try {
            $groupId = $model->createUsergroup($title, $parentId);

            if ($groupId) {
                echo json_encode([
                    'success' => true,
                    'message' => Text::_('COM_WHMCSBRIDGE_GROUP_CREATED'),
                    'group_id' => $groupId,
                    'group_title' => $title
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => Text::_('COM_WHMCSBRIDGE_GROUP_CREATE_FAILED')]);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        $app->close();
    }

    /**
     * Get model instance
     *
     * @param   string  $name    Model name
     * @param   string  $prefix  Model prefix
     * @param   array   $config  Configuration array
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @since   1.0.0
     */
    public function getModel($name = 'Groupmaps', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
