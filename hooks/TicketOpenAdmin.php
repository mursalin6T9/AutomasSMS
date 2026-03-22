<?php

/**
 * Automas SMS - alert the admin numbers when a ticket is opened.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'TicketOpenAdmin',
    'hook'        => 'TicketOpen',
    'type'        => 'admin',
    'description' => 'When a new ticket is created',
    'extra'       => '',
    'default'     => 'A new ticket #{ticketno} with the subject "{subject}" has been created.',
    'variables'   => '{subject},{ticketno},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        return [[
            'tokens' => [
                '{subject}'  => isset($args['subject']) ? $args['subject'] : '',
                '{ticketno}' => isset($args['ticketmask']) ? $args['ticketmask'] : (isset($args['ticketid']) ? $args['ticketid'] : ''),
            ],
        ]];
    },
];
