<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

\defined('_JEXEC') or die;

/**
 * Products list model
 *
 * @since  1.0.0
 */
class ProductsModel extends ListModel
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
                'product_name', 'a.product_name',
                'product_group', 'a.product_group',
                'domain', 'a.domain',
                'status', 'a.status',
                'next_due_date', 'a.next_due_date',
                'whmcs_email', 'bu.whmcs_email'
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
            $db->quoteName('bu.whmcs_email'),
            $db->quoteName('bu.whmcs_firstname'),
            $db->quoteName('bu.whmcs_lastname'),
            $db->quoteName('u.username', 'joomla_username')
        ])
            ->from($db->quoteName('#__whmcsbridge_products', 'a'))
            ->join('LEFT', $db->quoteName('#__whmcsbridge_users', 'bu') . ' ON ' . $db->quoteName('bu.id') . ' = ' . $db->quoteName('a.bridge_user_id'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('bu.joomla_user_id'));

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(' .
                $db->quoteName('a.product_name') . ' LIKE ' . $search . ' OR ' .
                $db->quoteName('a.domain') . ' LIKE ' . $search . ' OR ' .
                $db->quoteName('bu.whmcs_email') . ' LIKE ' . $search .
            ')');
        }

        // Filter by status
        $status = $this->getState('filter.status');
        if (!empty($status)) {
            $query->where($db->quoteName('a.status') . ' = ' . $db->quote($status));
        }

        // Filter by product group
        $group = $this->getState('filter.product_group');
        if (!empty($group)) {
            $query->where($db->quoteName('a.product_group') . ' = ' . $db->quote($group));
        }

        // Add ordering
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Get list of unique product groups
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getProductGroups(): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('product_group'))
            ->from($db->quoteName('#__whmcsbridge_products'))
            ->where($db->quoteName('product_group') . ' != ' . $db->quote(''))
            ->order($db->quoteName('product_group') . ' ASC');

        $db->setQuery($query);

        return $db->loadColumn() ?: [];
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

        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'string');
        $this->setState('filter.status', $status);

        $group = $this->getUserStateFromRequest($this->context . '.filter.product_group', 'filter_product_group', '', 'string');
        $this->setState('filter.product_group', $group);

        parent::populateState($ordering, $direction);
    }
}
