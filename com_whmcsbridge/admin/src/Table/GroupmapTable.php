<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

\defined('_JEXEC') or die;

/**
 * Groupmap Table class
 *
 * @since  1.0.0
 */
class GroupmapTable extends Table
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   1.0.0
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__whmcsbridge_groupmaps', 'id', $db);
    }

    /**
     * Overloaded check function
     *
     * @return  boolean  True on success, false on failure
     *
     * @since   1.0.0
     */
    public function check(): bool
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Check for required fields
        if (empty($this->map_type)) {
            $this->setError('Map type is required');
            return false;
        }

        if (empty($this->whmcs_identifier)) {
            $this->setError('WHMCS identifier is required');
            return false;
        }

        if (empty($this->joomla_group_id)) {
            $this->setError('Joomla group is required');
            return false;
        }

        return true;
    }
}
