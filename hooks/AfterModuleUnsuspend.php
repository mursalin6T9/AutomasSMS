<?php

/**
 * Automas SMS - After module unsuspend.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterModuleUnsuspend',
    'hook'        => 'AfterModuleUnsuspend',
    'type'        => 'client',
    'description' => 'After module unsuspend',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, the service {product} has been reactivated.',
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
