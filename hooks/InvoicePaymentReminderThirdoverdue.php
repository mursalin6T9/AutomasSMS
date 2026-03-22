<?php

/**
 * Automas SMS - Invoice payment reminder - third overdue notice.
 *
 * All four reminder templates bind to the same WHMCS hook and select themselves
 * on the reminder type it passes in.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'InvoicePaymentReminderThirdoverdue',
    'hook'        => 'InvoicePaymentReminder',
    'type'        => 'client',
    'description' => 'Invoice payment reminder - third overdue notice',
    'extra'       => '',
    'default'     => 'Hi {firstname} {lastname}, the payment for invoice #{invoiceid} due on {duedate} remains outstanding. Kindly make the payment at the earliest to continue enjoying the services.',
    'variables'   => '{firstname},{lastname},{date},{duedate},{total},{invoiceid},{company}',
    'resolve'     => function (array $args, Context $ctx) {
        $type = isset($args['type']) ? $args['type'] : '';

        if ($type !== 'thirdoverdue') {
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
