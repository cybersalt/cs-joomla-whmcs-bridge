<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\View\Log;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

/**
 * Log HTML View
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  string  Log content
     */
    protected $logContent;

    /**
     * @var  string  Log file path
     */
    protected $logFile;

    /**
     * @var  bool  Whether log file exists
     */
    protected $logExists;

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
        $this->logFile = JPATH_ADMINISTRATOR . '/logs/com_whmcsbridge.api.log.php';
        $this->logExists = file_exists($this->logFile);
        $this->logContent = '';

        if ($this->logExists) {
            $content = file_get_contents($this->logFile);
            // Remove the PHP die line
            $lines = explode("\n", $content);
            if (!empty($lines) && strpos($lines[0], '<?php die') !== false) {
                array_shift($lines);
            }
            $this->logContent = implode("\n", $lines);
        }

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
        ToolbarHelper::title(Text::_('COM_WHMCSBRIDGE_API_LOG'), 'file-alt');

        ToolbarHelper::custom('dashboard.clearLog', 'trash', '', 'COM_WHMCSBRIDGE_CLEAR_LOG', false);
        ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_whmcsbridge'));

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_whmcsbridge')) {
            ToolbarHelper::preferences('com_whmcsbridge');
        }
    }
}
