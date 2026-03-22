<?php

/**
 * Automas SMS - send login details once a service has been provisioned.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterModuleCreateHosting',
    'hook'        => 'AfterModuleCreate',
    'type'        => 'client',
    'description' => 'Post Service Activation',
    'extra'       => '',
    'default'     => 'Hello {firstname} {lastname}, the service for {domain} is now active. Login details - Username: {username} Password: {password}.',
    'variables'   => '{firstname},{lastname},{domain},{username},{password},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $params = isset($args['params']) ? $args['params'] : [];
        $clientId = isset($params['clientsdetails']['userid']) ? (int) $params['clientsdetails']['userid'] : 0;
        $client = $ctx->client($clientId);

        if (!$client) {
            return [];
        }

        return [[
            'client_id' => $client->id,
            'msisdn'    => $client->phonenumber,
            'tokens'    => [
                '{firstname}' => $client->firstname,
                '{lastname}'  => $client->lastname,
                '{domain}'    => isset($params['domain']) ? $params['domain'] : '',
                '{username}'  => isset($params['username']) ? $params['username'] : '',
                '{password}'  => isset($params['password']) ? $params['password'] : '',
            ],
        ]];
    },
];
