<?php

namespace AutomasSMS;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Automas gateway status codes, per the "Status Code Meaning" table in the API docs.
 */
class StatusCode
{
    const SUCCESS = 0;

    /** Returned by this module (not the gateway) when the request never reached Automas. */
    const TRANSPORT_ERROR = -1;

    /** Returned by this module when Automas replied with something we could not parse. */
    const MALFORMED_RESPONSE = -2;

    private static $meanings = [
        0    => 'Success',
        101  => 'Invalid Message Length',
        102  => 'Sender Not Valid',
        103  => 'Authentication Failed',
        104  => 'Invalid User',
        105  => 'Invalid MSISDN',
        106  => 'Incorrect API Key',
        107  => 'User Account Suspended',
        108  => 'IP Address Not Allowed',
        109  => 'API Access Not Allowed',
        110  => 'Do Not Disturb (DND)',
        111  => 'Spam Word Detected in Message',
        1000 => 'Insufficient Balance',
        2000 => 'Destination Provider Unavailable',
        2300 => 'Destination Route Issue',
        2400 => 'Destination Route Not Permitted',
        3000 => 'Destination Provider Unavailable',
        3300 => 'System Error',
        4000 => 'Destination Provider Unavailable',
        -1   => 'Could not reach the Automas API',
        -2   => 'Unexpected response from the Automas API',
    ];

    public static function isSuccess($code)
    {
        return (int) $code === self::SUCCESS;
    }

    public static function meaning($code)
    {
        $code = (int) $code;

        if (isset(self::$meanings[$code])) {
            return self::$meanings[$code];
        }

        return 'Unknown status code (' . $code . ')';
    }

    /**
     * Codes that mean the account/credentials are wrong rather than the individual
     * message. Worth surfacing loudly in the admin UI.
     */
    public static function isAccountLevel($code)
    {
        return in_array((int) $code, [103, 104, 106, 107, 108, 109, 1000], true);
    }

    public static function all()
    {
        return self::$meanings;
    }
}
