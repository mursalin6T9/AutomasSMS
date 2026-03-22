<?php

/**
 * Automas SMS - alert the admin numbers when someone signs into the admin area.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'AdminLogin',
    'hook'        => 'AdminLogin',
    'type'        => 'admin',
    'description' => 'When an admin signs into the admin area',
    'extra'       => '',
    'default'     => 'Admin user {username} has signed into the {company} admin panel.',
    'variables'   => '{username},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        return [[
            'tokens' => [
                '{username}' => isset($args['username']) ? $args['username'] : '',
            ],
        ]];
    },
];
