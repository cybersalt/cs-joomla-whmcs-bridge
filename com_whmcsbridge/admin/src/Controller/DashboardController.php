<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

\defined('_JEXEC') or die;

/**
 * Dashboard controller class.
 *
 * @since  1.0.0
 */
class DashboardController extends BaseController
{
    /**
     * View the API log file
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function viewLog(): void
    {
        $app = Factory::getApplication();

        // Check permissions
        if (!$app->getIdentity()->authorise('core.admin', 'com_whmcsbridge')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_whmcsbridge', false));
            return;
        }

        // Set the view
        $app->input->set('view', 'log');

        parent::display();
    }

    /**
     * Clear the API log file
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function clearLog(): void
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();

        // Check permissions
        if (!$app->getIdentity()->authorise('core.admin', 'com_whmcsbridge')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_whmcsbridge', false));
            return;
        }

        $logFile = JPATH_ADMINISTRATOR . '/logs/com_whmcsbridge.api.log.php';

        if (file_exists($logFile)) {
            // Recreate with just the PHP die header
            file_put_contents($logFile, "#<?php die('Direct Access To Log Files Not Permitted'); ?>\n");
            $app->enqueueMessage(Text::_('COM_WHMCSBRIDGE_LOG_CLEARED'), 'success');
        }

        $this->setRedirect(Route::_('index.php?option=com_whmcsbridge&view=log', false));
    }
}
