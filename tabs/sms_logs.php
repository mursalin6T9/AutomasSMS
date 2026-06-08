<?php

/**
 * Automas SMS - SMS Logs tab.
 *
 * Automas returns a final per-recipient status code on send and has no delivery
 * report endpoint, so nothing here polls the gateway - what was written at send
 * time is what is shown.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use AutomasSMS\MessageLog;
use AutomasSMS\Registry;

$notices = [];

if (!empty($_POST['automassms_action'])) {
    try {
        AutomasSMS_check_token();

        if ($_POST['automassms_action'] === 'delete' && !empty($_POST['id'])) {
            MessageLog::delete((int) $_POST['id']);
            $notices[] = ['success', 'Log entry deleted.'];
        } elseif ($_POST['automassms_action'] === 'clear') {
            $removed = MessageLog::truncate();
            $notices[] = ['success', $removed . ' log entries deleted.'];
        }
    } catch (\Throwable $e) {
        $notices[] = ['danger', $e->getMessage()];
    }
}

$filters = [
    'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
    'source' => isset($_GET['source']) ? (string) $_GET['source'] : '',
    'search' => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
];

if (!in_array($filters['status'], ['', 'sent', 'failed'], true)) {
    $filters['status'] = '';
}

$perPage = isset($_GET['limit']) ? (int) $_GET['limit'] : 25;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

$result = MessageLog::paginate($filters, $page, $perPage);
$summary = AutomasSMS_summary();

/** Preserve the active filters when building pagination links. */
$queryFor = function (array $overrides) use ($moduleLink, $filters, $perPage) {
    $params = array_filter([
        'status' => $filters['status'],
        'source' => $filters['source'],
        'q'      => $filters['search'],
        'limit'  => $perPage,
    ], function ($value) {
        return $value !== '' && $value !== null;
    });

    return $moduleLink . '&tab=sms_logs&' . http_build_query(array_merge($params, $overrides));
};

include AUTOMASSMS_PATH . '/partials/header.php';

?>

<div class="automassms-panel">
    <?php foreach ($notices as $notice) : ?>
        <?php AutomasSMS_alert($notice[1], $notice[0]); ?>
    <?php endforeach; ?>

    <div class="automassms-stats">
        <div class="automassms-stat-card">
            <span>Sent (30 days)</span>
            <strong><?= (int) $summary['sent']; ?></strong>
        </div>
        <div class="automassms-stat-card">
            <span>Failed (30 days)</span>
            <strong><?= (int) $summary['failed']; ?></strong>
        </div>
        <div class="automassms-stat-card">
            <span>Segments billed</span>
            <strong><?= (int) $summary['parts']; ?></strong>
        </div>
        <div class="automassms-stat-card">
            <span>Rows stored</span>
            <strong><?= (int) $result['total']; ?></strong>
        </div>
    </div>

    <form method="get" action="addonmodules.php" class="automassms-toolbar">
        <input type="hidden" name="module" value="<?= AutomasSMS_e(AUTOMASSMS_MODULE); ?>">
        <input type="hidden" name="tab" value="sms_logs">

        <div class="automassms-toolbar-field">
            <label for="automassms-filter-q">Search</label>
            <input type="search" id="automassms-filter-q" name="q"
                   value="<?= AutomasSMS_e($filters['search']); ?>" placeholder="Number, name or text">
        </div>

        <div class="automassms-toolbar-field">
            <label for="automassms-filter-status">Status</label>
            <select id="automassms-filter-status" name="status">
                <option value="">All</option>
                <option value="sent" <?= $filters['status'] === 'sent' ? 'selected' : ''; ?>>Sent</option>
                <option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>

        <div class="automassms-toolbar-field">
            <label for="automassms-filter-source">Notification</label>
            <select id="automassms-filter-source" name="source">
                <option value="">All</option>
                <?php foreach (MessageLog::sources() as $source) : ?>
                    <option value="<?= AutomasSMS_e($source); ?>" <?= $filters['source'] === $source ? 'selected' : ''; ?>>
                        <?= AutomasSMS_e($source === 'manual' ? 'Manual send' : Registry::label($source)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="automassms-toolbar-field">
            <label for="automassms-filter-limit">Per page</label>
            <select id="automassms-filter-limit" name="limit">
                <?php foreach ([25, 50, 100, 200] as $option) : ?>
                    <option value="<?= $option; ?>" <?= $perPage === $option ? 'selected' : ''; ?>><?= $option; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="automassms-btn automassms-btn-secondary">
            <i class="fas fa-filter"></i> Filter
        </button>

        <div class="automassms-toolbar-spacer"></div>
    </form>

    <div class="automassms-table-wrap">
        <table class="automassms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>When</th>
                    <th>Client</th>
                    <th>Recipient</th>
                    <th>Message</th>
                    <th>Notification</th>
                    <th>Status</th>
                    <th>Detail</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$result['rows']) : ?>
                    <tr>
                        <td colspan="9">
                            <div class="automassms-empty">
                                <h3>No messages yet</h3>
                                <p>Sent and failed messages will be listed here.</p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($result['rows'] as $row) : ?>
                        <tr>
                            <td class="automassms-nowrap"><?= (int) $row->id; ?></td>
                            <td class="automassms-nowrap"><?= AutomasSMS_e(date('d M Y, H:i', strtotime($row->created_at))); ?></td>
                            <td>
                                <?php if (!empty($row->uid)) : ?>
                                    <a href="clientssummary.php?userid=<?= (int) $row->uid; ?>">
                                        <?= AutomasSMS_e(trim($row->firstname . ' ' . $row->lastname)); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="automassms-badge automassms-badge-neutral">No client</span>
                                <?php endif; ?>
                            </td>
                            <td class="automassms-nowrap automassms-mono"><?= AutomasSMS_e($row->recipient); ?></td>
                            <td class="automassms-message-cell">
                                <?= AutomasSMS_e($row->message); ?>
                                <div class="automassms-hint">
                                    <?= AutomasSMS_e($row->encoding === 'unicode' ? 'Unicode' : 'GSM'); ?>
                                    &middot; <?= (int) $row->parts; ?> segment(s)
                                    <?php if (!empty($row->sms_id)) : ?>
                                        &middot; id <?= AutomasSMS_e($row->sms_id); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="automassms-nowrap">
                                <?= AutomasSMS_e($row->source === 'manual' ? 'Manual send' : Registry::label($row->source)); ?>
                            </td>
                            <td class="automassms-nowrap">
                                <span class="automassms-badge automassms-badge-<?= $row->status === 'sent' ? 'sent' : 'failed'; ?>">
                                    <?= AutomasSMS_e($row->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int) $row->status_code !== 0) : ?>
                                    <span class="automassms-mono"><?= (int) $row->status_code; ?></span>
                                    &middot; <?= AutomasSMS_e($row->status_text); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= AutomasSMS_e($moduleLink . '&tab=sms_logs'); ?>"
                                      onsubmit="return confirm('Delete this log entry?');">
                                    <?= AutomasSMS_token_field(); ?>
                                    <input type="hidden" name="automassms_action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $row->id; ?>">
                                    <button type="submit" class="automassms-btn-link" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="automassms-pager">
        <div>
            Page <?= (int) $result['page']; ?> of <?= (int) $result['pages']; ?>
            &middot; <?= (int) $result['total']; ?> message(s)
        </div>

        <?php if ($result['pages'] > 1) : ?>
            <div class="automassms-pager-links">
                <?php
                $current = (int) $result['page'];
                $last = (int) $result['pages'];
                $from = max(1, $current - 3);
                $to = min($last, $current + 3);
                ?>

                <?php if ($current > 1) : ?>
                    <a href="<?= AutomasSMS_e($queryFor(['page' => $current - 1])); ?>">&laquo;</a>
                <?php endif; ?>

                <?php if ($from > 1) : ?>
                    <a href="<?= AutomasSMS_e($queryFor(['page' => 1])); ?>">1</a>
                    <?php if ($from > 2) : ?><span>&hellip;</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $from; $i <= $to; $i++) : ?>
                    <?php if ($i === $current) : ?>
                        <span class="is-current"><?= $i; ?></span>
                    <?php else : ?>
                        <a href="<?= AutomasSMS_e($queryFor(['page' => $i])); ?>"><?= $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($to < $last) : ?>
                    <?php if ($to < $last - 1) : ?><span>&hellip;</span><?php endif; ?>
                    <a href="<?= AutomasSMS_e($queryFor(['page' => $last])); ?>"><?= $last; ?></a>
                <?php endif; ?>

                <?php if ($current < $last) : ?>
                    <a href="<?= AutomasSMS_e($queryFor(['page' => $current + 1])); ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($result['total'] > 0) : ?>
        <div class="automassms-actions">
            <form method="post" action="<?= AutomasSMS_e($moduleLink . '&tab=sms_logs'); ?>"
                  onsubmit="return confirm('Delete every message log entry? This cannot be undone.');">
                <?= AutomasSMS_token_field(); ?>
                <input type="hidden" name="automassms_action" value="clear">
                <button type="submit" class="automassms-btn automassms-btn-danger">
                    <i class="fas fa-trash"></i> Clear All Logs
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include AUTOMASSMS_PATH . '/partials/footer.php'; ?>
