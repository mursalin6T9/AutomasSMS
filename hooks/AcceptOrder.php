<?php

/**
 * Automas SMS - notify the client when their order is accepted.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AcceptOrder',
    'hook'        => 'AcceptOrder',
    'type'        => 'client',
    'description' => 'Post Order Acceptance',
    'extra'       => '',
    'default'     => 'Dear {firstname} {lastname}, your order #{orderid} has been approved.',
    'variables'   => '{firstname},{lastname},{orderid},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $orderId = isset($args['orderid']) ? (int) $args['orderid'] : 0;
        $client = $ctx->client($ctx->orderClientId($orderId));

        if (!$client) {
            return [];
        }

        return [[
            'client_id' => $client->id,
            'msisdn'    => $client->phonenumber,
            'tokens'    => [
                '{firstname}' => $client->firstname,
                '{lastname}'  => $client->lastname,
                '{orderid}'   => $orderId,
            ],
        ]];
    },
];
