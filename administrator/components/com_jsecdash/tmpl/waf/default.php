<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var \Joomla\Component\Jsecdash\Administrator\View\Waf\HtmlView $this */
$mode       = $this->mode;
$summary    = $this->summary;
$categories = $this->categories;
$topRules   = $this->topRules;
$items      = $this->items;

$categoryLabels = [
    'sqli'    => 'COM_JSECDASH_WAF_CAT_SQLI',
    'xss'     => 'COM_JSECDASH_WAF_CAT_XSS',
    'lfi'     => 'COM_JSECDASH_WAF_CAT_LFI',
    'cmdi'    => 'COM_JSECDASH_WAF_CAT_CMDI',
    'scanner' => 'COM_JSECDASH_WAF_CAT_SCANNER',
    'exploit' => 'COM_JSECDASH_WAF_CAT_EXPLOIT',
    'custom'  => 'COM_JSECDASH_WAF_CAT_CUSTOM',
];

$catLabel = static function (string $key) use ($categoryLabels): string {
    return isset($categoryLabels[$key]) ? Text::_($categoryLabels[$key]) : $key;
};

$modeInfo = [
    'off'    => ['bg-secondary', 'COM_JSECDASH_WAF_MODE_OFF'],
    'detect' => ['bg-warning text-dark', 'COM_JSECDASH_WAF_MODE_DETECT'],
    'block'  => ['bg-success', 'COM_JSECDASH_WAF_MODE_BLOCK'],
];
[$modeClass, $modeKey] = $modeInfo[$mode] ?? $modeInfo['detect'];
?>
<div class="jsecdash-waf">
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <?php echo Text::_('COM_JSECDASH_WAF_MODE_LABEL'); ?>:
                <span class="badge <?php echo $modeClass; ?>"><?php echo Text::_($modeKey); ?></span>
                <?php if ($mode === 'off') : ?>
                    <span class="text-muted ms-2"><?php echo Text::_('COM_JSECDASH_WAF_MODE_OFF_NOTE'); ?></span>
                <?php elseif ($mode === 'detect') : ?>
                    <span class="text-muted ms-2"><?php echo Text::_('COM_JSECDASH_WAF_MODE_DETECT_NOTE'); ?></span>
                <?php endif; ?>
            </div>
            <a class="btn btn-sm btn-outline-secondary"
               href="<?php echo Route::_('index.php?option=com_plugins&view=plugins&filter[search]=Security+Dashboard&filter[folder]=system'); ?>">
                <?php echo Text::_('COM_JSECDASH_WAF_CONFIGURE'); ?>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo (int) $summary['total']; ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_WAF_STAT_TOTAL'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0 text-danger"><?php echo (int) $summary['blocked']; ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_WAF_STAT_BLOCKED'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0 text-warning"><?php echo (int) $summary['detected']; ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_WAF_STAT_DETECTED'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo (int) $summary['ips']; ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_WAF_STAT_IPS'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><?php echo Text::_('COM_JSECDASH_WAF_BREAKDOWN_TITLE'); ?></div>
                <div class="card-body p-0">
                    <?php if (empty($categories)) : ?>
                        <p class="text-muted m-3 mb-0"><?php echo Text::_('COM_JSECDASH_WAF_NO_DATA'); ?></p>
                    <?php else : ?>
                        <table class="table mb-0">
                            <tbody>
                                <?php foreach ($categories as $cat) : ?>
                                    <tr>
                                        <td><?php echo $this->escape($catLabel($cat->category)); ?></td>
                                        <td class="text-end"><span class="badge bg-info"><?php echo (int) $cat->total; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><?php echo Text::_('COM_JSECDASH_WAF_TOP_RULES_TITLE'); ?></div>
                <div class="card-body p-0">
                    <?php if (empty($topRules)) : ?>
                        <p class="text-muted m-3 mb-0"><?php echo Text::_('COM_JSECDASH_WAF_NO_DATA'); ?></p>
                    <?php else : ?>
                        <table class="table mb-0">
                            <tbody>
                                <?php foreach ($topRules as $rule) : ?>
                                    <tr>
                                        <td><code><?php echo $this->escape($rule->rule_id); ?></code></td>
                                        <td><?php echo $this->escape($catLabel($rule->category)); ?></td>
                                        <td class="text-end"><span class="badge bg-info"><?php echo (int) $rule->total; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><?php echo Text::_('COM_JSECDASH_WAF_EVENTS_TITLE'); ?></span>
            <?php if (!empty($items)) : ?>
                <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=waf.clear'); ?>" method="post" class="d-inline"
                      onsubmit="return confirm('<?php echo Text::_('COM_JSECDASH_WAF_CLEAR_CONFIRM'); ?>');">
                    <input type="hidden" name="days" value="0">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo Text::_('COM_JSECDASH_WAF_CLEAR_BUTTON'); ?></button>
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($items)) : ?>
                <p class="text-muted m-3 mb-0"><?php echo Text::_('COM_JSECDASH_WAF_NO_EVENTS'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_JSECDASH_WAF_COL_DATE'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_WAF_COL_IP'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_WAF_COL_CATEGORY'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_WAF_COL_RULE'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_WAF_COL_FIELD'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_WAF_COL_PAYLOAD'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_WAF_COL_ACTION'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item) : ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?></td>
                                    <td class="text-nowrap"><?php echo $this->escape($item->ip); ?></td>
                                    <td><?php echo $this->escape($catLabel($item->category)); ?></td>
                                    <td><code><?php echo $this->escape($item->rule_id); ?></code></td>
                                    <td><code><?php echo $this->escape($item->matched_field); ?></code></td>
                                    <td><code class="text-danger" style="word-break:break-all;"><?php echo $this->escape($item->payload); ?></code></td>
                                    <td>
                                        <?php if ($item->action === 'blocked') : ?>
                                            <span class="badge bg-danger"><?php echo Text::_('COM_JSECDASH_WAF_ACTION_BLOCKED'); ?></span>
                                        <?php else : ?>
                                            <span class="badge bg-warning text-dark"><?php echo Text::_('COM_JSECDASH_WAF_ACTION_DETECTED'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
