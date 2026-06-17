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

/** @var \Joomla\Component\Jsecdash\Administrator\View\Blocks\HtmlView $this */
?>
<div class="jsecdash-blocks">
    <div class="card mb-3">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_BLOCKS_ADD_TITLE'); ?></div>
        <div class="card-body">
            <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=blocks.add'); ?>" method="post" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="jsd-block-ip"><?php echo Text::_('COM_JSECDASH_FIELD_IP'); ?></label>
                    <input type="text" id="jsd-block-ip" name="ip" class="form-control" placeholder="192.168.1.1 / 10.0.0.0/24" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="jsd-block-reason"><?php echo Text::_('COM_JSECDASH_FIELD_REASON'); ?></label>
                    <input type="text" id="jsd-block-reason" name="reason" class="form-control" placeholder="<?php echo Text::_('COM_JSECDASH_FIELD_REASON_PLACEHOLDER'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="jsd-block-duration">
                        <?php echo Text::_('COM_JSECDASH_FIELD_DURATION'); ?>
                        <span class="text-muted fw-normal">(<?php echo Text::_('COM_JSECDASH_FIELD_DURATION_DESC'); ?>)</span>
                    </label>
                    <input type="number" id="jsd-block-duration" name="duration" class="form-control" value="0" min="0">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100"><?php echo Text::_('COM_JSECDASH_BLOCKS_ADD_BUTTON'); ?></button>
                </div>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_TITLE_BLOCKS'); ?></div>
        <div class="card-body">
            <?php if (empty($this->items)) : ?>
                <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_BLOCKS_NONE'); ?></p>
            <?php else : ?>
                <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=blocks.delete'); ?>" method="post">
                    <table class="table">
                        <thead>
                            <tr>
                                <th></th>
                                <th><?php echo Text::_('COM_JSECDASH_FIELD_IP'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_FIELD_REASON'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_FIELD_CREATED'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_FIELD_EXPIRES'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_FIELD_TYPE'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $item) : ?>
                                <tr>
                                    <td><input type="checkbox" name="cid[]" value="<?php echo (int) $item->id; ?>"></td>
                                    <td><?php echo $this->escape($item->ip); ?></td>
                                    <td><?php echo $this->escape($item->reason); ?></td>
                                    <td><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?></td>
                                    <td>
                                        <?php if ($item->expires) : ?>
                                            <?php echo HTMLHelper::_('date', $item->expires, Text::_('DATE_FORMAT_LC4')); ?>
                                        <?php else : ?>
                                            <span class="badge bg-danger"><?php echo Text::_('COM_JSECDASH_BLOCKS_PERMANENT'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item->auto) : ?>
                                            <span class="badge bg-warning text-dark"><?php echo Text::_('COM_JSECDASH_BLOCKS_AUTO'); ?></span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary"><?php echo Text::_('COM_JSECDASH_BLOCKS_MANUAL'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-danger"><?php echo Text::_('COM_JSECDASH_BLOCKS_DELETE_BUTTON'); ?></button>
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_DASH_TOP_IPS'); ?></div>
        <div class="card-body">
            <?php if (empty($this->attempts)) : ?>
                <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_DASH_NO_RECENT_ACTIVITY'); ?></p>
            <?php else : ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_JSECDASH_FIELD_IP'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_DASH_FAILED_ATTEMPTS_24H'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_BLOCKS_LAST_ATTEMPT'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->attempts as $row) : ?>
                            <tr>
                                <td><?php echo $this->escape($row->ip); ?></td>
                                <td><?php echo (int) $row->total; ?></td>
                                <td><?php echo HTMLHelper::_('date', $row->last_attempt, Text::_('DATE_FORMAT_LC4')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
