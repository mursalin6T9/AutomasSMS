<?php

/**
 * Automas SMS - When a domain renewal fails. Sent to the admin numbers on this template.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterRegistrarRenewalFailedAdmin',
    'hook'        => 'AfterRegistrarRenewalFailed',
    'type'        => 'admin',
    'description' => 'When a domain renewal fails',
    'extra'       => '',
    'default'     => 'An error occurred while renewing the domain {domain}.',
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
