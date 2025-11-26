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
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

/**
 * Installation script for com_whmcsbridge
 *
 * @since  1.0.0
 */
class Com_WhmcsbridgeInstallerScript implements InstallerScriptInterface
{
    /**
     * Minimum PHP version required
     *
     * @var    string
     * @since  1.0.0
     */
    private string $minimumPhp = '8.1';

    /**
     * Minimum Joomla version required
     *
     * @var    string
     * @since  1.0.0
     */
    private string $minimumJoomla = '5.0';

    /**
     * Function called before extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update, discover_install, uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   1.0.0
     */
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf('WHMCS Bridge requires PHP %s or higher. You have %s.', $this->minimumPhp, PHP_VERSION),
                'error'
            );
            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                sprintf('WHMCS Bridge requires Joomla %s or higher. You have %s.', $this->minimumJoomla, JVERSION),
                'error'
            );
            return false;
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update, discover_install, uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
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

        // Clear the autoloader cache
        $this->clearAutoloaderCache();

        // Display installation message
        if ($type === 'install') {
            $this->displayInstallMessage();
        }

        return true;
    }

    /**
     * Function called on install
     *
     * @param   InstallerAdapter  $parent  The class calling this method
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
     * @param   InstallerAdapter  $parent  The class calling this method
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
     * @param   InstallerAdapter  $parent  The class calling this method
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
     * Clear the class autoloader cache
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function clearAutoloaderCache(): void
    {
        $cacheFile = JPATH_CACHE . '/autoload_psr4.php';

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        // Also clear OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Display a message after installation
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function displayInstallMessage(): void
    {
        $message = '
        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">WHMCS Bridge Installed Successfully!</h4>
            </div>
            <div class="card-body">
                <h5>Next Steps:</h5>
                <ol>
                    <li><strong>Configure API Settings:</strong> Go to Components > WHMCS Bridge > Options and enter your WHMCS API credentials.</li>
                    <li><strong>Install Authentication Plugin:</strong> Install the WHMCS Authentication plugin to allow users to log in with their WHMCS credentials.</li>
                    <li><strong>Create Group Mappings:</strong> Set up mappings between WHMCS products and Joomla user groups.</li>
                    <li><strong>Run Initial Sync:</strong> Click "Sync Users" to import your WHMCS clients.</li>
                </ol>
                <div class="alert alert-info">
                    <strong>Need WHMCS API Credentials?</strong><br>
                    In WHMCS, go to Setup > Staff Management > API Credentials to create new API credentials.
                </div>
            </div>
        </div>';

        Factory::getApplication()->enqueueMessage($message, 'info');
    }
}
