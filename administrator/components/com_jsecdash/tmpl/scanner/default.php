<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_jsecdash
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var \Joomla\Component\Jsecdash\Administrator\View\Scanner\HtmlView $this */
$info        = $this->baselineInfo;
$results     = $this->results;
$quarantined = $this->quarantined;

$jsStrings = [
    'starting'        => Text::_('COM_JSECDASH_SCANNER_PROGRESS_STARTING'),
    'scanning'        => Text::_('COM_JSECDASH_SCANNER_PROGRESS_SCANNING'),
    'finishing'       => Text::_('COM_JSECDASH_SCANNER_PROGRESS_FINISHING'),
    'error'           => Text::_('COM_JSECDASH_SCANNER_PROGRESS_ERROR'),
    'baselineConfirm' => Text::_('COM_JSECDASH_SCANNER_BASELINE_CONFIRM'),
];
?>
<div class="jsecdash-scanner">
    <div class="card mb-3">
        <div class="card-header"><?php echo Text::_('COM_JSECDASH_SCANNER_STATUS_TITLE'); ?></div>
        <div class="card-body">
            <p>
                <?php if ($info['baseline_time']) : ?>
                    <?php echo Text::sprintf(
                        'COM_JSECDASH_SCANNER_BASELINE_INFO',
                        (int) $info['file_count'],
                        HTMLHelper::_('date', $info['baseline_time'], Text::_('DATE_FORMAT_LC4'))
                    ); ?>
                <?php else : ?>
                    <?php echo Text::_('COM_JSECDASH_SCANNER_NO_BASELINE'); ?>
                <?php endif; ?>
            </p>
            <div class="mb-2">
                <button type="button" id="jsd-baseline" class="btn btn-primary"><?php echo Text::_('COM_JSECDASH_SCANNER_BASELINE_BUTTON'); ?></button>
                <button type="button" id="jsd-scan" class="btn btn-success" <?php echo $info['baseline_time'] ? '' : 'disabled'; ?>>
                    <?php echo Text::_('COM_JSECDASH_SCANNER_SCAN_BUTTON'); ?>
                </button>
            </div>
            <div id="jsd-progress" class="d-none mb-1">
                <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="height:22px;">
                    <div id="jsd-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%">0%</div>
                </div>
                <div class="mt-2"><small id="jsd-status" class="text-muted"></small></div>
                <div><small id="jsd-current" class="text-muted font-monospace text-break"></small></div>
            </div>
            <p class="text-muted mt-2 mb-0"><small><?php echo Text::_('COM_JSECDASH_SCANNER_SCOPE_NOTE'); ?></small></p>
        </div>
    </div>

    <?php if ($results) : ?>
        <div class="card mb-3">
            <div class="card-header"><?php echo Text::_('COM_JSECDASH_SCANNER_RESULTS_TITLE'); ?></div>
            <div class="card-body">
                <p>
                    <span class="badge bg-danger"><?php echo \count($results['added']); ?> <?php echo Text::_('COM_JSECDASH_SCANNER_ADDED'); ?></span>
                    <span class="badge bg-warning text-dark"><?php echo \count($results['modified']); ?> <?php echo Text::_('COM_JSECDASH_SCANNER_MODIFIED'); ?></span>
                    <span class="badge bg-secondary"><?php echo \count($results['deleted']); ?> <?php echo Text::_('COM_JSECDASH_SCANNER_DELETED'); ?></span>
                </p>

                <?php foreach (['added' => 'COM_JSECDASH_SCANNER_ADDED', 'modified' => 'COM_JSECDASH_SCANNER_MODIFIED', 'deleted' => 'COM_JSECDASH_SCANNER_DELETED'] as $key => $label) : ?>
                    <?php if (!empty($results[$key])) : ?>
                        <h4><?php echo Text::_($label); ?></h4>
                        <ul class="list-unstyled">
                            <?php foreach ($results[$key] as $path) : ?>
                                <li class="d-flex justify-content-between align-items-center border-bottom py-1">
                                    <code><?php echo $this->escape($path); ?></code>
                                    <?php if ($key !== 'deleted') : ?>
                                        <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=scanner.quarantine'); ?>" method="post" class="ms-2"
                                              onsubmit="return confirm('<?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_CONFIRM'); ?>');">
                                            <input type="hidden" name="path" value="<?php echo $this->escape($path); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_BUTTON'); ?>
                                            </button>
                                            <?php echo HTMLHelper::_('form.token'); ?>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (empty($results['added']) && empty($results['modified']) && empty($results['deleted'])) : ?>
                    <p class="text-success mb-0"><?php echo Text::_('COM_JSECDASH_SCANNER_NO_CHANGES'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($quarantined)) : ?>
        <div class="card mb-3">
            <div class="card-header"><?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_TITLE'); ?></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_ORIGINAL'); ?></th>
                            <th><?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_TIME'); ?></th>
                            <th class="text-end"><?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quarantined as $q) : ?>
                            <tr>
                                <td><code><?php echo $this->escape($q['original']); ?></code></td>
                                <td><?php echo HTMLHelper::_('date', $q['time'], Text::_('DATE_FORMAT_LC5')); ?></td>
                                <td class="text-end">
                                    <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=scanner.restoreQuarantine'); ?>" method="post" class="d-inline">
                                        <input type="hidden" name="name" value="<?php echo $this->escape($q['file']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_RESTORE'); ?></button>
                                        <?php echo HTMLHelper::_('form.token'); ?>
                                    </form>
                                    <form action="<?php echo Route::_('index.php?option=com_jsecdash&task=scanner.deleteQuarantine'); ?>" method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_DELETE_CONFIRM'); ?>');">
                                        <input type="hidden" name="name" value="<?php echo $this->escape($q['file']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo Text::_('COM_JSECDASH_SCANNER_QUARANTINE_DELETE'); ?></button>
                                        <?php echo HTMLHelper::_('form.token'); ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var S = <?php echo json_encode($jsStrings, JSON_UNESCAPED_UNICODE); ?>;
    var csrf = (window.Joomla && Joomla.getOptions) ? Joomla.getOptions('csrf.token') : '';
    var box = document.getElementById('jsd-progress');
    var bar = document.getElementById('jsd-bar');
    var statusEl = document.getElementById('jsd-status');
    var currentEl = document.getElementById('jsd-current');
    var baselineBtn = document.getElementById('jsd-baseline');
    var scanBtn = document.getElementById('jsd-scan');

    function setBar(p) {
        bar.style.width = p + '%';
        bar.textContent = p + '%';
        bar.setAttribute('aria-valuenow', p);
    }

    function call(task, params) {
        var body = new URLSearchParams();
        if (csrf) { body.set(csrf, '1'); }
        Object.keys(params).forEach(function (k) { body.set(k, params[k]); });

        return fetch('index.php?option=com_jsecdash&task=' + task, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        });
    }

    function run(mode) {
        box.classList.remove('d-none');
        baselineBtn.disabled = true;
        if (scanBtn) { scanBtn.disabled = true; }
        bar.classList.remove('bg-danger');
        setBar(0);
        statusEl.textContent = S.starting;
        currentEl.textContent = '';

        call('scanner.start', { mode: mode }).then(function (s) {
            if (s.error) { throw new Error(s.error); }
            var token = s.token;

            function next() {
                return call('scanner.step', { token: token }).then(function (st) {
                    if (st.error) { throw new Error(st.error); }
                    var pct = st.total ? Math.round(st.processed / st.total * 100) : 100;
                    setBar(pct);
                    statusEl.textContent = st.processed + ' / ' + st.total;
                    currentEl.textContent = st.current ? (S.scanning + ' ' + st.current) : '';

                    if (st.done) {
                        setBar(100);
                        statusEl.textContent = S.finishing;
                        window.location.href = 'index.php?option=com_jsecdash&view=scanner';
                        return;
                    }

                    return next();
                });
            }

            return next();
        }).catch(function (e) {
            bar.classList.add('bg-danger');
            statusEl.textContent = S.error + ' ' + e.message;
            baselineBtn.disabled = false;
            if (scanBtn) { scanBtn.disabled = false; }
        });
    }

    if (baselineBtn) {
        baselineBtn.addEventListener('click', function () {
            if (window.confirm(S.baselineConfirm)) { run('baseline'); }
        });
    }

    if (scanBtn) {
        scanBtn.addEventListener('click', function () { run('scan'); });
    }
})();
</script>
