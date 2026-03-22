<?php

/**
 * Automas SMS - confirm a client area password change.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'ClientChangePassword',
    'hook'        => 'UserChangePassword',
    'type'        => 'client',
    'description' => 'After the client changes their password',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, your password has been changed successfully. If this was not you, contact us immediately.',
    'variables'   => '{firstname},{lastname},{password},{company}',
    'resolve'     => function (array $args, Context $ctx) {
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
                '{password}'  => isset($args['password']) ? $args['password'] : '',
            ],
        ]];
    },
];
