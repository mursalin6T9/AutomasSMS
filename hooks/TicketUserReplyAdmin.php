<?php

/**
 * Automas SMS - alert the admin numbers when a client replies to a ticket.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'TicketUserReplyAdmin',
    'hook'        => 'TicketUserReply',
    'type'        => 'admin',
    'description' => 'When a user has replied on a ticket',
    'extra'       => '',
    'default'     => 'A user has replied to ticket #{ticketno} with the subject "{subject}".',
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
