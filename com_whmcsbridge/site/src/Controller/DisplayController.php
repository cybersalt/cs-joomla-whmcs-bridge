<?php

/**
 * @package     CyberSalt.Whmcsbridge
 * @subpackage  Site
 *
 * @copyright   Copyright (C) 2024 CyberSalt. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace CyberSalt\Component\Whmcsbridge\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;

/**
 * WHMCS Bridge site display controller.
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $default_view = 'products';
}
