<?php

/**
 * Automas SMS - Domain Registration Failure.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterRegistrarRegistrationFailed',
    'hook'        => 'AfterRegistrarRegistrationFailed',
    'type'        => 'client',
    'description' => 'Domain Registration Failure',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, your domain {domain} could not be registered. Our team will contact you shortly.',
    'variables'   => '{firstname},{lastname},{domain},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $params = isset($args['params']) ? $args['params'] : [];
        $client = $ctx->client(isset($params['userid']) ? $params['userid'] : 0);

        if (!$client) {
            return [];
        }

        $sld = isset($params['sld']) ? $params['sld'] : '';
        $tld = isset($params['tld']) ? $params['tld'] : '';
        $domain = trim($sld . '.' . $tld, '.');

        if ($domain === '' && isset($params['domain'])) {
            $domain = $params['domain'];
        }

        return [[
            'client_id' => $client->id,
            'msisdn'    => $client->phonenumber,
            'tokens'    => [
                '{firstname}' => $client->firstname,
                '{lastname}'  => $client->lastname,
                '{domain}'    => $domain,
            ],
        ]];
    },
];
