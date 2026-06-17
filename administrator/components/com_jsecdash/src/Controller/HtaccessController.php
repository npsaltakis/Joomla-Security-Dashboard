<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jsecdash\Administrator\Model\HtaccessModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Htaccess controller for com_jsecdash.
 *
 * @since  1.0.0
 */
class HtaccessController extends BaseController
{
    /**
     * @return  void
     *
     * @since   1.0.0
     */
    public function generate(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=htaccess', false));

            return;
        }

        $post    = $this->input->post;
        $options = [
            'disable_dir_listing'   => $post->getInt('disable_dir_listing', 0),
            'protect_config'        => $post->getInt('protect_config', 0),
            'protect_htaccess'      => $post->getInt('protect_htaccess', 0),
            'block_xmlrpc'          => $post->getInt('block_xmlrpc', 0),
            'block_sensitive_files' => $post->getInt('block_sensitive_files', 0),
            'custom_rules'          => $post->getRaw('custom_rules', ''),
        ];

        /** @var HtaccessModel $model */
        $model = $this->getModel('Htaccess');

        if ($model->generate($options)) {
            $this->setMessage(Text::_('COM_JSECDASH_HTACCESS_GENERATED'));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_HTACCESS_GENERATE_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=htaccess', false));
    }

    /**
     * @return  void
     *
     * @since   1.0.0
     */
    public function restore(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=htaccess', false));

            return;
        }

        /** @var HtaccessModel $model */
        $model = $this->getModel('Htaccess');

        if ($model->restoreLatest()) {
            $this->setMessage(Text::_('COM_JSECDASH_HTACCESS_RESTORED'));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_HTACCESS_RESTORE_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=htaccess', false));
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
    public function getModel($name = 'Htaccess', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
