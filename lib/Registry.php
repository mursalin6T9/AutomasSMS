<?php

namespace AutomasSMS;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Loads the notification definitions from hooks/.
 *
 * Each file in hooks/ returns one definition array:
 *
 *   key         string  Template identifier, unique per user_type.
 *   hook        string  WHMCS hook point to bind to.
 *   type        string  'client' (message goes to the customer) or
 *                       'admin' (message goes to the numbers on the template).
 *   description string  Shown next to the template in the admin UI.
 *   extra       string  Optional numeric setting, e.g. the notice window in days.
 *   default     string  Default message body.
 *   variables   string  Comma separated {tokens} offered in the admin UI.
 *   resolve     callable(array $args, Context $ctx, object $template): array
 *                       Returns a list of targets:
 *                       ['client_id' => int, 'msisdn' => ?string, 'tokens' => array]
 *                       For admin notifications msisdn is omitted and the
 *                       dispatcher fans the message out to the admin numbers.
 */
class Registry
{
    private static $definitions;

    /**
     * @return array<string, array> Keyed by "type:key".
     */
    public static function definitions()
    {
        if (self::$definitions !== null) {
            return self::$definitions;
        }

        $definitions = [];
        $directory = AUTOMASSMS_PATH . '/hooks';

        foreach (glob($directory . '/*.php') as $file) {
            $definition = require $file;

            if (!is_array($definition)) {
                logActivity('Automas SMS: ' . basename($file) . ' did not return a definition array.');
                continue;
            }

            $definition = self::normalise($definition, $file);

            if ($definition === null) {
                continue;
            }

            $definitions[$definition['type'] . ':' . $definition['key']] = $definition;
        }

        ksort($definitions);

        self::$definitions = $definitions;

        return $definitions;
    }

    /**
     * @return array|null
     */
    public static function find($key, $type)
    {
        $definitions = self::definitions();
        $index = $type . ':' . $key;

        return isset($definitions[$index]) ? $definitions[$index] : null;
    }

    private static function normalise(array $definition, $file)
    {
        $required = ['key', 'hook', 'type', 'default', 'variables', 'resolve'];

        foreach ($required as $field) {
            if (!isset($definition[$field])) {
                logActivity('Automas SMS: ' . basename($file) . ' is missing the "' . $field . '" field.');

                return null;
            }
        }

        if (!in_array($definition['type'], ['client', 'admin'], true)) {
            logActivity('Automas SMS: ' . basename($file) . ' has an unknown type "' . $definition['type'] . '".');

            return null;
        }

        if (!is_callable($definition['resolve'])) {
            logActivity('Automas SMS: ' . basename($file) . ' has a non-callable resolver.');

            return null;
        }

        $definition['description'] = isset($definition['description']) ? $definition['description'] : '';
        $definition['extra'] = isset($definition['extra']) ? (string) $definition['extra'] : '';
        $definition['variables'] = str_replace(' ', '', (string) $definition['variables']);

        return $definition;
    }

    /**
     * Human readable label for a notification, used in the admin UI and log filter.
     */
    public static function label($key)
    {
        $label = preg_replace('/(Admin)$/', '', (string) $key);
        $label = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $label);

        return trim($label);
    }
}
