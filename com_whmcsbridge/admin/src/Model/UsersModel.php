<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/**
 * Users list model
 *
 * @since  1.0.0
 */
class UsersModel extends ListModel
{
    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'whmcs_email', 'a.whmcs_email',
                'whmcs_firstname', 'a.whmcs_firstname',
                'whmcs_lastname', 'a.whmcs_lastname',
                'whmcs_status', 'a.whmcs_status',
                'sync_status', 'a.sync_status',
                'last_sync', 'a.last_sync',
                'joomla_username', 'u.username'
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build the query for the list
     *
     * @return  QueryInterface
     *
     * @since   1.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            'a.*',
            $db->quoteName('u.username', 'joomla_username'),
            $db->quoteName('u.name', 'joomla_name'),
            $db->quoteName('u.email', 'joomla_email'),
            $db->quoteName('u.block', 'joomla_blocked')
        ])
            ->from($db->quoteName('#__whmcsbridge_users', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.joomla_user_id'));

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(' .
                    $db->quoteName('a.whmcs_email') . ' LIKE ' . $search . ' OR ' .
                    $db->quoteName('a.whmcs_firstname') . ' LIKE ' . $search . ' OR ' .
                    $db->quoteName('a.whmcs_lastname') . ' LIKE ' . $search . ' OR ' .
                    $db->quoteName('u.username') . ' LIKE ' . $search . ' OR ' .
                    $db->quoteName('u.name') . ' LIKE ' . $search .
                ')');
            }
        }

        // Filter by WHMCS status
        $status = $this->getState('filter.whmcs_status');
        if (!empty($status)) {
            $query->where($db->quoteName('a.whmcs_status') . ' = ' . $db->quote($status));
        }

        // Filter by sync status
        $syncStatus = $this->getState('filter.sync_status');
        if (!empty($syncStatus)) {
            $query->where($db->quoteName('a.sync_status') . ' = ' . $db->quote($syncStatus));
        }

        // Add ordering
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to auto-populate the model state
     *
     * @param   string  $ordering   Default ordering field
     * @param   string  $direction  Default direction
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'a.id', $direction = 'DESC'): void
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $status = $this->getUserStateFromRequest($this->context . '.filter.whmcs_status', 'filter_whmcs_status', '', 'string');
        $this->setState('filter.whmcs_status', $status);

        $syncStatus = $this->getUserStateFromRequest($this->context . '.filter.sync_status', 'filter_sync_status', '', 'string');
        $this->setState('filter.sync_status', $syncStatus);

        parent::populateState($ordering, $direction);
    }
}
