<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default controller for com_jsecdash.
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * @var    string
     * @since  1.0.0
     */
    protected $default_view = 'dashboard';
}
