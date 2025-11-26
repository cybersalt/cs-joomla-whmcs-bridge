<?php

/**
 * @package     CyberSalt.Plugin.Authentication.Whmcs
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Plugin\Authentication\Whmcs\Extension;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use CyberSalt\Component\Whmcsbridge\Administrator\Helper\WhmcsApi;

\defined('_JEXEC') or die;

/**
 * WHMCS Authentication Plugin
 *
 * Authenticates users against the WHMCS API and syncs their data
 *
 * @since  1.0.0
 */
class Whmcs extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onUserAuthenticate' => 'onUserAuthenticate',
            'onUserLogin'        => 'onUserLogin',
        ];
    }

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The dispatcher
     * @param   array                $config      An optional associative array of configuration settings
     *
     * @since   1.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        Log::addLogger(
            ['text_file' => 'plg_authentication_whmcs.log.php'],
            Log::ALL,
            ['plg_authentication_whmcs']
        );
    }

    /**
     * Handle the user authentication event
     *
     * @param   Event  $event  The authentication event
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserAuthenticate(Event $event): void
    {
        // Extract arguments from event
        $credentials = $event->getArgument('credentials', []);
        $options     = $event->getArgument('options', []);
        $response    = $event->getArgument('subject');

        $response->type = 'WHMCS';

        // Check if we have credentials
        if (empty($credentials['username']) || empty($credentials['password'])) {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('JGLOBAL_AUTH_EMPTY_PASS_NOT_ALLOWED');
            return;
        }

        // Check if WHMCS Bridge component is installed and configured
        if (!ComponentHelper::isEnabled('com_whmcsbridge')) {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('PLG_AUTHENTICATION_WHMCS_COMPONENT_NOT_ENABLED');
            return;
        }

        // Load the WHMCS API helper
        if (!class_exists(WhmcsApi::class)) {
            // Try to load the class file
            $apiFile = JPATH_ADMINISTRATOR . '/components/com_whmcsbridge/src/Helper/WhmcsApi.php';
            if (file_exists($apiFile)) {
                require_once $apiFile;
            } else {
                $response->status        = Authentication::STATUS_FAILURE;
                $response->error_message = Text::_('PLG_AUTHENTICATION_WHMCS_API_CLASS_NOT_FOUND');
                return;
            }
        }

        $api = new WhmcsApi();

        if (!$api->isConfigured()) {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('PLG_AUTHENTICATION_WHMCS_NOT_CONFIGURED');
            return;
        }

        // Username could be email or username - try email first
        $email = $credentials['username'];

        // Validate login against WHMCS
        $clientData = $api->validateLogin($email, $credentials['password']);

        if (!$clientData) {
            Log::add('WHMCS authentication failed for: ' . $email, Log::DEBUG, 'plg_authentication_whmcs');
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('JGLOBAL_AUTH_INVALID_PASS');
            return;
        }

        // Authentication successful
        Log::add('WHMCS authentication successful for: ' . $email, Log::INFO, 'plg_authentication_whmcs');

        // Get or create Joomla user
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($email));
        $db->setQuery($query);
        $userId = $db->loadResult();

        if (!$userId && $this->params->get('auto_create_user', 1)) {
            // Create new Joomla user
            $userId = $this->createJoomlaUser($clientData);

            if (!$userId) {
                $response->status        = Authentication::STATUS_FAILURE;
                $response->error_message = Text::_('PLG_AUTHENTICATION_WHMCS_USER_CREATE_FAILED');
                return;
            }

            Log::add('Created new Joomla user for WHMCS client: ' . $email, Log::INFO, 'plg_authentication_whmcs');
        }

        if (!$userId) {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('JGLOBAL_AUTH_NO_USER');
            return;
        }

        // Load the user
        $user = User::getInstance($userId);

        if ($user->block) {
            $response->status        = Authentication::STATUS_FAILURE;
            $response->error_message = Text::_('JGLOBAL_AUTH_ACCESS_DENIED');
            return;
        }

        // Store WHMCS data in session for later use
        $this->storeWhmcsData($clientData);

        // Set success response
        $response->status        = Authentication::STATUS_SUCCESS;
        $response->error_message = '';
        $response->username      = $user->username;
        $response->email         = $user->email;
        $response->fullname      = $user->name;
    }

    /**
     * Handle the user login event - sync data after successful login
     *
     * @param   Event  $event  The login event
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserLogin(Event $event): void
    {
        if (!$this->params->get('sync_on_login', 1)) {
            return;
        }

        // Extract arguments from event
        $user    = $event->getArgument('subject', []);
        $options = $event->getArgument('options', []);

        // Sync user data from WHMCS
        try {
            if (!empty($user['username'])) {
                $this->syncUserOnLogin($user['username']);
            }
        } catch (\Exception $e) {
            Log::add('Error syncing user on login: ' . $e->getMessage(), Log::ERROR, 'plg_authentication_whmcs');
        }
    }

    /**
     * Create a new Joomla user from WHMCS client data
     *
     * @param   array  $clientData  WHMCS client data
     *
     * @return  int|false  User ID on success, false on failure
     *
     * @since   1.0.0
     */
    protected function createJoomlaUser(array $clientData): int|false
    {
        $params = ComponentHelper::getParams('com_whmcsbridge');

        $email     = $clientData['email'] ?? '';
        $firstName = $clientData['firstname'] ?? '';
        $lastName  = $clientData['lastname'] ?? '';
        $name      = trim($firstName . ' ' . $lastName) ?: $email;

        if (!$email) {
            return false;
        }

        // Generate username from email
        $username = strstr($email, '@', true);
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        $username = $this->getUniqueUsername($username);

        $user = new User();

        $userData = [
            'name'         => $name,
            'username'     => $username,
            'email'        => $email,
            'password'     => UserHelper::genRandomPassword(32),
            'block'        => 0,
            'groups'       => [$params->get('default_usergroup', 2)],
            'sendEmail'    => 0,
            'registerDate' => Factory::getDate()->toSql(),
        ];

        if (!$user->bind($userData)) {
            Log::add('Failed to bind user data: ' . $user->getError(), Log::ERROR, 'plg_authentication_whmcs');
            return false;
        }

        if (!$user->save()) {
            Log::add('Failed to save user: ' . $user->getError(), Log::ERROR, 'plg_authentication_whmcs');
            return false;
        }

        // Create bridge user record
        $this->createBridgeUser($user->id, $clientData);

        return $user->id;
    }

    /**
     * Get a unique username
     *
     * @param   string  $baseUsername  Base username
     *
     * @return  string
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
                return $baseUsername . uniqid();
            }
        }
    }

    /**
     * Create bridge user record
     *
     * @param   int    $joomlaUserId  Joomla user ID
     * @param   array  $clientData    WHMCS client data
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function createBridgeUser(int $joomlaUserId, array $clientData): void
    {
        $db  = $this->getDatabase();
        $now = Factory::getDate()->toSql();

        $bridgeData = (object) [
            'joomla_user_id'  => $joomlaUserId,
            'whmcs_client_id' => (int) ($clientData['client']['id'] ?? $clientData['userid'] ?? 0),
            'whmcs_email'     => $clientData['email'] ?? '',
            'whmcs_firstname' => $clientData['firstname'] ?? '',
            'whmcs_lastname'  => $clientData['lastname'] ?? '',
            'whmcs_status'    => $clientData['status'] ?? 'Active',
            'last_sync'       => $now,
            'sync_status'     => 'synced',
            'created'         => $now,
            'modified'        => $now
        ];

        try {
            $db->insertObject('#__whmcsbridge_users', $bridgeData);
        } catch (\Exception $e) {
            Log::add('Failed to create bridge user: ' . $e->getMessage(), Log::ERROR, 'plg_authentication_whmcs');
        }
    }

    /**
     * Store WHMCS data in session
     *
     * @param   array  $clientData  WHMCS client data
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function storeWhmcsData(array $clientData): void
    {
        $session = Factory::getApplication()->getSession();
        $session->set('whmcsbridge.client_data', $clientData);
    }

    /**
     * Sync user data on login
     *
     * @param   string  $username  The username
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function syncUserOnLogin(string $username): void
    {
        $db = $this->getDatabase();

        // Get user by username
        $query = $db->getQuery(true)
            ->select('id, email')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('username') . ' = ' . $db->quote($username));
        $db->setQuery($query);
        $user = $db->loadObject();

        if (!$user) {
            return;
        }

        // Get bridge user
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__whmcsbridge_users'))
            ->where($db->quoteName('joomla_user_id') . ' = ' . (int) $user->id);
        $db->setQuery($query);
        $bridgeUser = $db->loadObject();

        if (!$bridgeUser) {
            return;
        }

        // Load WHMCS API and sync products
        $api = new WhmcsApi();

        if (!$api->isConfigured()) {
            return;
        }

        // Update user data from WHMCS
        $clientData = $api->getClientById((int) $bridgeUser->whmcs_client_id);

        if ($clientData && isset($clientData['client'])) {
            $now = Factory::getDate()->toSql();

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__whmcsbridge_users'))
                ->set([
                    $db->quoteName('whmcs_status') . ' = ' . $db->quote($clientData['client']['status'] ?? 'Active'),
                    $db->quoteName('last_sync') . ' = ' . $db->quote($now),
                    $db->quoteName('modified') . ' = ' . $db->quote($now)
                ])
                ->where($db->quoteName('id') . ' = ' . (int) $bridgeUser->id);

            $db->setQuery($query);
            $db->execute();
        }

        // Sync products (lightweight)
        $products = $api->getClientProducts((int) $bridgeUser->whmcs_client_id);

        if ($products && isset($products['products']['product'])) {
            $this->syncUserProducts((int) $bridgeUser->id, $products['products']['product']);
        }
    }

    /**
     * Sync user products
     *
     * @param   int    $bridgeUserId  Bridge user ID
     * @param   array  $products      Products array
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function syncUserProducts(int $bridgeUserId, array $products): void
    {
        $db  = $this->getDatabase();
        $now = Factory::getDate()->toSql();

        // Handle single product
        if (isset($products['id'])) {
            $products = [$products];
        }

        foreach ($products as $product) {
            $serviceId = (int) ($product['id'] ?? 0);

            if (!$serviceId) {
                continue;
            }

            // Check if exists
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
                'last_sync'         => $now,
                'modified'          => $now
            ];

            if ($existingId) {
                $query = $db->getQuery(true)->update($db->quoteName('#__whmcsbridge_products'));
                foreach ($productData as $key => $value) {
                    if ($value === null) {
                        $query->set($db->quoteName($key) . ' = NULL');
                    } else {
                        $query->set($db->quoteName($key) . ' = ' . $db->quote($value));
                    }
                }
                $query->where($db->quoteName('id') . ' = ' . (int) $existingId);
            } else {
                $productData['created'] = $now;
                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__whmcsbridge_products'))
                    ->columns($db->quoteName(array_keys($productData)))
                    ->values(implode(',', array_map(fn($v) => $v === null ? 'NULL' : $db->quote($v), $productData)));
            }

            $db->setQuery($query);
            $db->execute();
        }
    }
}
