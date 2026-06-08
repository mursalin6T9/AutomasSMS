<?php

/**
 * Automas SMS - unknown tab.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

include AUTOMASSMS_PATH . '/partials/header.php';

?>

<div class="automassms-panel">
    <div class="automassms-empty">
        <h1>404</h1>
        <h3>Tab not found</h3>
        <p>The tab you asked for does not exist in this module.</p>
        <p>
            <a href="<?= AutomasSMS_e($moduleLink . '&tab=settings'); ?>" class="automassms-btn">
                <i class="fas fa-cogs"></i> Go to Settings
            </a>
        </p>
    </div>
</div>

<?php include AUTOMASSMS_PATH . '/partials/footer.php'; ?>
