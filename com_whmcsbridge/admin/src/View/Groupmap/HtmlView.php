<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\View\Groupmap;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

/**
 * Groupmap edit HTML View
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var  \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * @var  object
     */
    protected $item;

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
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

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
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew = ($this->item->id == 0);

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_WHMCSBRIDGE_GROUPMAP_NEW' : 'COM_WHMCSBRIDGE_GROUPMAP_EDIT'),
            'users'
        );

        ToolbarHelper::apply('groupmap.apply');
        ToolbarHelper::save('groupmap.save');

        if ($isNew) {
            ToolbarHelper::cancel('groupmap.cancel');
        } else {
            ToolbarHelper::cancel('groupmap.cancel', 'JTOOLBAR_CLOSE');
        }
    }
}
