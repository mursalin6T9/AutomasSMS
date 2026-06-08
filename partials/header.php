<?php

/**
 * Opens the module chrome: brand bar, account summary and tab navigation.
 *
 * Expects $moduleLink and $activeTab in scope (set by AutomasSMS_output).
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use AutomasSMS\Settings;

$brandBalance = null;
$brandBalanceError = null;

if (Settings::isConfigured()) {
    $brandCheck = Settings::cachedBalance();

    if ($brandCheck['ok']) {
        $brandBalance = $brandCheck['balance'];
    } else {
        $brandBalanceError = $brandCheck['message'];
    }
}

$brandSenderId = trim((string) Settings::get('sender_id', ''));

?>
<div class="automassms-wrapper">
    <div class="automassms-brand">
        <div class="automassms-brand-name">
            <span class="automassms-mark"><i class="fas fa-comment-dots"></i></span>
            Automas SMS
        </div>

        <div class="automassms-brand-meta">
            <?php if ($brandBalance !== null) : ?>
                <div class="automassms-stat">
                    <span>Balance</span>
                    <strong>BDT <?= AutomasSMS_e(number_format($brandBalance, 2)); ?></strong>
                </div>
            <?php elseif ($brandBalanceError !== null) : ?>
                <div class="automassms-stat">
                    <span>Balance</span>
                    <strong title="<?= AutomasSMS_e($brandBalanceError); ?>">Unavailable</strong>
                </div>
            <?php endif; ?>

            <div class="automassms-stat">
                <span>Sender ID</span>
                <strong><?= $brandSenderId === '' ? 'Not set' : AutomasSMS_e($brandSenderId); ?></strong>
            </div>

            <div class="automassms-stat">
                <span>Version</span>
                <strong><?= AutomasSMS_e(AUTOMASSMS_VERSION); ?></strong>
            </div>
        </div>
    </div>

    <ul class="automassms-nav">
        <?php foreach (AutomasSMS_tabs() as $tabKey => $tabMeta) : ?>
            <li class="<?= $activeTab === $tabKey ? 'is-active' : ''; ?>">
                <a href="<?= AutomasSMS_e($moduleLink . '&tab=' . $tabKey); ?>">
                    <i class="fas <?= AutomasSMS_e($tabMeta['icon']); ?>"></i>
                    <span><?= AutomasSMS_e($tabMeta['label']); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
