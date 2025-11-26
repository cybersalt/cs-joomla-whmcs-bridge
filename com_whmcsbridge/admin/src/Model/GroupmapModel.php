<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Model;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;

\defined('_JEXEC') or die;

/**
 * Groupmap model for editing a single mapping
 *
 * @since  1.0.0
 */
class GroupmapModel extends AdminModel
{
    /**
     * The type alias for this content type
     *
     * @var    string
     * @since  1.0.0
     */
    public $typeAlias = 'com_whmcsbridge.groupmap';

    /**
     * Method to get the record form
     *
     * @param   array    $data      Data for the form
     * @param   boolean  $loadData  True if the form is to load its own data
     *
     * @return  Form|boolean
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_whmcsbridge.groupmap',
            'groupmap',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form
     *
     * @return  mixed  The data for the form
     *
     * @since   1.0.0
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState(
            'com_whmcsbridge.edit.groupmap.data',
            []
        );

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to get a table object
     *
     * @param   string  $name     The table name
     * @param   string  $prefix   The class prefix
     * @param   array   $options  Configuration array for model
     *
     * @return  Table
     *
     * @since   1.0.0
     */
    public function getTable($name = 'Groupmap', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Prepare and sanitize the table data prior to saving
     *
     * @param   Table  $table  The Table object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function prepareTable($table): void
    {
        $date = Factory::getDate()->toSql();

        if (empty($table->id)) {
            $table->created = $date;
        }

        $table->modified = $date;
    }
}
