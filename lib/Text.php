<?php

namespace AutomasSMS;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * SMS encoding detection and segment counting.
 *
 * The Automas docs state 160 characters for ASCII and 70 for Unicode. Those are the
 * single-segment limits; concatenated messages lose 7 bits per segment to the UDH,
 * giving 153 / 67. Characters in the GSM 03.38 extension table occupy two septets.
 */
class Text
{
    /** GSM 03.38 basic character set. */
    const GSM_BASIC = '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r"
        . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡'
        . 'ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';

    /** GSM 03.38 extension table - each of these costs two septets. */
    const GSM_EXTENDED = '^{}\\[~]|€';

    /**
     * True when the message needs UCS-2 (e.g. Bangla), which caps a segment at 70 chars.
     */
    public static function isUnicode($message)
    {
        $basic = self::toCharArray(self::GSM_BASIC);
        $extended = self::toCharArray(self::GSM_EXTENDED);
        $allowed = array_flip(array_merge($basic, $extended));

        foreach (self::toCharArray($message) as $char) {
            if (!isset($allowed[$char])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{encoding: string, chars: int, parts: int, per_part: int, remaining: int}
     */
    public static function measure($message)
    {
        $message = (string) $message;
        $unicode = self::isUnicode($message);
        $chars = self::toCharArray($message);
        $length = count($chars);

        if (!$unicode) {
            // Extension-table characters are two septets each.
            $extended = array_flip(self::toCharArray(self::GSM_EXTENDED));

            foreach ($chars as $char) {
                if (isset($extended[$char])) {
                    $length++;
                }
            }
        }

        $single = $unicode ? 70 : 160;
        $concatenated = $unicode ? 67 : 153;

        if ($length === 0) {
            return [
                'encoding'  => $unicode ? 'unicode' : 'text',
                'chars'     => 0,
                'parts'     => 0,
                'per_part'  => $single,
                'remaining' => $single,
            ];
        }

        if ($length <= $single) {
            $parts = 1;
            $perPart = $single;
        } else {
            $perPart = $concatenated;
            $parts = (int) ceil($length / $concatenated);
        }

        return [
            'encoding'  => $unicode ? 'unicode' : 'text',
            'chars'     => $length,
            'parts'     => $parts,
            'per_part'  => $perPart,
            'remaining' => ($perPart * $parts) - $length,
        ];
    }

    /**
     * Replace {token} placeholders. Tokens are matched literally, longest first, so
     * that e.g. {password} cannot be clobbered by a shorter overlapping token.
     */
    public static function render($template, array $tokens)
    {
        $keys = array_keys($tokens);

        usort($keys, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        $ordered = [];

        foreach ($keys as $key) {
            $ordered[$key] = (string) $tokens[$key];
        }

        return strtr((string) $template, $ordered);
    }

    private static function toCharArray($string)
    {
        return preg_split('//u', (string) $string, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
