<?php

/**
 * Automas SMS - After Module Suspension.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterModuleSuspend',
    'hook'        => 'AfterModuleSuspend',
    'type'        => 'client',
    'description' => 'After Module Suspension',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, the service {product} has been suspended. Kindly contact us for more details.',
    'variables'   => '{firstname},{lastname},{product},{domain},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $params = isset($args['params']) ? $args['params'] : [];
        $clientId = isset($params['clientsdetails']['userid']) ? (int) $params['clientsdetails']['userid'] : 0;
        $client = $ctx->client($clientId);

        if (!$client) {
            return [];
        }

        $service = $ctx->service(isset($params['serviceid']) ? $params['serviceid'] : 0);

        return [[
            'client_id' => $client->id,
            'msisdn'    => $client->phonenumber,
            'tokens'    => [
                '{firstname}' => $client->firstname,
                '{lastname}'  => $client->lastname,
                '{product}'   => $service ? $service->product : '',
                '{domain}'    => isset($params['domain']) ? $params['domain'] : ($service ? $service->domain : ''),
            ],
        ]];
    },
];
