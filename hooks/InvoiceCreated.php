<?php

/**
 * Automas SMS - notify the client when an invoice is generated.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'InvoiceCreated',
    'hook'        => 'InvoiceCreated',
    'type'        => 'client',
    'description' => 'After Invoice Creation',
    'extra'       => '',
    'default'     => 'Hello {firstname} {lastname}, your invoice #{invoiceid} has been generated. Total amount is {total}. The last day of payment is {duedate}. Kindly pay before the due date to use services without interruption.',
    'variables'   => '{firstname},{lastname},{date},{duedate},{total},{invoiceid},{company}',
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
                '{total}'     => $ctx->amount($invoice->total),
                '{invoiceid}' => $invoice->invoiceid,
            ],
        ]];
    },
];
