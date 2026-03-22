<?php

/**
 * Automas SMS - tell the client an admin has replied to their ticket.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'TicketAdminReply',
    'hook'        => 'TicketAdminReply',
    'type'        => 'client',
    'description' => 'After Reply By Admin',
    'extra'       => '',
    'default'     => 'Dear {firstname} {lastname}, your ticket #{ticketno} ({ticketsubject}) has been answered by our support team.',
    'variables'   => '{firstname},{lastname},{ticketsubject},{ticketno},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $ticket = $ctx->ticket(isset($args['ticketid']) ? $args['ticketid'] : 0);

        if (!$ticket) {
            return [];
        }

        return [[
            'client_id' => $ticket->userid,
            'msisdn'    => $ticket->phonenumber,
            'tokens'    => [
                '{firstname}'     => $ticket->firstname,
                '{lastname}'      => $ticket->lastname,
                '{ticketsubject}' => isset($args['subject']) ? $args['subject'] : $ticket->title,
                '{ticketno}'      => isset($args['ticketmask']) ? $args['ticketmask'] : $ticket->tid,
            ],
        ]];
    },
];
