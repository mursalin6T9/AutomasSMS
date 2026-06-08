<?php

/**
 * Automas SMS - Send SMS tab.
 *
 * Broadcasts are personalised per client and sent through smsapimany, so a
 * mail-merge to every client is a single HTTP request rather than one request
 * per recipient.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use AutomasSMS\Phone;
use AutomasSMS\Sender;
use AutomasSMS\Settings;
use AutomasSMS\Text;
use WHMCS\Database\Capsule;

$notices = [];
$form = [
    'audience'  => 'client',
    'userid'    => '',
    'numbers'   => '',
    'message'   => '',
    'schedule'  => '',
];

/** Tokens offered for a manual send. */
$manualTokens = ['{firstname}', '{lastname}', '{company}', '{email}'];

if (!empty($_POST['automassms_action']) && $_POST['automassms_action'] === 'send') {
    $form['audience'] = isset($_POST['audience']) ? (string) $_POST['audience'] : 'client';
    $form['userid'] = isset($_POST['userid']) ? trim((string) $_POST['userid']) : '';
    $form['numbers'] = isset($_POST['numbers']) ? trim((string) $_POST['numbers']) : '';
    $form['message'] = isset($_POST['message']) ? (string) $_POST['message'] : '';
    $form['schedule'] = isset($_POST['schedule']) ? trim((string) $_POST['schedule']) : '';

    try {
        AutomasSMS_check_token();

        if (!Settings::isConfigured()) {
            throw new \Exception('No API key is configured. Add one on the Settings tab first.');
        }

        if (trim($form['message']) === '') {
            throw new \Exception('The message is empty.');
        }

        // Optional scheduling. Automas takes "Y-m-d H:i:s" in Dhaka local time.
        $schedule = null;

        if ($form['schedule'] !== '') {
            $when = \DateTime::createFromFormat('Y-m-d\TH:i', $form['schedule'], new \DateTimeZone(AUTOMASSMS_TIMEZONE));

            if (!$when) {
                throw new \Exception('The scheduled time could not be read. Use the date and time picker.');
            }

            $now = new \DateTime('now', new \DateTimeZone(AUTOMASSMS_TIMEZONE));

            if ($when <= $now) {
                throw new \Exception('The scheduled time must be in the future (Asia/Dhaka time).');
            }

            $schedule = $when->format('Y-m-d H:i:s');
        }

        $company = (new \AutomasSMS\Context())->company();
        $jobs = [];
        $skipped = 0;

        if ($form['audience'] === 'numbers') {
            $parsed = Phone::normaliseList($form['numbers']);

            if ($parsed['invalid']) {
                $notices[] = ['warning', 'Skipped invalid number(s): ' . implode(', ', $parsed['invalid'])];
            }

            $body = Text::render($form['message'], [
                '{firstname}' => '',
                '{lastname}'  => '',
                '{email}'     => '',
                '{company}'   => $company,
            ]);

            foreach ($parsed['valid'] as $msisdn) {
                $jobs[] = ['msisdn' => $msisdn, 'text' => $body, 'client_id' => 0];
            }
        } else {
            $query = Capsule::table('tblclients')->select('id', 'firstname', 'lastname', 'email', 'phonenumber');

            if ($form['audience'] === 'client') {
                if ($form['userid'] === '' || (int) $form['userid'] <= 0) {
                    throw new \Exception('Select a client to send to.');
                }

                $query->where('id', (int) $form['userid']);
            } elseif ($form['audience'] === 'active') {
                $query->where('status', 'Active');
            }

            foreach ($query->get() as $client) {
                $msisdn = Phone::normalise($client->phonenumber);

                if ($msisdn === null) {
                    $skipped++;
                    continue;
                }

                $jobs[] = [
                    'msisdn'    => $msisdn,
                    'client_id' => $client->id,
                    'text'      => Text::render($form['message'], [
                        '{firstname}' => $client->firstname,
                        '{lastname}'  => $client->lastname,
                        '{email}'     => $client->email,
                        '{company}'   => $company,
                    ]),
                ];
            }
        }

        if (!$jobs) {
            throw new \Exception('No valid recipients were found for this selection.');
        }

        $summary = Sender::dispatch($jobs, 'manual', $schedule);
        Settings::forgetBalance();

        if ($summary['sent'] > 0) {
            $notices[] = ['success', $summary['sent'] . ' message(s) accepted by Automas'
                . ($schedule !== null ? ' and scheduled for ' . $schedule . ' (Asia/Dhaka)' : '')
                . '. See SMS Logs for per-recipient results.'];

            $form['message'] = '';
            $form['numbers'] = '';
        }

        if ($summary['failed'] > 0) {
            $notices[] = ['danger', $summary['failed'] . ' message(s) failed: '
                . implode(' | ', array_slice($summary['errors'], 0, 5))];
        }

        if ($skipped > 0) {
            $notices[] = ['warning', $skipped . ' client(s) skipped - no valid Bangladeshi mobile number on file.'];
        }
    } catch (\Throwable $e) {
        $notices[] = ['danger', $e->getMessage()];
    }
}

$reachable = 0;
$totalClients = 0;

try {
    foreach (Capsule::table('tblclients')->select('phonenumber')->get() as $row) {
        $totalClients++;

        if (Phone::isValid($row->phonenumber)) {
            $reachable++;
        }
    }
} catch (\Throwable $e) {
    // Counts are informational only.
}

include AUTOMASSMS_PATH . '/partials/header.php';

?>

<div class="automassms-panel">
    <?php foreach ($notices as $notice) : ?>
        <?php AutomasSMS_alert($notice[1], $notice[0]); ?>
    <?php endforeach; ?>

    <?php if (!Settings::isConfigured()) : ?>
        <div class="automassms-alert automassms-alert-warning">
            No API key is configured. Add one on the Settings tab before sending.
        </div>
    <?php endif; ?>

    <div class="automassms-stats">
        <div class="automassms-stat-card">
            <span>Clients</span>
            <strong><?= (int) $totalClients; ?></strong>
        </div>
        <div class="automassms-stat-card">
            <span>Reachable by SMS</span>
            <strong><?= (int) $reachable; ?></strong>
        </div>
        <div class="automassms-stat-card">
            <span>No valid mobile</span>
            <strong><?= (int) ($totalClients - $reachable); ?></strong>
        </div>
    </div>

    <form method="post" action="<?= AutomasSMS_e($moduleLink . '&tab=send_sms'); ?>">
        <?= AutomasSMS_token_field(); ?>
        <input type="hidden" name="automassms_action" value="send">

        <div class="automassms-section">
            <h4>Recipients</h4>
            <div class="automassms-section-body">

                <div class="automassms-field">
                    <label for="automassms-audience">Send to</label>
                    <div class="automassms-field-control">
                        <select id="automassms-audience" name="audience">
                            <option value="client" <?= $form['audience'] === 'client' ? 'selected' : ''; ?>>A single client</option>
                            <option value="active" <?= $form['audience'] === 'active' ? 'selected' : ''; ?>>All active clients</option>
                            <option value="all" <?= $form['audience'] === 'all' ? 'selected' : ''; ?>>All clients</option>
                            <option value="numbers" <?= $form['audience'] === 'numbers' ? 'selected' : ''; ?>>Specific numbers</option>
                        </select>
                        <span class="automassms-hint">
                            Broadcasts personalise each message and go out in one batched request.
                        </span>
                    </div>
                </div>

                <div class="automassms-field" data-automassms-audience="client">
                    <label for="selectUserid">Client</label>
                    <div class="automassms-field-control">
                        <select id="selectUserid" name="userid"
                                class="form-control selectize selectize-client-search"
                                data-value-field="id" data-active-label="Active" data-inactive-label="Inactive"
                                placeholder="Start typing to search clients">
                            <option value="">Select a client</option>
                        </select>
                    </div>
                </div>

                <div class="automassms-field" data-automassms-audience="numbers">
                    <label for="automassms-numbers">Mobile numbers</label>
                    <div class="automassms-field-control">
                        <textarea id="automassms-numbers" name="numbers"
                                  placeholder="01XXXXXXXXX, 01XXXXXXXXX"><?= AutomasSMS_e($form['numbers']); ?></textarea>
                        <span class="automassms-hint">
                            Comma, space or newline separated. Duplicates are collapsed automatically.
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="automassms-section">
            <h4>Message</h4>
            <div class="automassms-section-body">

                <div class="automassms-field">
                    <label for="automassms-message">Body</label>
                    <div class="automassms-field-control">
                        <div class="automassms-tokens">
                            <?php foreach ($manualTokens as $token) : ?>
                                <span class="automassms-token" data-automassms-token="<?= AutomasSMS_e($token); ?>"
                                      data-automassms-target="automassms-message"><?= AutomasSMS_e($token); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <textarea id="automassms-message" name="message" rows="5" required
                                  data-automassms-counter="automassms-message-counter"><?= AutomasSMS_e($form['message']); ?></textarea>

                        <div class="automassms-counter" id="automassms-message-counter"></div>

                        <span class="automassms-hint">
                            Bangla is sent as Unicode, which fits 70 characters per segment instead of 160.
                            Tokens are blank when sending to specific numbers.
                        </span>
                    </div>
                </div>

                <div class="automassms-field">
                    <label for="automassms-schedule">Schedule (optional)</label>
                    <div class="automassms-field-control">
                        <input type="datetime-local" id="automassms-schedule" name="schedule"
                               value="<?= AutomasSMS_e($form['schedule']); ?>">
                        <span class="automassms-hint">
                            Leave blank to send immediately. Times are Asia/Dhaka, matching the gateway.
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="automassms-actions">
            <button type="submit" class="automassms-btn">
                <i class="fas fa-paper-plane"></i> Send
            </button>
            <span class="automassms-hint" style="margin:0;">Sending charges your Automas balance.</span>
        </div>
    </form>
</div>

<?php include AUTOMASSMS_PATH . '/partials/footer.php'; ?>
<?php include AUTOMASSMS_PATH . '/partials/counter_js.php'; ?>

<script>
    (function ($) {
        'use strict';

        // Show only the recipient field the chosen audience needs.
        function syncAudience() {
            var audience = $('#automassms-audience').val();

            $('[data-automassms-audience]').each(function () {
                var wanted = $(this).data('automassms-audience');
                $(this).toggle(wanted === audience);
            });
        }

        $(document).on('change', '#automassms-audience', syncAudience);

        $(document).on('click', '[data-automassms-token]', function () {
            var chip = $(this),
                target = document.getElementById(chip.data('automassms-target')),
                token = chip.data('automassms-token');

            if (!target) {
                return;
            }

            var start = target.selectionStart || 0,
                end = target.selectionEnd || 0;

            target.value = target.value.substring(0, start) + token + target.value.substring(end);
            target.selectionStart = target.selectionEnd = start + token.length;
            target.focus();
            $(target).trigger('input');
        });

        $(function () {
            syncAudience();

            // WHMCS ships a client-search selectize initialiser; use it when present.
            if (typeof WHMCS !== 'undefined' && typeof WHMCS.selectize !== 'undefined') {
                $('.selectize-client-search').data('search-url', 'index.php?rp=/admin/search/client');
                WHMCS.selectize.clientSearch();
            }
        });
    })(jQuery);
</script>
