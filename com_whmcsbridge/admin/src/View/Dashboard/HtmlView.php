<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\View\Dashboard;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Dashboard HTML View
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  object  Statistics object
     */
    protected $statistics;

    /**
     * @var  array  Recent sync logs
     */
    protected $syncLogs;

    /**
     * @var  bool  API configured status
     */
    protected $apiConfigured;

    /**
     * @var  array  API status info
     */
    protected $apiStatus;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\DashboardModel $model */
        $model = $this->getModel();

        $this->statistics    = $model->getStatistics();
        $this->syncLogs      = $model->getRecentSyncLogs(5);
        $this->apiConfigured = $model->isApiConfigured();
        $this->apiStatus     = $model->getApiStatus();

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page toolbar
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_WHMCSBRIDGE_DASHBOARD'), 'dashboard');

        if (Factory::getApplication()->getIdentity()->authorise('whmcsbridge.sync', 'com_whmcsbridge')) {
            ToolbarHelper::custom('sync.users', 'refresh', '', 'COM_WHMCSBRIDGE_SYNC_USERS', false);
            ToolbarHelper::custom('sync.products', 'refresh', '', 'COM_WHMCSBRIDGE_SYNC_PRODUCTS', false);
        }

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_whmcsbridge')) {
            ToolbarHelper::preferences('com_whmcsbridge');
        }
    }
}
