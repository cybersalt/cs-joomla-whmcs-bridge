<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\View\Groupmaps;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Groupmaps HTML View - Shows all WHMCS products with mapping capability
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  array  List of WHMCS products with mappings
     */
    protected $products;

    /**
     * @var  array  List of Joomla usergroups
     */
    protected $usergroups;

    /**
     * @var  bool  Whether API is configured
     */
    protected $apiConfigured;

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
        /** @var \CyberSalt\Component\Whmcsbridge\Administrator\Model\GroupmapsModel $model */
        $model = $this->getModel();

        $this->apiConfigured = $model->isApiConfigured();
        $this->products      = $model->getProductsWithMappings();
        $this->usergroups    = $model->getUsergroups();

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
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
        ToolbarHelper::title(Text::_('COM_WHMCSBRIDGE_GROUPMAPS'), 'users');

        if (Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_whmcsbridge')) {
            ToolbarHelper::apply('groupmaps.saveAll', 'JTOOLBAR_APPLY');
        }

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_whmcsbridge')) {
            ToolbarHelper::preferences('com_whmcsbridge');
        }
    }
}
