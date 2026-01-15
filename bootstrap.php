<?php

/**
 * Automas SMS - shared bootstrap.
 *
 * Required by both the addon entry point and hooks.php so that either can be the
 * first thing WHMCS loads.
 *
 * @package    AutomasSMS
 * @author     Mursalin Ahammad Khan Alin <mursalin@xit.bd>
 * @link       https://github.com/mursalinahamed6t9
 * @copyright  XIT BD - https://xit.bd/
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (defined('AUTOMASSMS_VERSION')) {
    return;
}

define('AUTOMASSMS_VERSION', '1.0.1');
define('AUTOMASSMS_PATH', __DIR__);
define('AUTOMASSMS_MODULE', basename(__DIR__));

/** Everything the gateway does is in Dhaka local time. */
define('AUTOMASSMS_TIMEZONE', 'Asia/Dhaka');

define('AUTOMASSMS_TABLE_SETTINGS', 'mod_automassms_settings');
define('AUTOMASSMS_TABLE_TEMPLATES', 'mod_automassms_templates');
define('AUTOMASSMS_TABLE_MESSAGES', 'mod_automassms_messages');

spl_autoload_register(function ($class) {
    $prefix = 'AutomasSMS\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = AUTOMASSMS_PATH . '/lib/' . $relative . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
