<?php

namespace AutomasSMS;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Read-only lookups shared by every hook resolver.
 *
 * All queries go through Capsule with bound parameters - hook arguments are
 * never concatenated into a SQL string.
 */
class Context
{
    private $clients = [];
    private $company;

    public function company()
    {
        if ($this->company !== null) {
            return $this->company;
        }

        try {
            $value = Capsule::table('tblconfiguration')
                ->where('setting', 'CompanyName')
                ->value('value');
        } catch (\Exception $e) {
            $value = '';
        }

        $this->company = (string) $value;

        return $this->company;
    }

    /**
     * @return object|null id, firstname, lastname, phonenumber, country
     */
    public function client($clientId)
    {
        $clientId = (int) $clientId;

        if ($clientId <= 0) {
            return null;
        }

        if (array_key_exists($clientId, $this->clients)) {
            return $this->clients[$clientId];
        }

        try {
            $row = Capsule::table('tblclients')
                ->select('id', 'firstname', 'lastname', 'phonenumber', 'email', 'country')
                ->where('id', $clientId)
                ->first();
        } catch (\Exception $e) {
            $row = null;
        }

        $this->clients[$clientId] = $row;

        return $row;
    }

    /**
     * Invoice joined to its client.
     *
     * @return object|null
     */
    public function invoice($invoiceId)
    {
        try {
            return Capsule::table('tblinvoices as i')
                ->join('tblclients as c', 'c.id', '=', 'i.userid')
                ->select(
                    'i.id as invoiceid',
                    'i.date',
                    'i.duedate',
                    'i.datepaid',
                    'i.total',
                    'c.id as userid',
                    'c.firstname',
                    'c.lastname',
                    'c.phonenumber',
                    'c.country'
                )
                ->where('i.id', (int) $invoiceId)
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Ticket joined to its client. Departments can hold tickets with no client
     * (userid 0), in which case null is returned.
     *
     * @return object|null
     */
    public function ticket($ticketId)
    {
        try {
            return Capsule::table('tbltickets as t')
                ->join('tblclients as c', 'c.id', '=', 't.userid')
                ->select(
                    't.id as ticketid',
                    't.tid',
                    't.title',
                    'c.id as userid',
                    'c.firstname',
                    'c.lastname',
                    'c.phonenumber',
                    'c.country'
                )
                ->where('t.id', (int) $ticketId)
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Client id behind an order.
     */
    public function orderClientId($orderId)
    {
        try {
            return (int) Capsule::table('tblorders')->where('id', (int) $orderId)->value('userid');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Hosting service joined to its product.
     *
     * Resolved from the database rather than from $params['model'], because that
     * key is an Eloquent model whose relations are not guaranteed to be loaded
     * when a provisioning hook fires.
     *
     * @return object|null
     */
    public function service($serviceId)
    {
        try {
            return Capsule::table('tblhosting as h')
                ->leftJoin('tblproducts as p', 'p.id', '=', 'h.packageid')
                ->select(
                    'h.id',
                    'h.userid',
                    'h.domain',
                    'h.username',
                    'p.name as product'
                )
                ->where('h.id', (int) $serviceId)
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Active domains expiring exactly $days from today, with their client.
     *
     * @return object[]
     */
    public function domainsExpiringIn($days)
    {
        $days = (int) $days;

        $target = new \DateTime('now', new \DateTimeZone(AUTOMASSMS_TIMEZONE));
        $target->modify('+' . $days . ' days');

        try {
            return Capsule::table('tbldomains as d')
                ->join('tblclients as c', 'c.id', '=', 'd.userid')
                ->select(
                    'd.domain',
                    'd.expirydate',
                    'c.id as userid',
                    'c.firstname',
                    'c.lastname',
                    'c.phonenumber'
                )
                ->where('d.status', 'Active')
                ->where('d.expirydate', $target->format('Y-m-d'))
                ->get()
                ->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format a MySQL date for display inside an SMS. Matches the dd.mm.yyyy the
     * previous module produced.
     */
    public function date($value)
    {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('d.m.Y', $timestamp) : (string) $value;
    }

    /**
     * Format a money amount as WHMCS stores it on the invoice.
     */
    public function amount($value)
    {
        return number_format((float) $value, 2, '.', '');
    }
}
