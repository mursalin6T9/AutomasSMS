<?php

/**
 * Automas SMS - welcome message after a client registers.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'ClientAdd',
    'hook'        => 'ClientAdd',
    'type'        => 'client',
    'description' => 'After Client Registration',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, thank you for registering with {company}.',
    'variables'   => '{firstname},{lastname},{email},{password},{company}',
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
                '{email}'     => isset($args['email']) ? $args['email'] : $client->email,
                '{password}'  => isset($args['password']) ? $args['password'] : '',
            ],
        ]];
    },
];
