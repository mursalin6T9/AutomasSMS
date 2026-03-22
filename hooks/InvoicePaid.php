<?php

/**
 * Automas SMS - confirm receipt of an invoice payment.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'InvoicePaid',
    'hook'        => 'InvoicePaid',
    'type'        => 'client',
    'description' => 'Post Payment',
    'extra'       => '',
    'default'     => 'Dear {firstname} {lastname}, payment for invoice #{invoiceid} has been received. Thank you.',
    'variables'   => '{firstname},{lastname},{date},{duedate},{datepaid},{total},{invoiceid},{company}',
    'resolve'     => function (array $args, Context $ctx) {
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
                '{datepaid}'  => $ctx->date($invoice->datepaid),
                '{total}'     => $ctx->amount($invoice->total),
                '{invoiceid}' => $invoice->invoiceid,
            ],
        ]];
    },
];
