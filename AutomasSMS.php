<?php

/**
 * Automas SMS for WHMCS
 *
 * SMS notifications and broadcasts through the Automas gateway
 * (https://api.automas.com.bd).
 *
 * @package    AutomasSMS
 * @version    1.0.4
 * @author     Mursalin Ahammad Khan Alin <mursalin@xit.bd>
 * @link       https://github.com/mursalinahamed6t9
 * @copyright  XIT BD - https://xit.bd/
 * @link       https://www.automas.com.bd
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/bootstrap.php';

use AutomasSMS\MessageLog;
use AutomasSMS\Registry;
use AutomasSMS\Schema;
use AutomasSMS\Settings;
use AutomasSMS\Templates;

/**
 * Tabs the addon will render. Anything not in this list 404s, so the tab name
 * from the query string can never be used to include an arbitrary file.
 */
function AutomasSMS_tabs()
{
    return [
        'settings'         => ['label' => 'Settings', 'icon' => 'fa-cogs'],
        'client_templates' => ['label' => 'Client Templates', 'icon' => 'fa-user'],
        'admin_templates'  => ['label' => 'Admin Templates', 'icon' => 'fa-user-shield'],
        'send_sms'         => ['label' => 'Send SMS', 'icon' => 'fa-paper-plane'],
        'sms_logs'         => ['label' => 'SMS Logs', 'icon' => 'fa-list'],
    ];
}

function AutomasSMS_config()
{
    return [
        'name'        => 'Automas SMS',
        'description' => 'Send WHMCS notifications and bulk SMS through the Automas gateway (api.automas.com.bd).',
        'version'     => AUTOMASSMS_VERSION,
        'author'      => 'Mursalin Ahammad Khan Alin - XIT BD',
        'language'    => 'english',
    ];
}

function AutomasSMS_activate()
{
    try {
        Schema::install();

        $created = Templates::sync(Registry::definitions());

        return [
            'status'      => 'success',
            'description' => 'Automas SMS activated. ' . $created . ' notification template(s) installed. '
                . 'Open the module and add your API key on the Settings tab to start sending.',
        ];
    } catch (\Throwable $e) {
        return [
            'status'      => 'error',
            'description' => 'Could not activate Automas SMS: ' . $e->getMessage(),
        ];
    }
}

function AutomasSMS_deactivate()
{
    try {
        Schema::uninstall();

        return [
            'status'      => 'success',
            'description' => 'Automas SMS deactivated and its tables removed.',
        ];
    } catch (\Throwable $e) {
        return [
            'status'      => 'error',
            'description' => 'Could not deactivate Automas SMS: ' . $e->getMessage(),
        ];
    }
}

/**
 * Runs on every module upgrade. Creates any missing table, adds templates for
 * newly shipped hooks and removes templates for hooks that no longer exist,
 * without touching the wording the operator has customised.
 */
function AutomasSMS_upgrade($vars)
{
    try {
        Schema::install();

        $definitions = Registry::definitions();

        Templates::sync($definitions);
        Templates::prune($definitions);

        Settings::set('version', AUTOMASSMS_VERSION);
    } catch (\Throwable $e) {
        logActivity('Automas SMS upgrade failed: ' . $e->getMessage());
    }
}

function AutomasSMS_output($vars)
{
    $tabs = AutomasSMS_tabs();
    $requested = isset($_GET['tab']) ? (string) $_GET['tab'] : 'settings';
    $tab = array_key_exists($requested, $tabs) ? $requested : '404';

    // Everything the tabs need in scope.
    $moduleLink = 'addonmodules.php?module=' . AUTOMASSMS_MODULE;
    $activeTab = $tab;
    $installed = Schema::isInstalled();

    include AUTOMASSMS_PATH . '/partials/style.php';

    if (!$installed && $tab !== '404') {
        include AUTOMASSMS_PATH . '/partials/header.php';
        echo '<div class="automassms-panel"><div class="automassms-empty">'
            . '<h3>Module tables are missing</h3>'
            . '<p>Deactivate and reactivate Automas SMS from <strong>System Settings &raquo; Addon Modules</strong> to create them.</p>'
            . '</div></div>';
        include AUTOMASSMS_PATH . '/partials/footer.php';

        return;
    }

    try {
        include AUTOMASSMS_PATH . '/tabs/' . $tab . '.php';
    } catch (\Throwable $e) {
        logActivity('Automas SMS: error rendering the ' . $tab . ' tab - ' . $e->getMessage());

        echo '<div class="automassms-wrapper"><div class="automassms-panel">'
            . '<div class="automassms-alert automassms-alert-danger">'
            . 'Something went wrong rendering this tab: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</div></div></div>';
    }
}

/**
 * Renders a dismissible alert inside a tab.
 */
function AutomasSMS_alert($message, $type = 'success')
{
    $classes = [
        'success' => 'automassms-alert-success',
        'danger'  => 'automassms-alert-danger',
        'warning' => 'automassms-alert-warning',
        'info'    => 'automassms-alert-info',
    ];

    $class = isset($classes[$type]) ? $classes[$type] : $classes['info'];

    echo '<div class="automassms-alert ' . $class . '">'
        . htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8')
        . '</div>';
}

/**
 * Short helper so the tab templates stay readable.
 */
function AutomasSMS_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF protection.
 *
 * Self-contained rather than delegating to WHMCS's generate_token()/check_token(),
 * so the module does not depend on that pair's argument conventions staying put
 * across releases. Every state-changing form in this module posts the token and
 * every handler verifies it before touching anything.
 */
function AutomasSMS_token_value()
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (empty($_SESSION['automassms_token'])) {
        try {
            $_SESSION['automassms_token'] = bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            $_SESSION['automassms_token'] = md5(uniqid('', true));
        }
    }

    return $_SESSION['automassms_token'];
}

function AutomasSMS_token_field()
{
    return '<input type="hidden" name="automassms_token" value="'
        . AutomasSMS_e(AutomasSMS_token_value()) . '">';
}

/**
 * @throws Exception when the posted token does not match the session.
 */
function AutomasSMS_check_token()
{
    $submitted = isset($_POST['automassms_token']) ? (string) $_POST['automassms_token'] : '';

    if (!hash_equals(AutomasSMS_token_value(), $submitted)) {
        throw new \Exception(
            'This request could not be verified - your session may have expired. Reload the page and try again.'
        );
    }
}

/**
 * Summary strip shown at the top of the log tab.
 */
function AutomasSMS_summary()
{
    return MessageLog::summary();
}
