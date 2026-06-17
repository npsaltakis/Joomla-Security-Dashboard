<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

use Joomla\CMS\Language\Text;
use Joomla\Component\Jsecdash\Administrator\Model\HealthcheckModel;

defined('_JEXEC') or die;

/** @var \Joomla\Component\Jsecdash\Administrator\View\Healthcheck\HtmlView $this */
$report = $this->report;
$score  = (int) $report['score'];
$counts = $report['counts'];

if ($score >= 80) {
    $scoreClass = 'text-success';
    $barClass   = 'bg-success';
} elseif ($score >= 50) {
    $scoreClass = 'text-warning';
    $barClass   = 'bg-warning';
} else {
    $scoreClass = 'text-danger';
    $barClass   = 'bg-danger';
}

$badge = [
    HealthcheckModel::STATUS_PASS => 'bg-success',
    HealthcheckModel::STATUS_WARN => 'bg-warning text-dark',
    HealthcheckModel::STATUS_FAIL => 'bg-danger',
];
$badgeLabel = [
    HealthcheckModel::STATUS_PASS => 'COM_JSECDASH_HEALTH_STATUS_PASS',
    HealthcheckModel::STATUS_WARN => 'COM_JSECDASH_HEALTH_STATUS_WARN',
    HealthcheckModel::STATUS_FAIL => 'COM_JSECDASH_HEALTH_STATUS_FAIL',
];
?>
<div class="jsecdash-healthcheck">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3 text-center">
                <div class="card-body">
                    <div class="display-3 fw-bold <?php echo $scoreClass; ?>"><?php echo $score; ?></div>
                    <p class="text-muted mb-2"><?php echo Text::_('COM_JSECDASH_HEALTH_SCORE'); ?></p>
                    <div class="progress" role="progressbar" aria-valuenow="<?php echo $score; ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $score; ?>%"></div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-success"><?php echo (int) $counts[HealthcheckModel::STATUS_PASS]; ?> <?php echo Text::_('COM_JSECDASH_HEALTH_STATUS_PASS'); ?></span>
                        <span class="badge bg-warning text-dark"><?php echo (int) $counts[HealthcheckModel::STATUS_WARN]; ?> <?php echo Text::_('COM_JSECDASH_HEALTH_STATUS_WARN'); ?></span>
                        <span class="badge bg-danger"><?php echo (int) $counts[HealthcheckModel::STATUS_FAIL]; ?> <?php echo Text::_('COM_JSECDASH_HEALTH_STATUS_FAIL'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><?php echo Text::_('COM_JSECDASH_HEALTH_CHECKS_TITLE'); ?></div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_JSECDASH_HEALTH_COL_CHECK'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_HEALTH_COL_VALUE'); ?></th>
                                <th><?php echo Text::_('COM_JSECDASH_HEALTH_COL_STATUS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['checks'] as $check) : ?>
                                <tr>
                                    <td>
                                        <strong><?php echo Text::_($check['label']); ?></strong>
                                        <?php if ($check['status'] !== HealthcheckModel::STATUS_PASS) : ?>
                                            <br><small class="text-muted"><?php echo Text::_($check['recommendation']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo $this->escape($check['value']); ?></code></td>
                                    <td>
                                        <span class="badge <?php echo $badge[$check['status']]; ?>">
                                            <?php echo Text::_($badgeLabel[$check['status']]); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
