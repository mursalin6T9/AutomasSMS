<?php

/**
 * Automas SMS - confirm a profile change.
 *
 * Only fires when the phone number itself changed, matching the original
 * behaviour: the point is to confirm the new number actually receives SMS.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'ClientEdit',
    'hook'        => 'ClientEdit',
    'type'        => 'client',
    'description' => 'After Client Edit (only when the phone number changes)',
    'extra'       => '',
    'default'     => 'Dear {firstname} {lastname}, your profile has been updated.',
    'variables'   => '{firstname},{lastname},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $old = isset($args['olddata']['phonenumber']) ? $args['olddata']['phonenumber'] : null;
        $new = isset($args['phonenumber']) ? $args['phonenumber'] : null;

        if ($old === null || $new === null || $old === $new) {
            return [];
        }

        $client = $ctx->client(isset($args['userid']) ? $args['userid'] : 0);

        if (!$client) {
            return [];
        }

        return [[
            'client_id' => $client->id,
            'msisdn'    => $client->phonenumber,
            'tokens'    => [
                '{firstname}' => $client->firstname,
                '{lastname}'  => $client->lastname,
            ],
        ]];
    },
];
