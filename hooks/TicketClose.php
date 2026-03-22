<?php

/**
 * Automas SMS - tell the client their ticket has been closed.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'TicketClose',
    'hook'        => 'TicketClose',
    'type'        => 'client',
    'description' => 'Ticket Closure',
    'extra'       => '',
    'default'     => 'Hello {firstname} {lastname}, ticket #{ticketno} has been closed. If the issue persists, kindly contact us again.',
    'variables'   => '{firstname},{lastname},{ticketno},{ticketsubject},{company}',
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
                '{ticketno}'      => isset($args['ticketmask']) ? $args['ticketmask'] : $ticket->tid,
                '{ticketsubject}' => isset($args['subject']) ? $args['subject'] : $ticket->title,
            ],
        ]];
    },
];
