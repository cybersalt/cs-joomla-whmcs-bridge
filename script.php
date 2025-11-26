<?php

/**
 * @package     CyberSalt.Whmcsbridge
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;

/**
 * Package installation script for pkg_whmcsbridge
 *
 * @since  1.0.0
 */
class Pkg_WhmcsbridgeInstallerScript implements InstallerScriptInterface
{
    /**
     * Minimum PHP version
     *
     * @var    string
     * @since  1.0.0
     */
    private string $minimumPhp = '8.1';

    /**
     * Minimum Joomla version
     *
     * @var    string
     * @since  1.0.0
     */
    private string $minimumJoomla = '5.0';

    /**
     * Function called before extension installation/update/removal
     *
     * @param   string            $type    The type of change
     * @param   InstallerAdapter  $parent  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf('WHMCS Bridge requires PHP %s or higher.', $this->minimumPhp),
                'error'
            );
            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf('WHMCS Bridge requires Joomla %s or higher.', $this->minimumJoomla),
                'error'
            );
            return false;
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal
     *
     * @param   string            $type    The type of change
     * @param   InstallerAdapter  $parent  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        // Clear autoloader cache
        $cacheFile = JPATH_CACHE . '/autoload_psr4.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        // Enable the authentication plugin automatically
        if ($type === 'install') {
            $this->enablePlugin();
            $this->displayWelcomeMessage();
        }

        return true;
    }

    /**
     * Function called on install
     *
     * @param   InstallerAdapter  $parent  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function install(InstallerAdapter $parent): bool
    {
        return true;
    }

    /**
     * Function called on update
     *
     * @param   InstallerAdapter  $parent  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function update(InstallerAdapter $parent): bool
    {
        return true;
    }

    /**
     * Function called on uninstall
     *
     * @param   InstallerAdapter  $parent  The adapter calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function uninstall(InstallerAdapter $parent): bool
    {
        return true;
    }

    /**
     * Enable the authentication plugin
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function enablePlugin(): void
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('authentication'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('whmcs'));

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (\Exception $e) {
            // Plugin might not exist yet, ignore
        }
    }

    /**
     * Display welcome message after installation
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function displayWelcomeMessage(): void
    {
        $message = '
        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">WHMCS Bridge Package Installed Successfully!</h4>
            </div>
            <div class="card-body">
                <p>The following extensions have been installed:</p>
                <ul>
                    <li><strong>WHMCS Bridge Component</strong> - Syncs users and products from WHMCS</li>
                    <li><strong>WHMCS Authentication Plugin</strong> - Allows users to log in with WHMCS credentials (auto-enabled)</li>
                </ul>

                <h5>Next Steps:</h5>
                <ol>
                    <li><strong>Configure API Settings:</strong> Go to <a href="index.php?option=com_config&view=component&component=com_whmcsbridge">Components &gt; WHMCS Bridge &gt; Options</a> and enter your WHMCS API credentials.</li>
                    <li><strong>Create Group Mappings:</strong> Set up mappings between WHMCS products and Joomla user groups.</li>
                    <li><strong>Run Initial Sync:</strong> Go to the Dashboard and click "Sync Users" to import your WHMCS clients.</li>
                </ol>

                <div class="alert alert-info mb-0">
                    <strong>Need WHMCS API Credentials?</strong><br>
                    In WHMCS, go to Setup &gt; Staff Management &gt; API Credentials to create new API credentials.
                </div>
            </div>
        </div>';

        Factory::getApplication()->enqueueMessage($message, 'info');
    }
}
