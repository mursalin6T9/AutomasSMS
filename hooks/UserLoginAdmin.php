<?php

/**
 * Automas SMS - alert the admin numbers when a client signs in.
 *
 * Bound to ClientLogin rather than the old module's UserLogin: since the WHMCS
 * 8.0 user/account split, UserLogin passes a User model with no client id, while
 * ClientLogin still hands over the userid this notification needs.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'UserLoginAdmin',
    'hook'        => 'ClientLogin',
    'type'        => 'admin',
    'description' => 'When a client signs into the client area',
    'extra'       => '',
    'default'     => 'Client {firstname} {lastname} has signed into {company}.',
    'variables'   => '{firstname},{lastname},{email},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $client = $ctx->client(isset($args['userid']) ? $args['userid'] : 0);

        if (!$client) {
            return [];
        }

        return [[
            'tokens' => [
                '{firstname}' => $client->firstname,
                '{lastname}'  => $client->lastname,
                '{email}'     => isset($args['email']) ? $args['email'] : $client->email,
            ],
        ]];
    },
];
