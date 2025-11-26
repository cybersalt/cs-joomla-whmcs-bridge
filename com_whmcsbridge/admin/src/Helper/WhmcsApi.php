<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Helper
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

\defined('_JEXEC') or die;

/**
 * WHMCS API Helper Class
 *
 * Handles all communication with the WHMCS API
 *
 * @since  1.0.0
 */
class WhmcsApi
{
    /**
     * @var string API URL
     */
    private string $apiUrl;

    /**
     * @var string API Identifier
     */
    private string $identifier;

    /**
     * @var string API Secret
     */
    private string $secret;

    /**
     * @var array Last error information
     */
    private array $lastError = [];

    /**
     * @var bool Skip SSL verification
     */
    private bool $skipSslVerify = false;

    /**
     * Constructor
     *
     * @param   string|null  $apiUrl      WHMCS API URL (optional, loads from config)
     * @param   string|null  $identifier  API Identifier (optional, loads from config)
     * @param   string|null  $secret      API Secret (optional, loads from config)
     *
     * @since   1.0.0
     */
    public function __construct(?string $apiUrl = null, ?string $identifier = null, ?string $secret = null)
    {
        $params = ComponentHelper::getParams('com_whmcsbridge');

        $this->apiUrl       = $apiUrl ?? $params->get('api_url', '');
        $this->identifier   = $identifier ?? $params->get('api_identifier', '');
        $this->secret       = $secret ?? $params->get('api_secret', '');
        $this->skipSslVerify = (bool) $params->get('skip_ssl_verify', false);

        // Ensure API URL ends with /includes/api.php
        if ($this->apiUrl && !str_contains($this->apiUrl, 'api.php')) {
            $this->apiUrl = rtrim($this->apiUrl, '/') . '/includes/api.php';
        }

        // Add logging category
        Log::addLogger(
            ['text_file' => 'com_whmcsbridge.api.log.php'],
            Log::ALL,
            ['com_whmcsbridge.api']
        );
    }

    /**
     * Check if API is configured
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiUrl) && !empty($this->identifier) && !empty($this->secret);
    }

    /**
     * Make an API call to WHMCS
     *
     * @param   string  $action      The API action to call
     * @param   array   $parameters  Additional parameters for the API call
     *
     * @return  array|false  Response array on success, false on failure
     *
     * @since   1.0.0
     */
    public function call(string $action, array $parameters = []): array|false
    {
        if (!$this->isConfigured()) {
            $this->lastError = [
                'code'    => 'NOT_CONFIGURED',
                'message' => 'WHMCS API is not configured. Please set API URL, Identifier, and Secret.'
            ];
            return false;
        }

        $postFields = array_merge([
            'identifier'   => $this->identifier,
            'secret'       => $this->secret,
            'action'       => $action,
            'responsetype' => 'json'
        ], $parameters);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            // SSL verification - can be disabled for same-server connections
            if ($this->skipSslVerify) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            }
            // Force IPv4 to avoid issues with same-server calls
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            // Add headers to appear more like a legitimate browser request
            // This helps avoid bot detection by Cloudflare and other security systems
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            // Follow redirects
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

            // Set default headers that help avoid bot detection
            $httpHeaders = [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9',
                'Content-Type: application/x-www-form-urlencoded'
            ];

            // Check if we should use direct IP connection to bypass Cloudflare
            $params = ComponentHelper::getParams('com_whmcsbridge');
            $directIp = trim($params->get('direct_ip', ''));
            $parsedUrl = parse_url($this->apiUrl);

            if ($directIp && isset($parsedUrl['host']) && !filter_var($parsedUrl['host'], FILTER_VALIDATE_IP)) {
                // Use CURLOPT_RESOLVE to map hostname to IP - this bypasses DNS/Cloudflare
                // while preserving the hostname for virtual hosting and SSL
                $port = isset($parsedUrl['scheme']) && $parsedUrl['scheme'] === 'https' ? 443 : 80;
                $resolve = ["{$parsedUrl['host']}:{$port}:{$directIp}"];
                curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
                Log::add("Using direct IP: {$parsedUrl['host']}:{$port} -> {$directIp}", Log::DEBUG, 'com_whmcsbridge.api');
            } elseif (isset($parsedUrl['host']) && filter_var($parsedUrl['host'], FILTER_VALIDATE_IP)) {
                // Legacy: If using IP address directly in URL, set Host header for virtual hosting
                $whmcsHost = $params->get('whmcs_hostname', '');

                // Clean the hostname - extract just the host if a full URL was entered
                if ($whmcsHost) {
                    // Remove protocol if present
                    $whmcsHost = preg_replace('#^https?://#i', '', $whmcsHost);
                    // Remove any path/trailing slashes
                    $whmcsHost = explode('/', $whmcsHost)[0];
                    $whmcsHost = trim($whmcsHost);
                }

                Log::add("Using IP address in URL, hostname setting: '{$whmcsHost}'", Log::DEBUG, 'com_whmcsbridge.api');
                if ($whmcsHost) {
                    $httpHeaders[] = 'Host: ' . $whmcsHost;
                    Log::add("Set Host header to: {$whmcsHost}", Log::DEBUG, 'com_whmcsbridge.api');
                }
            }

            // Apply all HTTP headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

            Log::add("API URL: {$this->apiUrl}", Log::DEBUG, 'com_whmcsbridge.api');

            // Capture response headers for debugging
            $responseHeaders = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    return $len;
                }
                $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);
                return $len;
            });

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $this->lastError = [
                    'code'    => 'CURL_ERROR',
                    'message' => curl_error($ch)
                ];
                Log::add('WHMCS API cURL error: ' . curl_error($ch), Log::ERROR, 'com_whmcsbridge.api');
                curl_close($ch);
                return false;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
            $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            if ($httpCode !== 200) {
                $this->lastError = [
                    'code'    => 'HTTP_ERROR',
                    'message' => "HTTP error code: {$httpCode}"
                ];
                Log::add("WHMCS API HTTP error: {$httpCode} (Connected to: {$primaryIp}, Local IP: {$localIp})", Log::ERROR, 'com_whmcsbridge.api');
                Log::add("Effective URL after redirects: {$effectiveUrl}", Log::DEBUG, 'com_whmcsbridge.api');
                // Log response headers for debugging
                if (!empty($responseHeaders)) {
                    $headerStr = json_encode($responseHeaders);
                    Log::add("Response headers: {$headerStr}", Log::DEBUG, 'com_whmcsbridge.api');
                }
                // Log first 500 chars of response body for debugging
                $responsePreview = substr($response, 0, 500);
                Log::add("Response body preview: {$responsePreview}", Log::DEBUG, 'com_whmcsbridge.api');
                return false;
            }

            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->lastError = [
                    'code'    => 'JSON_ERROR',
                    'message' => 'Failed to parse WHMCS API response'
                ];
                Log::add('WHMCS API JSON parse error: ' . json_last_error_msg(), Log::ERROR, 'com_whmcsbridge.api');
                return false;
            }

            if (isset($result['result']) && $result['result'] === 'error') {
                $this->lastError = [
                    'code'    => 'API_ERROR',
                    'message' => $result['message'] ?? 'Unknown WHMCS API error'
                ];
                Log::add('WHMCS API error: ' . ($result['message'] ?? 'Unknown'), Log::WARNING, 'com_whmcsbridge.api');
                return false;
            }

            Log::add("WHMCS API call successful: {$action}", Log::DEBUG, 'com_whmcsbridge.api');
            return $result;

        } catch (\Exception $e) {
            $this->lastError = [
                'code'    => 'EXCEPTION',
                'message' => $e->getMessage()
            ];
            Log::add('WHMCS API exception: ' . $e->getMessage(), Log::ERROR, 'com_whmcsbridge.api');
            return false;
        }
    }

    /**
     * Get the last error
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public function getLastError(): array
    {
        return $this->lastError;
    }

    /**
     * Test API connection
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    public function testConnection(): bool
    {
        // Try GetProducts first - it's a commonly available API action
        // GetAdminDetails may not be available in all WHMCS versions
        $result = $this->call('GetProducts');

        if ($result !== false && isset($result['result']) && $result['result'] === 'success') {
            return true;
        }

        // Fallback to WhmcsDetails which is available in most versions
        $result = $this->call('WhmcsDetails');
        return $result !== false && isset($result['result']) && $result['result'] === 'success';
    }

    /**
     * Get all clients from WHMCS
     *
     * @param   int     $limitStart  Starting offset
     * @param   int     $limitNum    Number of records to retrieve
     * @param   string  $sorting     Sorting method (id, firstname, lastname, companyname, email, datecreated, status)
     * @param   string  $status      Filter by status (Active, Inactive, Closed)
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getClients(int $limitStart = 0, int $limitNum = 100, string $sorting = 'id', string $status = ''): array|false
    {
        $params = [
            'limitstart' => $limitStart,
            'limitnum'   => $limitNum,
            'sorting'    => $sorting
        ];

        if ($status) {
            $params['status'] = $status;
        }

        return $this->call('GetClients', $params);
    }

    /**
     * Get client details by ID
     *
     * @param   int  $clientId  WHMCS client ID
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getClientById(int $clientId): array|false
    {
        return $this->call('GetClientsDetails', ['clientid' => $clientId]);
    }

    /**
     * Get client details by email
     *
     * @param   string  $email  Client email address
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getClientByEmail(string $email): array|false
    {
        return $this->call('GetClientsDetails', ['email' => $email]);
    }

    /**
     * Validate client login credentials
     *
     * @param   string  $email     Client email
     * @param   string  $password  Client password (plain text)
     *
     * @return  array|false  Client details on success, false on failure
     *
     * @since   1.0.0
     */
    public function validateLogin(string $email, string $password): array|false
    {
        $result = $this->call('ValidateLogin', [
            'email'     => $email,
            'password2' => $password
        ]);

        if ($result && isset($result['result']) && $result['result'] === 'success') {
            // ValidateLogin returns userid on success, fetch full details
            return $this->getClientById((int) $result['userid']);
        }

        return false;
    }

    /**
     * Get client products/services
     *
     * @param   int     $clientId    WHMCS client ID
     * @param   int     $limitStart  Starting offset
     * @param   int     $limitNum    Number of records
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getClientProducts(int $clientId, int $limitStart = 0, int $limitNum = 100): array|false
    {
        return $this->call('GetClientsProducts', [
            'clientid'   => $clientId,
            'limitstart' => $limitStart,
            'limitnum'   => $limitNum
        ]);
    }

    /**
     * Get all products configured in WHMCS
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getProducts(): array|false
    {
        return $this->call('GetProducts');
    }

    /**
     * Get product groups from WHMCS
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getProductGroups(): array|false
    {
        // WHMCS doesn't have a direct GetProductGroups, we get it from GetProducts
        $products = $this->getProducts();

        if (!$products || !isset($products['products']['product'])) {
            return false;
        }

        $groups = [];
        foreach ($products['products']['product'] as $product) {
            $groupId   = $product['gid'] ?? 0;
            $groupName = $product['groupname'] ?? 'Unknown';

            if ($groupId && !isset($groups[$groupId])) {
                $groups[$groupId] = [
                    'id'   => $groupId,
                    'name' => $groupName
                ];
            }
        }

        return array_values($groups);
    }

    /**
     * Get client domains
     *
     * @param   int  $clientId  WHMCS client ID
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getClientDomains(int $clientId): array|false
    {
        return $this->call('GetClientsDomains', ['clientid' => $clientId]);
    }

    /**
     * Get all client data including products and domains
     *
     * @param   int  $clientId  WHMCS client ID
     *
     * @return  array|false
     *
     * @since   1.0.0
     */
    public function getFullClientData(int $clientId): array|false
    {
        $client = $this->getClientById($clientId);

        if (!$client) {
            return false;
        }

        $products = $this->getClientProducts($clientId);
        $domains  = $this->getClientDomains($clientId);

        return [
            'client'   => $client,
            'products' => $products['products']['product'] ?? [],
            'domains'  => $domains['domains']['domain'] ?? []
        ];
    }
}
