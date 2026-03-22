<?php

/**
 * Automas SMS - notify the client after a service password change.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterModuleChangePassword',
    'hook'        => 'AfterModuleChangePassword',
    'type'        => 'client',
    'description' => 'After module password changed',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, the password for {domain} has been changed. New password: {newpassword}.',
    'variables'   => '{firstname},{lastname},{domain},{username},{newpassword},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $params = isset($args['params']) ? $args['params'] : [];

        $serviceId = isset($args['serviceid'])
            ? (int) $args['serviceid']
            : (isset($params['serviceid']) ? (int) $params['serviceid'] : 0);

        $service = $ctx->service($serviceId);

        if (!$service) {
            return [];
        }

        $client = $ctx->client($service->userid);

        if (!$client) {
            return [];
        }

        $newPassword = '';

        foreach (['newpassword', 'password'] as $key) {
            if (!empty($args[$key])) {
                $newPassword = $args[$key];
                break;
            }

            if (!empty($params[$key])) {
                $newPassword = $params[$key];
                break;
            }
        }

        return [[
            'client_id' => $client->id,
            'msisdn'    => $client->phonenumber,
            'tokens'    => [
                '{firstname}'   => $client->firstname,
                '{lastname}'    => $client->lastname,
                '{domain}'      => $service->domain,
                '{username}'    => $service->username,
                '{newpassword}' => $newPassword,
            ],
        ]];
    },
];
