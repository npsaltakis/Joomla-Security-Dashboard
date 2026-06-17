<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jsecdash\Administrator\Model\BlocksModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Blocks controller for com_jsecdash.
 *
 * @since  1.0.0
 */
class BlocksController extends BaseController
{
    /**
     * @return  void
     *
     * @since   1.0.0
     */
    public function add(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=blocks', false));

            return;
        }

        $ip       = $this->input->post->getString('ip', '');
        $reason   = $this->input->post->getString('reason', 'Manual block');
        $duration = $this->input->post->getInt('duration', 0);

        /** @var BlocksModel $model */
        $model = $this->getModel('Blocks');

        if ($model->addBlock($ip, $reason, $duration)) {
            $this->setMessage(Text::sprintf('COM_JSECDASH_BLOCKS_ADDED', $ip));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_BLOCKS_INVALID_IP'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=blocks', false));
    }

    /**
     * @return  void
     *
     * @since   1.0.0
     */
    public function delete(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=blocks', false));

            return;
        }

        $ids = $this->input->post->get('cid', [], 'array');

        /** @var BlocksModel $model */
        $model = $this->getModel('Blocks');
        $model->deleteBlocks($ids);

        $this->setMessage(Text::_('COM_JSECDASH_BLOCKS_DELETED'));
        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=blocks', false));
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
    public function getModel($name = 'Blocks', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
