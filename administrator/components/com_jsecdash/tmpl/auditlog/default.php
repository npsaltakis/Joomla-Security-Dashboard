<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Actionlogs\Administrator\Helper\ActionlogsHelper;

defined('_JEXEC') or die;

/** @var \Joomla\Component\Jsecdash\Administrator\View\Auditlog\HtmlView $this */
?>
<div class="jsecdash-auditlog">
    <?php if (!$this->loggingEnabled) : ?>
        <div class="alert alert-warning">
            <?php echo Text::_('COM_JSECDASH_AUDITLOG_DISABLED'); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_AUDITLOG_TITLE'); ?></div>
        <div class="card-body p-0">
            <?php if (empty($this->items)) : ?>
                <p class="text-muted m-3 mb-0"><?php echo Text::_('COM_JSECDASH_AUDITLOG_NONE'); ?></p>
            <?php else : ?>
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_JSECDASH_AUDITLOG_COL_DATE'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_AUDITLOG_COL_MESSAGE'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_AUDITLOG_COL_USER'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_AUDITLOG_COL_IP'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->items as $item) : ?>
                            <tr>
                                <td><?php echo HTMLHelper::_('date', $item->log_date, Text::_('DATE_FORMAT_LC5')); ?></td>
                                <td>
                                    <?php
                                    $message = '';

                                    if (class_exists(ActionlogsHelper::class) && $item->message_language_key) {
                                        try {
                                            $message = ActionlogsHelper::getHumanReadableLogMessage($item, false);
                                        } catch (\Throwable $e) {
                                            $message = '';
                                        }
                                    }

                                    if ($message === '') {
                                        $message = $this->escape((string) $item->message_language_key);
                                    }

                                    echo $message;
                                    ?>
                                </td>
                                <td><?php echo $this->escape((string) ($item->user_name ?: '—')); ?></td>
                                <td><code><?php echo $this->escape((string) $item->ip_address); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
