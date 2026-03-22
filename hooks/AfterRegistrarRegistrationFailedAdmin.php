<?php

/**
 * Automas SMS - When a domain registration fails. Sent to the admin numbers on this template.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterRegistrarRegistrationFailedAdmin',
    'hook'        => 'AfterRegistrarRegistrationFailed',
    'type'        => 'admin',
    'description' => 'When a domain registration fails',
    'extra'       => '',
    'default'     => 'An error occurred while registering the domain {domain}.',
    'variables'   => '{domain},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $params = isset($args['params']) ? $args['params'] : [];

        $sld = isset($params['sld']) ? $params['sld'] : '';
        $tld = isset($params['tld']) ? $params['tld'] : '';
        $domain = trim($sld . '.' . $tld, '.');

        if ($domain === '' && isset($params['domain'])) {
            $domain = $params['domain'];
        }

        return [[
            'tokens' => ['{domain}' => $domain],
        ]];
    },
];
