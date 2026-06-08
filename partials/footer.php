<?php

/**
 * Closes the module chrome and renders the credit line.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

?>
    <div class="automassms-credit">
        <span>Automas SMS v<?= AutomasSMS_e(AUTOMASSMS_VERSION); ?></span>
        <span class="automassms-credit-sep">&middot;</span>
        <span>
            Developed by
            <a href="https://xit.bd/" target="_blank" rel="noopener noreferrer">XIT BD</a>
        </span>
    </div>
</div>
