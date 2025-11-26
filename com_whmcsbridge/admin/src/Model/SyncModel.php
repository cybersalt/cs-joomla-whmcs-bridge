<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use CyberSalt\Component\Whmcsbridge\Administrator\Helper\WhmcsApi;

\defined('_JEXEC') or die;

/**
 * Sync Model - Handles all synchronization logic between WHMCS and Joomla
 *
 * @since  1.0.0
 */
class SyncModel extends BaseDatabaseModel
{
    /**
     * @var WhmcsApi
     */
    protected WhmcsApi $api;

    /**
     * @var array Last API error
     */
    protected array $apiError = [];

    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->api = new WhmcsApi();

        Log::addLogger(
            ['text_file' => 'com_whmcsbridge.sync.log.php'],
            Log::ALL,
            ['com_whmcsbridge.sync']
        );
    }

    /**
     * Test API connection
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function testApiConnection(): bool
    {
        $result = $this->api->testConnection();

        if (!$result) {
            $this->apiError = $this->api->getLastError();
        }

        return $result;
    }

    /**
     * Get last API error
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getApiError(): array
    {
        return $this->apiError;
    }

    /**
     * Sync all users from WHMCS to Joomla
     *
     * @return  array  Result array with success status and counts
     *
     * @since   1.0.0
     */
    public function syncUsersFromWhmcs(): array
    {
        $db     = $this->getDatabase();
        $params = ComponentHelper::getParams('com_whmcsbridge');
        $now    = Factory::getDate()->toSql();

        // Start sync log
        $logId = $this->startSyncLog('users');

        $result = [
            'success' => false,
            'total'   => 0,
            'created' => 0,
            'updated' => 0,
            'failed'  => 0,
            'error'   => ''
        ];

        try {
            // Fetch all clients from WHMCS (paginated)
            $limitStart = 0;
            $limitNum   = 100;
            $allClients = [];

            do {
                $response = $this->api->getClients($limitStart, $limitNum);

                if (!$response) {
                    throw new \RuntimeException('Failed to fetch clients from WHMCS: ' . ($this->api->getLastError()['message'] ?? 'Unknown error'));
                }

                $clients    = $response['clients']['client'] ?? [];
                $totalCount = (int) ($response['totalresults'] ?? 0);

                // Handle single client response (not wrapped in array)
                if (isset($clients['id'])) {
                    $clients = [$clients];
                }

                $allClients = array_merge($allClients, $clients);
                $limitStart += $limitNum;

            } while ($limitStart < $totalCount);

            $result['total'] = count($allClients);

            foreach ($allClients as $client) {
                try {
                    $syncResult = $this->syncSingleClientToJoomla($client);

                    if ($syncResult === 'created') {
                        $result['created']++;
                    } elseif ($syncResult === 'updated') {
                        $result['updated']++;
                    }
                } catch (\Exception $e) {
                    $result['failed']++;
                    Log::add('Failed to sync client ' . ($client['email'] ?? 'unknown') . ': ' . $e->getMessage(), Log::ERROR, 'com_whmcsbridge.sync');
                }
            }

            $result['success'] = true;

            // Complete sync log
            $this->completeSyncLog($logId, $result);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $this->failSyncLog($logId, $e->getMessage());
            Log::add('User sync failed: ' . $e->getMessage(), Log::ERROR, 'com_whmcsbridge.sync');
        }

        return $result;
    }

    /**
     * Sync a single WHMCS client to Joomla
     *
     * @param   array  $client  WHMCS client data
     *
     * @return  string  'created', 'updated', or 'unchanged'
     *
     * @since   1.0.0
     */
    protected function syncSingleClientToJoomla(array $client): string
    {
        $db     = $this->getDatabase();
        $params = ComponentHelper::getParams('com_whmcsbridge');
        $now    = Factory::getDate()->toSql();

        $email     = $client['email'] ?? '';
        $whmcsId   = (int) ($client['id'] ?? 0);
        $firstName = $client['firstname'] ?? '';
        $lastName  = $client['lastname'] ?? '';
        $status    = $client['status'] ?? 'Active';

        if (!$email || !$whmcsId) {
            throw new \InvalidArgumentException('Client missing email or ID');
        }

        // Check if bridge mapping exists
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__whmcsbridge_users'))
            ->where($db->quoteName('whmcs_client_id') . ' = ' . $whmcsId);
        $db->setQuery($query);
        $bridgeUser = $db->loadObject();

        // Check if Joomla user exists by email
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($email));
        $db->setQuery($query);
        $joomlaUserId = $db->loadResult();

        $action = 'unchanged';

        if (!$joomlaUserId && $params->get('auto_create_users', 1)) {
            // Create new Joomla user
            $joomlaUserId = $this->createJoomlaUser($client);
            $action = 'created';
            Log::add("Created Joomla user for WHMCS client {$email}", Log::INFO, 'com_whmcsbridge.sync');
        }

        if ($joomlaUserId) {
            if ($bridgeUser) {
                // Update existing bridge record
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__whmcsbridge_users'))
                    ->set([
                        $db->quoteName('whmcs_email') . ' = ' . $db->quote($email),
                        $db->quoteName('whmcs_firstname') . ' = ' . $db->quote($firstName),
                        $db->quoteName('whmcs_lastname') . ' = ' . $db->quote($lastName),
                        $db->quoteName('whmcs_status') . ' = ' . $db->quote($status),
                        $db->quoteName('last_sync') . ' = ' . $db->quote($now),
                        $db->quoteName('sync_status') . ' = ' . $db->quote('synced'),
                        $db->quoteName('modified') . ' = ' . $db->quote($now)
                    ])
                    ->where($db->quoteName('id') . ' = ' . (int) $bridgeUser->id);
                $db->setQuery($query);
                $db->execute();

                if ($action !== 'created') {
                    $action = 'updated';
                }
            } else {
                // Create new bridge record
                $bridgeData = (object) [
                    'joomla_user_id'  => $joomlaUserId,
                    'whmcs_client_id' => $whmcsId,
                    'whmcs_email'     => $email,
                    'whmcs_firstname' => $firstName,
                    'whmcs_lastname'  => $lastName,
                    'whmcs_status'    => $status,
                    'last_sync'       => $now,
                    'sync_status'     => 'synced',
                    'created'         => $now,
                    'modified'        => $now
                ];
                $db->insertObject('#__whmcsbridge_users', $bridgeData);

                if ($action !== 'created') {
                    $action = 'updated';
                }
            }

            // Update user groups based on mappings
            $this->updateUserGroups($joomlaUserId, $whmcsId);
        }

        return $action;
    }

    /**
     * Create a new Joomla user from WHMCS client data
     *
     * @param   array  $client  WHMCS client data
     *
     * @return  int  New Joomla user ID
     *
     * @since   1.0.0
     */
    protected function createJoomlaUser(array $client): int
    {
        $params = ComponentHelper::getParams('com_whmcsbridge');

        $email     = $client['email'];
        $firstName = $client['firstname'] ?? '';
        $lastName  = $client['lastname'] ?? '';
        $name      = trim($firstName . ' ' . $lastName) ?: $email;

        // Generate username from email (before @)
        $username = strstr($email, '@', true);
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

        // Ensure username is unique
        $username = $this->getUniqueUsername($username);

        // Create user object
        $user = new User();

        $userData = [
            'name'           => $name,
            'username'       => $username,
            'email'          => $email,
            'password'       => UserHelper::genRandomPassword(32), // Random password - auth via WHMCS
            'password2'      => '',
            'block'          => 0,
            'groups'         => [$params->get('default_usergroup', 2)], // Default to Registered
            'sendEmail'      => 0,
            'registerDate'   => Factory::getDate()->toSql(),
        ];

        if (!$user->bind($userData)) {
            throw new \RuntimeException('Failed to bind user data: ' . $user->getError());
        }

        if (!$user->save()) {
            throw new \RuntimeException('Failed to save user: ' . $user->getError());
        }

        return $user->id;
    }

    /**
     * Get a unique username
     *
     * @param   string  $baseUsername  Base username to start with
     *
     * @return  string  Unique username
     *
     * @since   1.0.0
     */
    protected function getUniqueUsername(string $baseUsername): string
    {
        $db       = $this->getDatabase();
        $username = $baseUsername;
        $counter  = 1;

        while (true) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('username') . ' = ' . $db->quote($username));
            $db->setQuery($query);

            if ($db->loadResult() == 0) {
                return $username;
            }

            $username = $baseUsername . $counter;
            $counter++;

            if ($counter > 1000) {
                throw new \RuntimeException('Unable to generate unique username');
            }
        }
    }

    /**
     * Update Joomla user groups based on WHMCS products and group mappings
     *
     * @param   int  $joomlaUserId  Joomla user ID
     * @param   int  $whmcsId       WHMCS client ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function updateUserGroups(int $joomlaUserId, int $whmcsId): void
    {
        $db     = $this->getDatabase();
        $params = ComponentHelper::getParams('com_whmcsbridge');

        // Get user's WHMCS products
        $products = $this->api->getClientProducts($whmcsId);

        if (!$products || !isset($products['products']['product'])) {
            return;
        }

        $userProducts = $products['products']['product'];

        // Handle single product (not wrapped in array)
        if (isset($userProducts['id'])) {
            $userProducts = [$userProducts];
        }

        // Get all group mappings
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__whmcsbridge_groupmaps'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('priority') . ' DESC');
        $db->setQuery($query);
        $mappings = $db->loadObjectList();

        if (!$mappings) {
            return;
        }

        $groupsToAdd = [];

        foreach ($userProducts as $product) {
            $productId     = $product['pid'] ?? 0;
            $productName   = $product['name'] ?? '';
            $productGroup  = $product['groupname'] ?? '';
            $productStatus = $product['status'] ?? '';

            foreach ($mappings as $mapping) {
                $match = false;

                switch ($mapping->map_type) {
                    case 'product':
                        // Map by specific product ID
                        if ($mapping->whmcs_identifier == $productId && $productStatus === 'Active') {
                            $match = true;
                        }
                        break;

                    case 'product_group':
                        // Map by product group name
                        if ($mapping->whmcs_identifier == $productGroup && $productStatus === 'Active') {
                            $match = true;
                        }
                        break;

                    case 'status':
                        // Map by product status
                        if ($mapping->whmcs_identifier == $productStatus) {
                            $match = true;
                        }
                        break;
                }

                if ($match && !in_array($mapping->joomla_group_id, $groupsToAdd)) {
                    $groupsToAdd[] = $mapping->joomla_group_id;
                }
            }
        }

        // Add groups to user (don't remove existing groups, only add)
        if (!empty($groupsToAdd)) {
            $user = User::getInstance($joomlaUserId);
            $currentGroups = $user->groups;

            foreach ($groupsToAdd as $groupId) {
                if (!in_array($groupId, $currentGroups)) {
                    $currentGroups[] = $groupId;
                }
            }

            // Save user groups
            $user->groups = array_unique($currentGroups);
            $user->save();

            Log::add("Updated groups for user {$joomlaUserId}: " . implode(',', $groupsToAdd), Log::DEBUG, 'com_whmcsbridge.sync');
        }
    }

    /**
     * Sync a single user by WHMCS client ID
     *
     * @param   int  $whmcsClientId  WHMCS client ID
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function syncSingleUser(int $whmcsClientId): bool
    {
        $clientData = $this->api->getClientById($whmcsClientId);

        if (!$clientData || !isset($clientData['client'])) {
            $this->apiError = $this->api->getLastError();
            return false;
        }

        try {
            $this->syncSingleClientToJoomla($clientData['client']);
            $this->syncUserProducts($whmcsClientId);
            return true;
        } catch (\Exception $e) {
            Log::add('Failed to sync single user: ' . $e->getMessage(), Log::ERROR, 'com_whmcsbridge.sync');
            return false;
        }
    }

    /**
     * Sync products for all bridge users
     *
     * @return  array  Result array
     *
     * @since   1.0.0
     */
    public function syncProductsFromWhmcs(): array
    {
        $db    = $this->getDatabase();
        $logId = $this->startSyncLog('products');

        $result = [
            'success' => false,
            'total'   => 0,
            'synced'  => 0,
            'error'   => ''
        ];

        try {
            // Get all bridge users
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__whmcsbridge_users'));
            $db->setQuery($query);
            $bridgeUsers = $db->loadObjectList();

            $result['total'] = count($bridgeUsers);

            foreach ($bridgeUsers as $bridgeUser) {
                try {
                    $this->syncUserProducts((int) $bridgeUser->whmcs_client_id);
                    $result['synced']++;
                } catch (\Exception $e) {
                    Log::add('Failed to sync products for user ' . $bridgeUser->whmcs_email . ': ' . $e->getMessage(), Log::ERROR, 'com_whmcsbridge.sync');
                }
            }

            $result['success'] = true;
            $this->completeSyncLog($logId, $result);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $this->failSyncLog($logId, $e->getMessage());
        }

        return $result;
    }

    /**
     * Sync products for a single user
     *
     * @param   int  $whmcsClientId  WHMCS client ID
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function syncUserProducts(int $whmcsClientId): void
    {
        $db  = $this->getDatabase();
        $now = Factory::getDate()->toSql();

        // Get bridge user
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__whmcsbridge_users'))
            ->where($db->quoteName('whmcs_client_id') . ' = ' . $whmcsClientId);
        $db->setQuery($query);
        $bridgeUserId = $db->loadResult();

        if (!$bridgeUserId) {
            return;
        }

        // Get products from WHMCS
        $products = $this->api->getClientProducts($whmcsClientId);

        if (!$products || !isset($products['products']['product'])) {
            return;
        }

        $userProducts = $products['products']['product'];

        // Handle single product
        if (isset($userProducts['id'])) {
            $userProducts = [$userProducts];
        }

        foreach ($userProducts as $product) {
            $serviceId = (int) ($product['id'] ?? 0);

            if (!$serviceId) {
                continue;
            }

            // Check if product exists
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__whmcsbridge_products'))
                ->where($db->quoteName('whmcs_service_id') . ' = ' . $serviceId);
            $db->setQuery($query);
            $existingId = $db->loadResult();

            $productData = [
                'bridge_user_id'    => $bridgeUserId,
                'whmcs_service_id'  => $serviceId,
                'whmcs_product_id'  => (int) ($product['pid'] ?? 0),
                'product_name'      => $product['name'] ?? '',
                'product_group'     => $product['groupname'] ?? '',
                'domain'            => $product['domain'] ?? '',
                'status'            => $product['status'] ?? 'Unknown',
                'billing_cycle'     => $product['billingcycle'] ?? '',
                'next_due_date'     => !empty($product['nextduedate']) ? $product['nextduedate'] : null,
                'amount'            => (float) ($product['recurringamount'] ?? 0),
                'currency_code'     => 'USD',
                'registration_date' => !empty($product['regdate']) ? $product['regdate'] : null,
                'last_sync'         => $now,
                'modified'          => $now
            ];

            if ($existingId) {
                // Update
                $query = $db->getQuery(true)->update($db->quoteName('#__whmcsbridge_products'));
                foreach ($productData as $key => $value) {
                    if ($value === null) {
                        $query->set($db->quoteName($key) . ' = NULL');
                    } else {
                        $query->set($db->quoteName($key) . ' = ' . $db->quote($value));
                    }
                }
                $query->where($db->quoteName('id') . ' = ' . (int) $existingId);
                $db->setQuery($query);
                $db->execute();
            } else {
                // Insert
                $productData['created'] = $now;
                $db->insertObject('#__whmcsbridge_products', (object) $productData);
            }
        }
    }

    /**
     * Start a sync log entry
     *
     * @param   string  $type  Sync type
     *
     * @return  int  Log ID
     *
     * @since   1.0.0
     */
    protected function startSyncLog(string $type): int
    {
        $db   = $this->getDatabase();
        $user = Factory::getApplication()->getIdentity();

        $log = (object) [
            'sync_type'      => $type,
            'sync_direction' => 'whmcs_to_joomla',
            'started'        => Factory::getDate()->toSql(),
            'status'         => 'running',
            'initiated_by'   => $user->id
        ];

        $db->insertObject('#__whmcsbridge_sync_log', $log);

        return $db->insertid();
    }

    /**
     * Complete a sync log entry
     *
     * @param   int    $logId   Log ID
     * @param   array  $result  Sync result
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function completeSyncLog(int $logId, array $result): void
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__whmcsbridge_sync_log'))
            ->set([
                $db->quoteName('completed') . ' = ' . $db->quote(Factory::getDate()->toSql()),
                $db->quoteName('total_records') . ' = ' . (int) ($result['total'] ?? 0),
                $db->quoteName('created_records') . ' = ' . (int) ($result['created'] ?? 0),
                $db->quoteName('updated_records') . ' = ' . (int) ($result['updated'] ?? $result['synced'] ?? 0),
                $db->quoteName('failed_records') . ' = ' . (int) ($result['failed'] ?? 0),
                $db->quoteName('status') . ' = ' . $db->quote('completed')
            ])
            ->where($db->quoteName('id') . ' = ' . $logId);

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Mark a sync log as failed
     *
     * @param   int     $logId  Log ID
     * @param   string  $error  Error message
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function failSyncLog(int $logId, string $error): void
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__whmcsbridge_sync_log'))
            ->set([
                $db->quoteName('completed') . ' = ' . $db->quote(Factory::getDate()->toSql()),
                $db->quoteName('status') . ' = ' . $db->quote('failed'),
                $db->quoteName('error_details') . ' = ' . $db->quote($error)
            ])
            ->where($db->quoteName('id') . ' = ' . $logId);

        $db->setQuery($query);
        $db->execute();
    }
}
