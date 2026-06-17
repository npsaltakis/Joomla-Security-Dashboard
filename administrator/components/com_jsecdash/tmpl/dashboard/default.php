<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var \Joomla\Component\Jsecdash\Administrator\View\Dashboard\HtmlView $this */
$stats = $this->stats;

$cards = [
    [
        'view'  => 'healthcheck',
        'title' => 'COM_JSECDASH_NAV_HEALTHCHECK',
        'desc'  => 'COM_JSECDASH_DASH_CARD_HEALTHCHECK',
        'icon'  => 'icon-health',
        'color' => 'primary',
    ],
    [
        'view'  => 'blocks',
        'title' => 'COM_JSECDASH_NAV_BLOCKS',
        'desc'  => 'COM_JSECDASH_DASH_CARD_BLOCKS',
        'icon'  => 'icon-lock',
        'color' => 'danger',
    ],
    [
        'view'  => 'waf',
        'title' => 'COM_JSECDASH_NAV_WAF',
        'desc'  => 'COM_JSECDASH_DASH_CARD_WAF',
        'icon'  => 'icon-shield',
        'color' => 'warning',
    ],
    [
        'view'  => 'htaccess',
        'title' => 'COM_JSECDASH_NAV_HTACCESS',
        'desc'  => 'COM_JSECDASH_DASH_CARD_HTACCESS',
        'icon'  => 'icon-file',
        'color' => 'success',
    ],
    [
        'view'  => 'scanner',
        'title' => 'COM_JSECDASH_NAV_SCANNER',
        'desc'  => 'COM_JSECDASH_DASH_CARD_SCANNER',
        'icon'  => 'icon-search',
        'color' => 'info',
    ],
    [
        'view'  => 'auditlog',
        'title' => 'COM_JSECDASH_NAV_AUDITLOG',
        'desc'  => 'COM_JSECDASH_DASH_CARD_AUDITLOG',
        'icon'  => 'icon-list',
        'color' => 'secondary',
    ],
    [
        'view'  => 'backup',
        'title' => 'COM_JSECDASH_NAV_BACKUP',
        'desc'  => 'COM_JSECDASH_DASH_CARD_BACKUP',
        'icon'  => 'icon-database',
        'color' => 'dark',
    ],
];
?>
<div class="jsecdash-dashboard">
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo (int) $stats['active_blocks']; ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_DASH_ACTIVE_BLOCKS'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo (int) $stats['recent_attempts']; ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_DASH_RECENT_ATTEMPTS'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo (int) ($stats['waf_hits'] ?? 0); ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_DASH_WAF_HITS'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo (int) $stats['files_tracked']; ?></h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_DASH_FILES_TRACKED'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <h2 class="mb-0">
                        <?php if ($stats['last_baseline']) : ?>
                            <?php echo HTMLHelper::_('date', $stats['last_baseline'], Text::_('DATE_FORMAT_LC4')); ?>
                        <?php else : ?>
                            &mdash;
                        <?php endif; ?>
                    </h2>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_DASH_LAST_BASELINE'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <?php foreach ($cards as $card) : ?>
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo Route::_('index.php?option=com_jsecdash&view=' . $card['view']); ?>"
                   class="card mb-3 text-decoration-none h-100 jsecdash-nav-card">
                    <div class="card-body d-flex align-items-center">
                        <span class="badge bg-<?php echo $card['color']; ?> p-3 me-3 fs-4">
                            <span class="<?php echo $card['icon']; ?>" aria-hidden="true"></span>
                        </span>
                        <span>
                            <span class="d-block fs-5 fw-bold text-body"><?php echo Text::_($card['title']); ?></span>
                            <span class="d-block text-muted small"><?php echo Text::_($card['desc']); ?></span>
                        </span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_DASH_TOP_IPS'); ?></div>
        <div class="card-body">
            <?php if (empty($stats['top_ips'])) : ?>
                <p class="text-muted mb-0"><?php echo Text::_('COM_JSECDASH_DASH_NO_RECENT_ACTIVITY'); ?></p>
            <?php else : ?>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_JSECDASH_FIELD_IP'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_DASH_FAILED_ATTEMPTS_24H'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_ips'] as $row) : ?>
                            <tr>
                                <td><?php echo $this->escape($row->ip); ?></td>
                                <td><?php echo (int) $row->total; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($this->version) : ?>
        <p class="text-end text-muted small mt-3 mb-0">
            <span class="icon-shield-alt" aria-hidden="true"></span>
            <?php echo Text::sprintf('COM_JSECDASH_VERSION', $this->escape($this->version)); ?>
        </p>
    <?php endif; ?>
</div>
