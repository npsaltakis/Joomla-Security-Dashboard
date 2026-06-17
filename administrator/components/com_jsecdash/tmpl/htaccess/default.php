<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var \Joomla\Component\Jsecdash\Administrator\View\Htaccess\HtmlView $this */
?>
<div class="jsecdash-htaccess">
    <div class="card mb-3">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_HTACCESS_SETTINGS_TITLE'); ?></div>
        <div class="card-body">
            <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=htaccess.generate'); ?>" method="post">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="disable_dir_listing" id="disable_dir_listing" value="1" checked>
                    <label class="form-check-label" for="disable_dir_listing"><?php echo Text::_('COM_JSECDASH_HTACCESS_OPT_DIR_LISTING'); ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="protect_config" id="protect_config" value="1" checked>
                    <label class="form-check-label" for="protect_config"><?php echo Text::_('COM_JSECDASH_HTACCESS_OPT_PROTECT_CONFIG'); ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="protect_htaccess" id="protect_htaccess" value="1" checked>
                    <label class="form-check-label" for="protect_htaccess"><?php echo Text::_('COM_JSECDASH_HTACCESS_OPT_PROTECT_HTACCESS'); ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="block_xmlrpc" id="block_xmlrpc" value="1" checked>
                    <label class="form-check-label" for="block_xmlrpc"><?php echo Text::_('COM_JSECDASH_HTACCESS_OPT_BLOCK_XMLRPC'); ?></label>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="block_sensitive_files" id="block_sensitive_files" value="1" checked>
                    <label class="form-check-label" for="block_sensitive_files"><?php echo Text::_('COM_JSECDASH_HTACCESS_OPT_SENSITIVE_FILES'); ?></label>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo Text::_('COM_JSECDASH_HTACCESS_OPT_CUSTOM_RULES'); ?></label>
                    <textarea name="custom_rules" class="form-control" rows="5" placeholder="# <?php echo Text::_('COM_JSECDASH_HTACCESS_OPT_CUSTOM_RULES_PLACEHOLDER'); ?>"></textarea>
                </div>
                <button type="submit" class="btn btn-success"><?php echo Text::_('COM_JSECDASH_HTACCESS_GENERATE_BUTTON'); ?></button>
                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_HTACCESS_CURRENT_TITLE'); ?></div>
        <div class="card-body">
            <?php if ($this->currentContent === '') : ?>
                <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_HTACCESS_NONE'); ?></p>
            <?php else : ?>
                <pre style="max-height:320px;overflow:auto;"><?php echo $this->escape($this->currentContent); ?></pre>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_HTACCESS_BACKUPS_TITLE'); ?></div>
        <div class="card-body">
            <?php if (empty($this->backups)) : ?>
                <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_HTACCESS_NO_BACKUPS'); ?></p>
            <?php else : ?>
                <p><?php echo Text::sprintf('COM_JSECDASH_HTACCESS_LATEST_BACKUP', $this->escape($this->backups[0])); ?></p>
                <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=htaccess.restore'); ?>" method="post"
                      onsubmit="return confirm('<?php echo Text::_('COM_JSECDASH_HTACCESS_RESTORE_CONFIRM'); ?>');">
                    <button type="submit" class="btn btn-warning"><?php echo Text::_('COM_JSECDASH_HTACCESS_RESTORE_BUTTON'); ?></button>
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
