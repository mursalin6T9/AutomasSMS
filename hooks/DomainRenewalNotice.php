<?php

/**
 * Automas SMS - remind clients that a domain expires in {x} days.
 *
 * Runs from the daily cron. The notice window is the "Days" field on the
 * template in the admin UI.
 */

use AutomasSMS\Context;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

return [
    'key'         => 'DomainRenewalNotice',
    'hook'        => 'DailyCronJob',
    'type'        => 'client',
    'description' => 'Domain renewal notice, sent {x} days before expiry',
    'extra'       => '15',
    'default'     => 'Hi {firstname} {lastname}, your domain {domain} expires in {x} days, on {expirydate}. Kindly renew it to avoid interruption.',
    'variables'   => '{firstname},{lastname},{domain},{expirydate},{x},{company}',
    'resolve'     => function (array $args, Context $ctx, $template) {
        $days = (int) $template->extra_info;

        if ($days <= 0) {
            return [];
        }

        $targets = [];

        // One query for every domain hitting the window today, rather than the
        // full domain table walked row by row in PHP.
        foreach ($ctx->domainsExpiringIn($days) as $domain) {
            $targets[] = [
                'client_id' => $domain->userid,
                'msisdn'    => $domain->phonenumber,
                'tokens'    => [
                    '{firstname}'  => $domain->firstname,
                    '{lastname}'   => $domain->lastname,
                    '{domain}'     => $domain->domain,
                    '{expirydate}' => $ctx->date($domain->expirydate),
                    '{x}'          => $days,
                ],
            ];
        }

        return $targets;
    },
];
