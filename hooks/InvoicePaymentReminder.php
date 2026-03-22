<?php

/**
 * Automas SMS - Invoice Payment Reminder.
 *
 * All four reminder templates bind to the same WHMCS hook and select themselves
 * on the reminder type it passes in.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'InvoicePaymentReminder',
    'hook'        => 'InvoicePaymentReminder',
    'type'        => 'client',
    'description' => 'Invoice Payment Reminder',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, the due date for invoice #{invoiceid} is {duedate}. Kindly make the payment for uninterrupted services.',
    'variables'   => '{firstname},{lastname},{date},{duedate},{total},{invoiceid},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $type = isset($args['type']) ? $args['type'] : '';

        if ($type !== 'reminder') {
            return [];
        }

        $invoice = $ctx->invoice(isset($args['invoiceid']) ? $args['invoiceid'] : 0);

        if (!$invoice) {
            return [];
        }

        return [[
            'client_id' => $invoice->userid,
            'msisdn'    => $invoice->phonenumber,
            'tokens'    => [
                '{firstname}' => $invoice->firstname,
                '{lastname}'  => $invoice->lastname,
                '{date}'      => $ctx->date($invoice->date),
                '{duedate}'   => $ctx->date($invoice->duedate),
                '{total}'     => $ctx->amount($invoice->total),
                '{invoiceid}' => $invoice->invoiceid,
            ],
        ]];
    },
];
