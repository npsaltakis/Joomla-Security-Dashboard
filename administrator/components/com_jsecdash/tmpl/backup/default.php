<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

/** @var \Joomla\Component\Jsecdash\Administrator\View\Backup\HtmlView $this */
$backups = $this->backups;

/**
 * Formats a byte count into a human readable string.
 *
 * @param   int  $bytes  Size in bytes.
 *
 * @return  string
 */
$formatSize = static function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i     = 0;

    while ($bytes >= 1024 && $i < \count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 1) . ' ' . $units[$i];
};
?>
<div class="jsecdash-backup">
    <div class="card mb-3">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_BACKUP_CREATE_TITLE'); ?></div>
        <div class="card-body">
            <p class="text-muted"><?php echo Text::_('COM_JSECDASH_BACKUP_INTRO'); ?></p>
            <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=backup.backup'); ?>" method="post"
                  onsubmit="return confirm('<?php echo Text::_('COM_JSECDASH_BACKUP_CONFIRM'); ?>');">
                <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_JSECDASH_BACKUP_CREATE_BUTTON'); ?></button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_BACKUP_LIST_TITLE'); ?></div>
        <div class="card-body p-0">
            <?php if (empty($backups)) : ?>
                <p class="text-muted m-3 mb-0"><?php echo Text::_('COM_JSECDASH_BACKUP_NONE'); ?></p>
            <?php else : ?>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_JSECDASH_BACKUP_COL_FILE'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_BACKUP_COL_SIZE'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_BACKUP_COL_DATE'); ?></th>
                            <th class="text-end"><?php echo Text::_('COM_JSECDASH_BACKUP_COL_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b) : ?>
                            <tr>
                                <td><code><?php echo $this->escape($b['file']); ?></code></td>
                                <td><?php echo $formatSize((int) $b['size']); ?></td>
                                <td><?php echo HTMLHelper::_('date', $b['time'], Text::_('DATE_FORMAT_LC5')); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?php echo Route::_('index.php?option=com_jsecdash&task=backup.download&name=' . urlencode($b['file']) . '&' . Session::getFormToken() . '=1'); ?>">
                                        <?php echo Text::_('COM_JSECDASH_BACKUP_DOWNLOAD'); ?>
                                    </a>
                                    <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=backup.delete'); ?>" method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo Text::_('COM_JSECDASH_BACKUP_DELETE_CONFIRM'); ?>');">
                                        <input type="hidden" name="name" value="<?php echo $this->escape($b['file']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo Text::_('COM_JSECDASH_BACKUP_DELETE'); ?></button>
                                        <?php echo HTMLHelper::_('form.token'); ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
