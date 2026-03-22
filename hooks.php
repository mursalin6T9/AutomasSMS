<?php

/**
 * Automas SMS - hook registration.
 *
 * WHMCS includes this file on every request. Each notification defined in hooks/
 * is bound to its WHMCS hook point through the shared dispatcher; none of them
 * declare global functions of their own.
 *
 * @package    AutomasSMS
 * @author     Mursalin Ahammad Khan Alin <mursalin@xit.bd>
 * @copyright  XIT BD - https://xit.bd/
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/bootstrap.php';

try {
    \AutomasSMS\Dispatcher::register();
} catch (\Throwable $e) {
    logActivity('Automas SMS: could not register hooks - ' . $e->getMessage());
}
