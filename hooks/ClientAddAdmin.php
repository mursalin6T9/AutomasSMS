<?php

/**
 * Automas SMS - alert the admin numbers when a client registers.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'ClientAddAdmin',
    'hook'        => 'ClientAdd',
    'type'        => 'admin',
    'description' => 'When a client is added',
    'extra'       => '',
    'default'     => 'A new customer, {firstname} {lastname}, has registered on {company}.',
    'variables'   => '{firstname},{lastname},{email},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $client = $ctx->client(isset($args['userid']) ? $args['userid'] : 0);

        return [[
            'tokens' => [
                '{firstname}' => $client ? $client->firstname : '',
                '{lastname}'  => $client ? $client->lastname : '',
                '{email}'     => isset($args['email']) ? $args['email'] : ($client ? $client->email : ''),
            ],
        ]];
    },
];
