<?php

/**
 * Automas SMS - When a domain is registered. Sent to the admin numbers on this template.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterRegistrarRegistrationAdmin',
    'hook'        => 'AfterRegistrarRegistration',
    'type'        => 'admin',
    'description' => 'When a domain is registered',
    'extra'       => '',
    'default'     => 'New domain {domain} has been registered.',
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
