<?php

/**
 * Automas SMS - Settings tab.
 *
 * Automas exposes no sender-ID listing endpoint, so the sender ID is a free text
 * field here rather than a dropdown populated from the gateway.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use AutomasSMS\Phone;
use AutomasSMS\Sender;
use AutomasSMS\Settings;

$notices = [];
$storedKey = (string) Settings::get('api_key', '');

if (!empty($_POST['automassms_action']) && $_POST['automassms_action'] === 'save_settings') {
    try {
        AutomasSMS_check_token();

        $submittedKey = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';

        // The field renders masked; an unchanged mask means "keep what we have".
        $apiKey = Settings::isMasked($submittedKey, $storedKey) ? $storedKey : $submittedKey;
        $keyChanged = $apiKey !== $storedKey;

        $senderId = isset($_POST['sender_id']) ? trim($_POST['sender_id']) : '';
        $unicodeType = isset($_POST['unicode_type']) ? trim($_POST['unicode_type']) : 'unicode';
        $retention = isset($_POST['log_retention']) ? (int) $_POST['log_retention'] : 90;

        $adminNumbers = Phone::normaliseList(isset($_POST['admin_numbers']) ? $_POST['admin_numbers'] : '');

        if ($adminNumbers['invalid']) {
            $notices[] = ['danger', 'These admin numbers are not valid Bangladeshi mobile numbers and were dropped: '
                . implode(', ', $adminNumbers['invalid'])];
        }

        if (!in_array($unicodeType, ['unicode', '8', 'text'], true)) {
            $unicodeType = 'unicode';
        }

        Settings::setMany([
            'api_key'       => $apiKey,
            'sender_id'     => $senderId,
            'unicode_type'  => $unicodeType,
            'admin_numbers' => implode(',', $adminNumbers['valid']),
            'log_retention' => max(0, $retention),
        ]);

        if ($keyChanged) {
            Settings::forgetBalance();
        }

        if ($apiKey === '') {
            $notices[] = ['warning', 'Settings saved, but no API key is set - nothing will be sent.'];
        } else {
            $check = Settings::cachedBalance(true);

            if ($check['ok']) {
                $notices[] = ['success', 'Settings saved. API key verified - current balance is BDT '
                    . number_format($check['balance'], 2) . '.'];
            } else {
                $notices[] = ['danger', 'Settings saved, but Automas rejected the API key: ' . $check['message']];
            }
        }
    } catch (\Throwable $e) {
        $notices[] = ['danger', $e->getMessage()];
    }
}

if (!empty($_POST['automassms_action']) && $_POST['automassms_action'] === 'test_sms') {
    try {
        AutomasSMS_check_token();

        $testNumber = isset($_POST['test_number']) ? $_POST['test_number'] : '';
        $normalised = Phone::normalise($testNumber);

        if (!Settings::isConfigured()) {
            throw new \Exception('Add and save your API key before sending a test message.');
        }

        if ($normalised === null) {
            throw new \Exception('That is not a valid Bangladeshi mobile number.');
        }

        $summary = Sender::dispatch(
            [[
                'msisdn'    => $normalised,
                'text'      => 'Automas SMS test message from your WHMCS installation.',
                'client_id' => 0,
            ]],
            'test'
        );

        Settings::forgetBalance();

        if ($summary['sent'] > 0) {
            $notices[] = ['success', 'Test message accepted by Automas for ' . $normalised . '.'];
        } else {
            $notices[] = ['danger', 'Test message failed: ' . implode(' | ', $summary['errors'])];
        }
    } catch (\Throwable $e) {
        $notices[] = ['danger', $e->getMessage()];
    }
}

$settings = Settings::all();
$storedKey = (string) $settings['api_key'];

include AUTOMASSMS_PATH . '/partials/header.php';

?>

<div class="automassms-panel">
    <?php foreach ($notices as $notice) : ?>
        <?php AutomasSMS_alert($notice[1], $notice[0]); ?>
    <?php endforeach; ?>

    <?php if ($storedKey === '') : ?>
        <div class="automassms-alert automassms-alert-info">
            Paste the API key from your Automas panel below to start sending. Sending is disabled until a key is saved.
        </div>
    <?php endif; ?>

    <form method="post" action="<?= AutomasSMS_e($moduleLink . '&tab=settings'); ?>" class="automassms-panel-narrow">
        <?= AutomasSMS_token_field(); ?>
        <input type="hidden" name="automassms_action" value="save_settings">

        <div class="automassms-section">
            <h4>Gateway credentials</h4>
            <div class="automassms-section-body">

                <div class="automassms-field">
                    <label for="automassms-api-key">API Key</label>
                    <div class="automassms-field-control">
                        <input type="text" id="automassms-api-key" name="api_key" autocomplete="off"
                               value="<?= AutomasSMS_e(Settings::maskApiKey($storedKey)); ?>">
                        <span class="automassms-hint">
                            Generated in the Automas panel. Leave the masked value untouched to keep the current key.
                        </span>
                    </div>
                </div>

                <div class="automassms-field">
                    <label for="automassms-sender-id">Sender ID</label>
                    <div class="automassms-field-control">
                        <input type="text" id="automassms-sender-id" name="sender_id"
                               value="<?= AutomasSMS_e($settings['sender_id']); ?>" placeholder="e.g. 8809617641356">
                        <span class="automassms-hint">
                            One of the approved sender IDs shown in your Automas panel. The API has no endpoint that
                            lists them, so it has to be typed here. Leave blank to use the account default.
                        </span>
                    </div>
                </div>

                <div class="automassms-field">
                    <label for="automassms-unicode-type">Unicode message type</label>
                    <div class="automassms-field-control">
                        <select id="automassms-unicode-type" name="unicode_type">
                            <?php foreach (['unicode' => 'unicode (default)', '8' => '8', 'text' => 'text'] as $value => $label) : ?>
                                <option value="<?= AutomasSMS_e($value); ?>" <?= $settings['unicode_type'] === (string) $value ? 'selected' : ''; ?>>
                                    <?= AutomasSMS_e($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="automassms-hint">
                            Value sent as <code>type</code> on smsapiv4 for Bangla / UCS-2 messages. The Automas docs
                            show <code>"type":"text"</code> for ASCII but only say "use 8" for Unicode, so if Bangla
                            arrives garbled, try the other value here. ASCII messages always send as <code>text</code>.
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="automassms-section">
            <h4>Defaults</h4>
            <div class="automassms-section-body">

                <div class="automassms-field">
                    <label for="automassms-admin-numbers">Default admin numbers</label>
                    <div class="automassms-field-control">
                        <input type="text" id="automassms-admin-numbers" name="admin_numbers"
                               value="<?= AutomasSMS_e($settings['admin_numbers']); ?>"
                               placeholder="01XXXXXXXXX, 01XXXXXXXXX">
                        <span class="automassms-hint">
                            Used by any admin notification that has no numbers of its own. Comma separated.
                        </span>
                    </div>
                </div>

                <div class="automassms-field">
                    <label for="automassms-retention">Log retention</label>
                    <div class="automassms-field-control">
                        <input type="number" id="automassms-retention" name="log_retention" min="0" max="3650"
                               value="<?= AutomasSMS_e($settings['log_retention']); ?>">
                        <span class="automassms-hint">
                            Days of message history to keep. Older rows are removed by the daily cron. Use 0 to keep everything.
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="automassms-actions">
            <button type="submit" class="automassms-btn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>

    <?php if ($storedKey !== '') : ?>
        <form method="post" action="<?= AutomasSMS_e($moduleLink . '&tab=settings'); ?>" class="automassms-panel-narrow">
            <?= AutomasSMS_token_field(); ?>
            <input type="hidden" name="automassms_action" value="test_sms">

            <div class="automassms-section" style="margin-top:16px;">
                <h4>Send a test message</h4>
                <div class="automassms-section-body">
                    <div class="automassms-field">
                        <label for="automassms-test-number">Mobile number</label>
                        <div class="automassms-field-control">
                            <input type="text" id="automassms-test-number" name="test_number" placeholder="01XXXXXXXXX">
                            <span class="automassms-hint">
                                Sends one real SMS and charges your account. The result appears in SMS Logs.
                            </span>
                        </div>
                    </div>

                    <div class="automassms-actions">
                        <button type="submit" class="automassms-btn automassms-btn-secondary">
                            <i class="fas fa-paper-plane"></i> Send Test
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include AUTOMASSMS_PATH . '/partials/footer.php'; ?>
