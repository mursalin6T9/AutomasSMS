<?php

/**
 * Automas SMS - After Domain Registration.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterRegistrarRegistration',
    'hook'        => 'AfterRegistrarRegistration',
    'type'        => 'client',
    'description' => 'After Domain Registration',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, your domain {domain} has been registered successfully.',
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
