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
use Joomla\CMS\Factory;
use CyberSalt\Component\Whmcsbridge\Administrator\Helper\WhmcsApi;

\defined('_JEXEC') or die;

/**
 * Groupmaps model - Shows all WHMCS products with mapping capability
 *
 * @since  1.0.0
 */
class GroupmapsModel extends BaseDatabaseModel
{
    /**
     * @var WhmcsApi
     */
    protected $api;

    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->api = new WhmcsApi();
    }

    /**
     * Get all WHMCS products with their current group mappings
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getProductsWithMappings(): array
    {
        $products = [];

        // Fetch products from WHMCS API
        if ($this->api->isConfigured()) {
            $result = $this->api->getProducts();

            if ($result && isset($result['products']['product'])) {
                foreach ($result['products']['product'] as $product) {
                    $products[] = [
                        'pid'         => (int) $product['pid'],
                        'name'        => $product['name'] ?? '',
                        'description' => $product['description'] ?? '',
                        'groupname'   => $product['groupname'] ?? '',
                        'gid'         => (int) ($product['gid'] ?? 0),
                        'type'        => $product['type'] ?? '',
                        'mappings'    => []
                    ];
                }
            }
        }

        // Get existing mappings from database
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'gm.id',
                'gm.whmcs_identifier',
                'gm.joomla_group_id',
                'ug.title AS joomla_group_title'
            ])
            ->from($db->quoteName('#__whmcsbridge_groupmaps', 'gm'))
            ->join('LEFT', $db->quoteName('#__usergroups', 'ug') . ' ON ' . $db->quoteName('ug.id') . ' = ' . $db->quoteName('gm.joomla_group_id'))
            ->where($db->quoteName('gm.map_type') . ' = ' . $db->quote('product'))
            ->where($db->quoteName('gm.published') . ' = 1');

        $db->setQuery($query);
        $mappings = $db->loadObjectList() ?: [];

        // Index mappings by product ID
        $mappingsByProduct = [];
        foreach ($mappings as $mapping) {
            $productId = $mapping->whmcs_identifier;
            if (!isset($mappingsByProduct[$productId])) {
                $mappingsByProduct[$productId] = [];
            }
            $mappingsByProduct[$productId][] = [
                'id'          => $mapping->id,
                'group_id'    => $mapping->joomla_group_id,
                'group_title' => $mapping->joomla_group_title
            ];
        }

        // Attach mappings to products
        foreach ($products as &$product) {
            $pid = (string) $product['pid'];
            $product['mappings'] = $mappingsByProduct[$pid] ?? [];
        }

        return $products;
    }

    /**
     * Get all Joomla usergroups
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getUsergroups(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['id', 'title', 'parent_id'])
            ->from($db->quoteName('#__usergroups'))
            ->order('lft ASC');

        $db->setQuery($query);
        $groups = $db->loadObjectList() ?: [];

        // Build hierarchical titles
        $groupsById = [];
        foreach ($groups as $group) {
            $groupsById[$group->id] = $group;
        }

        $result = [];
        foreach ($groups as $group) {
            $title = $this->buildGroupTitle($group, $groupsById);
            $result[] = [
                'id'    => $group->id,
                'title' => $title
            ];
        }

        return $result;
    }

    /**
     * Build hierarchical group title
     *
     * @param   object  $group       The group object
     * @param   array   $groupsById  All groups indexed by ID
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function buildGroupTitle(object $group, array $groupsById): string
    {
        $titles = [$group->title];
        $parentId = $group->parent_id;

        while ($parentId && isset($groupsById[$parentId])) {
            $parent = $groupsById[$parentId];
            array_unshift($titles, $parent->title);
            $parentId = $parent->parent_id;
        }

        // Remove "Root" if present
        if (count($titles) > 1 && $titles[0] === 'Root') {
            array_shift($titles);
        }

        return implode(' > ', $titles);
    }

    /**
     * Save product to usergroup mappings
     *
     * @param   int    $productId  WHMCS product ID
     * @param   array  $groupIds   Array of Joomla usergroup IDs
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function saveProductMappings(int $productId, array $groupIds): bool
    {
        $db = $this->getDatabase();

        // First, remove existing mappings for this product
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__whmcsbridge_groupmaps'))
            ->where($db->quoteName('map_type') . ' = ' . $db->quote('product'))
            ->where($db->quoteName('whmcs_identifier') . ' = ' . $db->quote((string) $productId));

        $db->setQuery($query);
        $db->execute();

        // Insert new mappings
        if (!empty($groupIds)) {
            $columns = ['map_type', 'whmcs_identifier', 'whmcs_name', 'joomla_group_id', 'published', 'priority'];

            // Get product name from API or local cache
            $productName = $this->getProductName($productId);

            $values = [];
            foreach ($groupIds as $groupId) {
                $values[] = implode(',', [
                    $db->quote('product'),
                    $db->quote((string) $productId),
                    $db->quote($productName),
                    (int) $groupId,
                    1,
                    0
                ]);
            }

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__whmcsbridge_groupmaps'))
                ->columns($db->quoteName($columns))
                ->values($values);

            $db->setQuery($query);
            $db->execute();
        }

        return true;
    }

    /**
     * Get product name by ID
     *
     * @param   int  $productId  WHMCS product ID
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function getProductName(int $productId): string
    {
        if (!$this->api->isConfigured()) {
            return 'Product #' . $productId;
        }

        $result = $this->api->getProducts();

        if ($result && isset($result['products']['product'])) {
            foreach ($result['products']['product'] as $product) {
                if ((int) $product['pid'] === $productId) {
                    return $product['name'] ?? 'Product #' . $productId;
                }
            }
        }

        return 'Product #' . $productId;
    }

    /**
     * Create a new Joomla usergroup
     *
     * @param   string  $title     Group title
     * @param   int     $parentId  Parent group ID (default: 2 = Registered)
     *
     * @return  int|false  New group ID on success, false on failure
     *
     * @since   1.0.0
     */
    public function createUsergroup(string $title, int $parentId = 2): int|false
    {
        $db = $this->getDatabase();

        // Get parent's lft and rgt values
        $query = $db->getQuery(true)
            ->select(['lft', 'rgt'])
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('id') . ' = ' . $parentId);

        $db->setQuery($query);
        $parent = $db->loadObject();

        if (!$parent) {
            return false;
        }

        // Make room for the new node
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__usergroups'))
            ->set($db->quoteName('rgt') . ' = ' . $db->quoteName('rgt') . ' + 2')
            ->where($db->quoteName('rgt') . ' >= ' . $parent->rgt);

        $db->setQuery($query);
        $db->execute();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__usergroups'))
            ->set($db->quoteName('lft') . ' = ' . $db->quoteName('lft') . ' + 2')
            ->where($db->quoteName('lft') . ' > ' . $parent->rgt);

        $db->setQuery($query);
        $db->execute();

        // Insert the new group
        $newGroup = (object) [
            'parent_id' => $parentId,
            'title'     => $title,
            'lft'       => $parent->rgt,
            'rgt'       => $parent->rgt + 1
        ];

        $db->insertObject('#__usergroups', $newGroup);

        return $db->insertid();
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
        return $this->api->isConfigured();
    }

    /**
     * Get API error message
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function getApiError(): string
    {
        $error = $this->api->getLastError();
        return $error['message'] ?? '';
    }
}
