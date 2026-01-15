<?php

namespace AutomasSMS;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Key/value module settings.
 *
 * A key/value table rather than the fixed-column layout the old module used, so
 * that adding a setting never needs a schema migration.
 */
class Settings
{
    private static $cache;

    private static $defaults = [
        'api_key'       => '',
        'sender_id'     => '',
        // See Api::$unicodeType - the docs are ambiguous on the v4 value for UCS-2.
        'unicode_type'  => 'unicode',
        'admin_numbers' => '',
        'log_retention' => '90',
        'balance_checked_at' => '0',
        'balance_key'   => '',
        'balance_value' => '',
        'balance_error' => '',
        'version'       => '',
    ];

    public static function all()
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $values = self::$defaults;

        try {
            foreach (Capsule::table(AUTOMASSMS_TABLE_SETTINGS)->get() as $row) {
                $values[$row->setting] = $row->value;
            }
        } catch (\Exception $e) {
            // Table missing (module not activated yet) - fall back to defaults.
        }

        self::$cache = $values;

        return $values;
    }

    public static function get($key, $default = null)
    {
        $all = self::all();

        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }

        return $default;
    }

    public static function set($key, $value)
    {
        Capsule::table(AUTOMASSMS_TABLE_SETTINGS)->updateOrInsert(
            ['setting' => $key],
            ['value' => (string) $value]
        );

        self::$cache = null;
    }

    public static function setMany(array $values)
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }

    public static function flush()
    {
        self::$cache = null;
    }

    public static function isConfigured()
    {
        return trim((string) self::get('api_key')) !== '';
    }

    /**
     * Build an API client from the stored credentials.
     */
    public static function api()
    {
        return new Api(
            self::get('api_key'),
            self::get('sender_id'),
            self::get('unicode_type', 'unicode')
        );
    }

    /**
     * Balance, cached briefly so that opening a tab does not hit the gateway on
     * every page load.
     *
     * @param bool $force Skip the cache (used right after saving settings).
     * @return array{ok: bool, balance: float|null, message: string, cached: bool}
     */
    public static function cachedBalance($force = false, $ttl = 300)
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'balance' => null, 'message' => 'No API key configured.', 'cached' => false];
        }

        $cachedAt = (int) self::get('balance_checked_at', 0);
        $fingerprint = md5((string) self::get('api_key'));

        if (
            !$force
            && $cachedAt > 0
            && (time() - $cachedAt) < $ttl
            && self::get('balance_key') === $fingerprint
        ) {
            $stored = self::get('balance_value', '');

            if ($stored !== '') {
                return ['ok' => true, 'balance' => (float) $stored, 'message' => '', 'cached' => true];
            }

            return [
                'ok'      => false,
                'balance' => null,
                'message' => (string) self::get('balance_error', 'Balance unavailable.'),
                'cached'  => true,
            ];
        }

        $result = self::api()->verify();

        self::setMany([
            'balance_checked_at' => time(),
            'balance_key'        => $fingerprint,
            'balance_value'      => $result['ok'] ? (string) $result['balance'] : '',
            'balance_error'      => $result['ok'] ? '' : $result['message'],
        ]);

        return [
            'ok'      => $result['ok'],
            'balance' => $result['balance'],
            'message' => $result['message'],
            'cached'  => false,
        ];
    }

    /**
     * Drop the cached balance, e.g. after sending or changing the API key.
     */
    public static function forgetBalance()
    {
        self::setMany([
            'balance_checked_at' => 0,
            'balance_value'      => '',
            'balance_error'      => '',
        ]);
    }

    /**
     * Show only the first and last few characters of the key in the admin UI.
     */
    public static function maskApiKey($apiKey)
    {
        $apiKey = (string) $apiKey;
        $length = strlen($apiKey);

        if ($length === 0) {
            return '';
        }

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($apiKey, 0, 4) . str_repeat('*', $length - 8) . substr($apiKey, -4);
    }

    /**
     * True when the submitted value is the mask we rendered, meaning "unchanged".
     */
    public static function isMasked($submitted, $stored)
    {
        $submitted = trim((string) $submitted);

        return $submitted !== '' && $submitted === self::maskApiKey($stored);
    }
}
