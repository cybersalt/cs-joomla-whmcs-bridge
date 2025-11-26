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
 * Sync Controller for WHMCS Bridge
 *
 * @since  1.0.0
 */
class SyncController extends BaseController
{
    /**
     * Run full user sync from WHMCS
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function users(): void
    {
        Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\SyncModel $model */
        $model = $this->getModel('Sync');

        try {
            $result = $model->syncUsersFromWhmcs();

            if ($result['success']) {
                $this->app->enqueueMessage(
                    Text::sprintf(
                        'COM_WHMCSBRIDGE_SYNC_USERS_SUCCESS',
                        $result['created'],
                        $result['updated'],
                        $result['total']
                    ),
                    'success'
                );
            } else {
                $this->app->enqueueMessage(
                    Text::sprintf('COM_WHMCSBRIDGE_SYNC_USERS_FAILED', $result['error']),
                    'error'
                );
            }
        } catch (\Exception $e) {
            $this->app->enqueueMessage(
                Text::sprintf('COM_WHMCSBRIDGE_SYNC_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=dashboard', false));
    }

    /**
     * Run products sync from WHMCS
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function products(): void
    {
        Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\SyncModel $model */
        $model = $this->getModel('Sync');

        try {
            $result = $model->syncProductsFromWhmcs();

            if ($result['success']) {
                $this->app->enqueueMessage(
                    Text::sprintf(
                        'COM_WHMCSBRIDGE_SYNC_PRODUCTS_SUCCESS',
                        $result['synced'],
                        $result['total']
                    ),
                    'success'
                );
            } else {
                $this->app->enqueueMessage(
                    Text::sprintf('COM_WHMCSBRIDGE_SYNC_PRODUCTS_FAILED', $result['error']),
                    'error'
                );
            }
        } catch (\Exception $e) {
            $this->app->enqueueMessage(
                Text::sprintf('COM_WHMCSBRIDGE_SYNC_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=dashboard', false));
    }

    /**
     * Sync a single user by ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function syncuser(): void
    {
        Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

        $whmcsClientId = $this->input->getInt('whmcs_id', 0);

        if (!$whmcsClientId) {
            $this->app->enqueueMessage(Text::_('COM_WHMCSBRIDGE_ERROR_NO_CLIENT_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=users', false));
            return;
        }

        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\SyncModel $model */
        $model = $this->getModel('Sync');

        try {
            $result = $model->syncSingleUser($whmcsClientId);

            if ($result) {
                $this->app->enqueueMessage(Text::_('COM_WHMCSBRIDGE_SYNC_USER_SUCCESS'), 'success');
            } else {
                $this->app->enqueueMessage(Text::_('COM_WHMCSBRIDGE_SYNC_USER_FAILED'), 'error');
            }
        } catch (\Exception $e) {
            $this->app->enqueueMessage(
                Text::sprintf('COM_WHMCSBRIDGE_SYNC_ERROR', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=users', false));
    }

    /**
     * Test WHMCS API connection
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function testapi(): void
    {
        Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\SyncModel $model */
        $model = $this->getModel('Sync');

        if ($model->testApiConnection()) {
            $this->app->enqueueMessage(Text::_('COM_WHMCSBRIDGE_API_TEST_SUCCESS'), 'success');
        } else {
            $error = $model->getApiError();
            $this->app->enqueueMessage(
                Text::sprintf('COM_WHMCSBRIDGE_API_TEST_FAILED', $error['message'] ?? 'Unknown error'),
                'error'
            );
        }

        $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=settings', false));
    }
}
