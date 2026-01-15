<?php

namespace AutomasSMS;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Notification templates - one row per hook, keyed by (name, user_type).
 */
class Templates
{
    private static $cache = [];

    /**
     * @return object|null
     */
    public static function find($name, $userType)
    {
        $cacheKey = $userType . ':' . $name;

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        try {
            $row = Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)
                ->where('name', $name)
                ->where('user_type', $userType)
                ->first();
        } catch (\Exception $e) {
            $row = null;
        }

        self::$cache[$cacheKey] = $row;

        return $row;
    }

    /**
     * @return object[]
     */
    public static function ofType($userType)
    {
        return Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)
            ->where('user_type', $userType)
            ->orderBy('name')
            ->get()
            ->all();
    }

    public static function update($id, array $values)
    {
        Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)->where('id', (int) $id)->update($values);

        self::$cache = [];
    }

    /**
     * Create rows for any hook that does not have one yet, and refresh the
     * variable list on hooks that do (so a module upgrade that adds a token
     * shows it in the admin UI without wiping the operator's wording).
     *
     * @param array $definitions From Registry::definitions().
     * @return int Number of templates created.
     */
    public static function sync(array $definitions)
    {
        $created = 0;

        foreach ($definitions as $definition) {
            $existing = Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)
                ->where('name', $definition['key'])
                ->where('user_type', $definition['type'])
                ->first();

            if ($existing) {
                Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)
                    ->where('id', $existing->id)
                    ->update([
                        'variables'   => $definition['variables'],
                        'description' => $definition['description'],
                    ]);

                continue;
            }

            Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)->insert([
                'name'          => $definition['key'],
                'user_type'     => $definition['type'],
                'admin_numbers' => '',
                'content'       => $definition['default'],
                'variables'     => $definition['variables'],
                'is_active'     => 0,
                'extra_info'    => $definition['extra'],
                'description'   => $definition['description'],
            ]);

            $created++;
        }

        self::$cache = [];

        return $created;
    }

    /**
     * Remove template rows for hooks that no longer ship with the module.
     */
    public static function prune(array $definitions)
    {
        $keep = [];

        foreach ($definitions as $definition) {
            $keep[] = $definition['type'] . ':' . $definition['key'];
        }

        $removed = 0;

        foreach (Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)->get() as $row) {
            if (in_array($row->user_type . ':' . $row->name, $keep, true)) {
                continue;
            }

            Capsule::table(AUTOMASSMS_TABLE_TEMPLATES)->where('id', $row->id)->delete();
            $removed++;
        }

        self::$cache = [];

        return $removed;
    }

    public static function flush()
    {
        self::$cache = [];
    }
}
