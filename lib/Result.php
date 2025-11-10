<?php

namespace AutomasSMS;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * The outcome of a single recipient within an Automas API call.
 *
 * Automas has no delivery-report endpoint, so whatever comes back in the send
 * response is final - there is nothing to poll later.
 */
class Result
{
    /** @var string Normalised MSISDN this result belongs to. */
    public $msisdn;

    /** @var int Automas status code (0 = success). */
    public $statusCode;

    /** @var string|null Gateway side message id ("id" on v4, "sid" on smsapimany). */
    public $smsId;

    /** @var string|null Our own reference echoed back as "cid" by smsapimany. */
    public $reference;

    public function __construct($msisdn, $statusCode, $smsId = null, $reference = null)
    {
        $this->msisdn = $msisdn;
        $this->statusCode = (int) $statusCode;
        $this->smsId = $smsId;
        $this->reference = $reference;
    }

    public function isSuccess()
    {
        return StatusCode::isSuccess($this->statusCode);
    }

    public function meaning()
    {
        return StatusCode::meaning($this->statusCode);
    }

    public function status()
    {
        return $this->isSuccess() ? 'sent' : 'failed';
    }
}
