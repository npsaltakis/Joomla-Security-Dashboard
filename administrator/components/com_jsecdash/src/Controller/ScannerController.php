<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Jsecdash\Administrator\Model\ScannerModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Scanner controller for com_jsecdash.
 *
 * @since  1.0.0
 */
class ScannerController extends BaseController
{
    /**
     * @return  void
     *
     * @since   1.0.0
     */
    public function baseline(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));

            return;
        }

        /** @var ScannerModel $model */
        $model = $this->getModel('Scanner');
        $count = $model->baseline();

        $this->app->setUserState('com_jsecdash.scanner.results', null);
        $this->setMessage(Text::sprintf('COM_JSECDASH_SCANNER_BASELINE_DONE', $count));
        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));
    }

    /**
     * @return  void
     *
     * @since   1.0.0
     */
    public function scan(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));

            return;
        }

        /** @var ScannerModel $model */
        $model   = $this->getModel('Scanner');
        $results = $model->scan();

        $this->app->setUserState('com_jsecdash.scanner.results', $results);
        $this->setMessage(Text::_('COM_JSECDASH_SCANNER_SCAN_DONE'));
        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));
    }

    /**
     * Moves a flagged file into quarantine.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function quarantine(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));

            return;
        }

        $path = $this->input->getString('path', '');

        /** @var ScannerModel $model */
        $model = $this->getModel('Scanner');

        if ($model->quarantineFile($path)) {
            $this->setMessage(Text::sprintf('COM_JSECDASH_SCANNER_QUARANTINE_DONE', $path));
        } else {
            $this->setMessage(Text::sprintf('COM_JSECDASH_SCANNER_QUARANTINE_FAILED', $path), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));
    }

    /**
     * Restores a quarantined file to its original location.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function restoreQuarantine(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));

            return;
        }

        $name = $this->input->getString('name', '');

        /** @var ScannerModel $model */
        $model = $this->getModel('Scanner');

        if ($model->restoreQuarantined($name)) {
            $this->setMessage(Text::_('COM_JSECDASH_SCANNER_RESTORE_DONE'));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_SCANNER_RESTORE_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));
    }

    /**
     * Permanently deletes a quarantined file.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function deleteQuarantine(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));

            return;
        }

        $name = $this->input->getString('name', '');

        /** @var ScannerModel $model */
        $model = $this->getModel('Scanner');

        if ($model->deleteQuarantined($name)) {
            $this->setMessage(Text::_('COM_JSECDASH_SCANNER_QDELETE_DONE'));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_SCANNER_QDELETE_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=scanner', false));
    }

    /**
     * AJAX: starts a chunked baseline or scan job and returns {token, total}.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function start(): void
    {
        if (!Session::checkToken('post')) {
            $this->jsonResponse(['error' => Text::_('JINVALID_TOKEN_NOTICE')]);

            return;
        }

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->jsonResponse(['error' => Text::_('JERROR_ALERTNOAUTHOR')]);

            return;
        }

        $mode = $this->input->post->getCmd('mode', '');

        if (!\in_array($mode, ['baseline', 'scan'], true)) {
            $this->jsonResponse(['error' => 'Invalid mode.']);

            return;
        }

        /** @var ScannerModel $model */
        $model = $this->getModel('Scanner');

        if ($mode === 'scan') {
            $info = $model->getBaselineInfo();

            if (empty($info['baseline_time'])) {
                $this->jsonResponse(['error' => Text::_('COM_JSECDASH_SCANNER_NO_BASELINE')]);

                return;
            }
        }

        $this->jsonResponse($model->startJob($mode));
    }

    /**
     * AJAX: processes the next batch of a running job and returns progress.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function step(): void
    {
        if (!Session::checkToken('post')) {
            $this->jsonResponse(['error' => Text::_('JINVALID_TOKEN_NOTICE')]);

            return;
        }

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->jsonResponse(['error' => Text::_('JERROR_ALERTNOAUTHOR')]);

            return;
        }

        /** @var ScannerModel $model */
        $model = $this->getModel('Scanner');

        $this->jsonResponse($model->stepJob($this->input->post->getCmd('token', '')));
    }

    /**
     * Sends a JSON payload and terminates the application.
     *
     * @param   array  $data  The data to encode.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function jsonResponse(array $data): void
    {
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->sendHeaders();
        echo json_encode($data);
        $this->app->close();
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
    public function getModel($name = 'Scanner', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
