<?php

/**
 * Shared editor for the client and admin notification templates.
 *
 * Expects $templateType ('client'|'admin'), $moduleLink and $activeTab in scope.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use AutomasSMS\Phone;
use AutomasSMS\Registry;
use AutomasSMS\Settings;
use AutomasSMS\Templates;
use AutomasSMS\Text;

$notices = [];

if (!empty($_POST['automassms_action']) && $_POST['automassms_action'] === 'save_templates') {
    try {
        AutomasSMS_check_token();

        $saved = 0;

        foreach (Templates::ofType($templateType) as $row) {
            $prefix = 'tpl_' . (int) $row->id . '_';

            $content = isset($_POST[$prefix . 'content']) ? (string) $_POST[$prefix . 'content'] : $row->content;
            $isActive = !empty($_POST[$prefix . 'active']) ? 1 : 0;

            $update = [
                'content'   => $content,
                'is_active' => $isActive,
            ];

            if (isset($_POST[$prefix . 'extra'])) {
                $update['extra_info'] = (string) max(0, (int) $_POST[$prefix . 'extra']);
            }

            if ($templateType === 'admin') {
                $numbers = Phone::normaliseList(isset($_POST[$prefix . 'numbers']) ? $_POST[$prefix . 'numbers'] : '');

                if ($numbers['invalid']) {
                    $notices[] = ['warning', Registry::label($row->name) . ': dropped invalid number(s) '
                        . implode(', ', $numbers['invalid'])];
                }

                $update['admin_numbers'] = implode(',', $numbers['valid']);

                if ($isActive && !$numbers['valid'] && trim((string) Settings::get('admin_numbers', '')) === '') {
                    $notices[] = ['warning', Registry::label($row->name)
                        . ' is enabled but has no admin numbers, and no default numbers are set on the Settings tab.'];
                }
            }

            if ($isActive && trim($content) === '') {
                $notices[] = ['danger', Registry::label($row->name) . ' is enabled but its message is empty - it was left disabled.'];
                $update['is_active'] = 0;
            }

            Templates::update($row->id, $update);
            $saved++;
        }

        array_unshift($notices, ['success', $saved . ' template(s) saved.']);
    } catch (\Throwable $e) {
        $notices[] = ['danger', $e->getMessage()];
    }
}

$rows = Templates::ofType($templateType);
$enabledCount = 0;

foreach ($rows as $row) {
    if ((int) $row->is_active === 1) {
        $enabledCount++;
    }
}

include AUTOMASSMS_PATH . '/partials/header.php';

?>

<div class="automassms-panel">
    <?php foreach ($notices as $notice) : ?>
        <?php AutomasSMS_alert($notice[1], $notice[0]); ?>
    <?php endforeach; ?>

    <?php if (!Settings::isConfigured()) : ?>
        <div class="automassms-alert automassms-alert-warning">
            No API key is configured, so enabled templates will not send. Add one on the Settings tab.
        </div>
    <?php endif; ?>

    <div class="automassms-alert automassms-alert-info">
        <?php if ($templateType === 'admin') : ?>
            These notifications go to your staff. Enter the numbers per notification, or leave them blank to use the
            default admin numbers from the Settings tab.
        <?php else : ?>
            These notifications go to the customer's mobile number on their WHMCS profile. Accounts without a valid
            Bangladeshi mobile number are skipped and noted in the activity log.
        <?php endif; ?>
        <strong><?= (int) $enabledCount; ?></strong> of <strong><?= count($rows); ?></strong> currently enabled.
    </div>

    <?php if (!$rows) : ?>
        <div class="automassms-empty">
            <h3>No templates found</h3>
            <p>Reactivate the module to install the notification templates.</p>
        </div>
    <?php else : ?>
        <form method="post" action="<?= AutomasSMS_e($moduleLink . '&tab=' . $activeTab); ?>">
            <?= AutomasSMS_token_field(); ?>
            <input type="hidden" name="automassms_action" value="save_templates">

            <?php foreach ($rows as $row) : ?>
                <?php
                $id = (int) $row->id;
                $isActive = (int) $row->is_active === 1;
                $definition = Registry::find($row->name, $templateType);
                $measured = Text::measure($row->content);
                $usesDays = strpos((string) $row->variables, '{x}') !== false;
                ?>

                <div class="automassms-template">
                    <div class="automassms-template-head">
                        <label for="tpl_<?= $id; ?>_active">
                            <input type="checkbox" id="tpl_<?= $id; ?>_active" name="tpl_<?= $id; ?>_active"
                                   value="1" <?= $isActive ? 'checked' : ''; ?>
                                   data-automassms-toggle="tpl_<?= $id; ?>_content">
                            <?= AutomasSMS_e(Registry::label($row->name)); ?>
                        </label>
                        <span class="automassms-template-desc"><?= AutomasSMS_e($row->description); ?></span>
                    </div>

                    <div class="automassms-template-body">
                        <div class="automassms-tokens">
                            <?php foreach (array_filter(explode(',', (string) $row->variables)) as $token) : ?>
                                <span class="automassms-token" data-automassms-token="<?= AutomasSMS_e($token); ?>"
                                      data-automassms-target="tpl_<?= $id; ?>_content"><?= AutomasSMS_e($token); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <textarea id="tpl_<?= $id; ?>_content" name="tpl_<?= $id; ?>_content"
                                  data-automassms-counter="tpl_<?= $id; ?>_counter"
                                  <?= $isActive ? '' : 'readonly'; ?>><?= AutomasSMS_e($row->content); ?></textarea>

                        <div class="automassms-counter" id="tpl_<?= $id; ?>_counter">
                            <?= (int) $measured['chars']; ?> characters &middot;
                            <?= (int) $measured['parts']; ?> SMS &middot;
                            <?= AutomasSMS_e($measured['encoding'] === 'unicode' ? 'Unicode (70/segment)' : 'GSM (160/segment)'); ?>
                        </div>

                        <?php if ($templateType === 'admin') : ?>
                            <div class="automassms-inline-field">
                                <label for="tpl_<?= $id; ?>_numbers">Admin numbers</label>
                                <input type="text" id="tpl_<?= $id; ?>_numbers" name="tpl_<?= $id; ?>_numbers"
                                       value="<?= AutomasSMS_e($row->admin_numbers); ?>"
                                       placeholder="01XXXXXXXXX, 01XXXXXXXXX">
                            </div>
                        <?php endif; ?>

                        <?php if ($usesDays) : ?>
                            <div class="automassms-inline-field">
                                <label for="tpl_<?= $id; ?>_extra">Days before expiry ({x})</label>
                                <input type="number" min="1" max="365" class="automassms-days"
                                       id="tpl_<?= $id; ?>_extra" name="tpl_<?= $id; ?>_extra"
                                       value="<?= AutomasSMS_e($row->extra_info); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>

            <div class="automassms-actions">
                <button type="submit" class="automassms-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include AUTOMASSMS_PATH . '/partials/footer.php'; ?>
<?php include AUTOMASSMS_PATH . '/partials/counter_js.php'; ?>

<script>
    (function ($) {
        'use strict';

        // Enabling a template unlocks its message body.
        $(document).on('change', '[data-automassms-toggle]', function () {
            var target = document.getElementById($(this).data('automassms-toggle'));

            if (target) {
                target.readOnly = !this.checked;
            }
        });

        // Clicking a token chip inserts it at the caret.
        $(document).on('click', '[data-automassms-token]', function () {
            var chip = $(this),
                target = document.getElementById(chip.data('automassms-target')),
                token = chip.data('automassms-token');

            if (!target || target.readOnly) {
                return;
            }

            var start = target.selectionStart || 0,
                end = target.selectionEnd || 0;

            target.value = target.value.substring(0, start) + token + target.value.substring(end);
            target.selectionStart = target.selectionEnd = start + token.length;
            target.focus();
            $(target).trigger('input');
        });
    })(jQuery);
</script>
