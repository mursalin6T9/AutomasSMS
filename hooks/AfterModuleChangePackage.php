<?php

/**
 * Automas SMS - notify the client after their package is changed.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterModuleChangePackage',
    'hook'        => 'AfterModuleChangePackage',
    'type'        => 'client',
    'description' => 'Following Module Package Change',
    'extra'       => '',
    'default'     => 'Hello {firstname} {lastname}, the package for {domain} has been changed. Kindly contact us for more details.',
    'variables'   => '{firstname},{lastname},{domain},{product},{company}',
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
                '{domain}'    => isset($params['domain']) ? $params['domain'] : '',
                '{product}'   => $service ? $service->product : '',
            ],
        ]];
    },
];
