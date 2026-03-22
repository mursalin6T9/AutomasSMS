<?php

/**
 * Automas SMS - When a domain is renewed. Sent to the admin numbers on this template.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AfterRegistrarRenewalAdmin',
    'hook'        => 'AfterRegistrarRenewal',
    'type'        => 'admin',
    'description' => 'When a domain is renewed',
    'extra'       => '',
    'default'     => 'The domain {domain} has been renewed.',
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
