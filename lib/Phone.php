<?php

namespace AutomasSMS;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Bangladeshi MSISDN handling.
 *
 * The Automas docs accept both "01870830179" and "88017XXXXXXXX". We normalise
 * everything to the 13 digit "8801XXXXXXXXX" form so that de-duplication and log
 * lookups are consistent regardless of how the number was typed into WHMCS.
 */
class Phone
{
    /**
     * @return string|null 8801XXXXXXXXX, or null when the number is not a valid BD mobile.
     */
    public static function normalise($number)
    {
        if ($number === null) {
            return null;
        }

        // Drop spaces, dashes, brackets and any other decoration.
        $digits = preg_replace('/[^0-9]/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        // 008801XXXXXXXXX -> 8801XXXXXXXXX
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11 && strpos($digits, '01') === 0) {
            // 01XXXXXXXXX
            $digits = '88' . $digits;
        } elseif (strlen($digits) === 10 && strpos($digits, '1') === 0) {
            // 1XXXXXXXXX (leading zero lost, e.g. stored as an integer somewhere)
            $digits = '880' . $digits;
        }

        if (!preg_match('/^8801[3-9][0-9]{8}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    public static function isValid($number)
    {
        return self::normalise($number) !== null;
    }

    /**
     * Normalise a comma//semicolon/newline/plus separated list into unique MSISDNs.
     *
     * @param string|array $input
     * @return array{valid: string[], invalid: string[]}
     */
    public static function normaliseList($input)
    {
        if (!is_array($input)) {
            $input = preg_split('/[\s,;+]+/', (string) $input);
        }

        $valid = [];
        $invalid = [];

        foreach ($input as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate === '') {
                continue;
            }

            $normalised = self::normalise($candidate);

            if ($normalised === null) {
                $invalid[] = $candidate;
                continue;
            }

            $valid[$normalised] = $normalised;
        }

        return [
            'valid'   => array_values($valid),
            'invalid' => array_values(array_unique($invalid)),
        ];
    }
}
