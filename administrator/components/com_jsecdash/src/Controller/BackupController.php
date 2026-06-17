<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

namespace Joomla\Component\Jsecdash\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jsecdash\Administrator\Model\BackupModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Database backup controller for com_jsecdash.
 *
 * @since  1.0.0
 */
class BackupController extends BaseController
{
    /**
     * Generates a new database backup.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function backup(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=backup', false));

            return;
        }

        /** @var BackupModel $model */
        $model  = $this->getModel('Backup');
        $result = $model->createBackup();

        if ($result !== false) {
            $this->setMessage(Text::sprintf('COM_JSECDASH_BACKUP_DONE', $result));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_BACKUP_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=backup', false));
    }

    /**
     * Streams a backup file to the browser for download.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function download(): void
    {
        $this->checkToken('get');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=backup', false));

            return;
        }

        $name = $this->input->getString('name', '');

        /** @var BackupModel $model */
        $model = $this->getModel('Backup');
        $path  = $model->getBackupPath($name);

        if ($path === false) {
            $this->setMessage(Text::_('COM_JSECDASH_BACKUP_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=backup', false));

            return;
        }

        $this->app->setHeader('Content-Type', 'application/sql', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="' . basename($path) . '"', true);
        $this->app->setHeader('Content-Length', (string) filesize($path), true);
        $this->app->sendHeaders();

        readfile($path);

        $this->app->close();
    }

    /**
     * Deletes a backup file.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function delete(): void
    {
        $this->checkToken('post');

        if (!$this->app->getIdentity()->authorise('core.manage', 'com_jsecdash')) {
            $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=backup', false));

            return;
        }

        $name = $this->input->getString('name', '');

        /** @var BackupModel $model */
        $model = $this->getModel('Backup');

        if ($model->deleteBackup($name)) {
            $this->setMessage(Text::_('COM_JSECDASH_BACKUP_DELETED'));
        } else {
            $this->setMessage(Text::_('COM_JSECDASH_BACKUP_DELETE_FAILED'), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_jsecdash&view=backup', false));
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
    public function getModel($name = 'Backup', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
