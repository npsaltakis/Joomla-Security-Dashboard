<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jsecdash\Administrator\Model\WafModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Application Firewall controller for com_jsecdash.
 *
 * @since  1.0.0
 */
class WafController extends BaseController
{
    /**
     * Clears the firewall log, optionally only entries older than a number of days.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function clear(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=waf', false));

            return;
        }

        $days = $this->input->post->getInt('days', 0);

        /** @var WafModel $model */
        $model = $this->getModel('Waf');

        if ($model->clearLog($days)) {
            $this->setMessage(Text::_('COM_JSECDASH_WAF_CLEARED'));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_WAF_CLEAR_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=waf', false));
    }

    /**
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Configuration array for model.
     *
     * @return  object
     *
     * @since   1.0.0
     */
    public function getModel($name = 'Waf', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
