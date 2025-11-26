<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\View\Users;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Users list HTML View
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  array  List of items
     */
    protected $items;

    /**
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * @var  \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * @var  \Joomla\CMS\Form\Form
     */
    public $filterForm;

    /**
     * @var  array  Active filters
     */
    public $activeFilters;

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
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

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
        ToolbarHelper::title(Text::_('COM_WHMCSBRIDGE_USERS'), 'users');

        if (Factory::getApplication()->getIdentity()->authorise('whmcsbridge.sync', 'com_whmcsbridge')) {
            ToolbarHelper::custom('sync.users', 'refresh', '', 'COM_WHMCSBRIDGE_SYNC_NOW', false);
        }

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_whmcsbridge')) {
            ToolbarHelper::preferences('com_whmcsbridge');
        }
    }
}
