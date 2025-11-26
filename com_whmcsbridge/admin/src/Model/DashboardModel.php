<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use CyberSalt\Component\Whmcsbridge\Administrator\Helper\WhmcsApi;

\defined('_JEXEC') or die;

/**
 * Dashboard Model
 *
 * @since  1.0.0
 */
class DashboardModel extends BaseDatabaseModel
{
    /**
     * Get dashboard statistics
     *
     * @return  object
     *
     * @since   1.0.0
     */
    public function getStatistics(): object
    {
        $db = $this->getDatabase();

        $stats = new \stdClass();

        // Count synced users
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__whmcsbridge_users'));
        $db->setQuery($query);
        $stats->syncedUsers = (int) $db->loadResult();

        // Count products
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__whmcsbridge_products'));
        $db->setQuery($query);
        $stats->totalProducts = (int) $db->loadResult();

        // Count active products
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__whmcsbridge_products'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('Active'));
        $db->setQuery($query);
        $stats->activeProducts = (int) $db->loadResult();

        // Count group mappings
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__whmcsbridge_groupmaps'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $stats->groupMappings = (int) $db->loadResult();

        // Get last sync info
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__whmcsbridge_sync_log'))
            ->order($db->quoteName('started') . ' DESC');
        $db->setQuery($query, 0, 1);
        $stats->lastSync = $db->loadObject();

        return $stats;
    }

    /**
     * Get recent sync logs
     *
     * @param   int  $limit  Number of logs to retrieve
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getRecentSyncLogs(int $limit = 10): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__whmcsbridge_sync_log'))
            ->order($db->quoteName('started') . ' DESC');
        $db->setQuery($query, 0, $limit);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Check if API is configured
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function isApiConfigured(): bool
    {
        $api = new WhmcsApi();
        return $api->isConfigured();
    }

    /**
     * Get API connection status
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getApiStatus(): array
    {
        $api = new WhmcsApi();

        if (!$api->isConfigured()) {
            return [
                'configured' => false,
                'connected'  => false,
                'error'      => 'API not configured'
            ];
        }

        $connected = $api->testConnection();

        return [
            'configured' => true,
            'connected'  => $connected,
            'error'      => $connected ? '' : ($api->getLastError()['message'] ?? 'Unknown error')
        ];
    }
}
