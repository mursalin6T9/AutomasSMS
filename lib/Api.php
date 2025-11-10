<?php

namespace AutomasSMS;

use Exception;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Client for the Automas SMS HTTP API (https://api.automas.com.bd).
 *
 * Three send transports are documented; this module uses two of them:
 *
 *   smsapiv4    JSON. One message body to one or many recipients ("contacts"
 *               joined with "+"). Supports scheduling. Used for hook
 *               notifications and single/broadcast sends.
 *   smsapimany  JSON. A distinct body per recipient in a single request. Used
 *               for personalised broadcasts, so a 500 client mail-merge is one
 *               HTTP call instead of 500.
 *
 * smsapiv3 (plain GET/POST) is implemented for completeness but is not used by
 * the module - it offers nothing v4 does not, and cannot schedule.
 *
 * Every send returns Result[] keyed by MSISDN. Note that Automas exposes no
 * delivery report endpoint, so the status in that response is the final word.
 */
class Api
{
    const BASE_URL = 'https://api.automas.com.bd';

    const ENDPOINT_V3      = '/smsapiv3';
    const ENDPOINT_V4      = '/smsapiv4';
    const ENDPOINT_DYNAMIC = '/smsapimany';
    const ENDPOINT_BALANCE = '/getbalancev3';

    /** Automas caps a bulk "contacts" list; chunk to stay well inside it. */
    const MAX_RECIPIENTS_PER_REQUEST = 500;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $senderId;

    /**
     * Value sent as "type" for UCS-2 messages.
     *
     * The docs show `"type": "text"` for ASCII but describe the Unicode variant
     * only as "use 8" (borrowed from the v3 `smsformat=8` parameter), so the
     * correct v4 value is ambiguous. It is exposed as a module setting rather
     * than hard-coded, because getting it wrong silently mangles Bangla text.
     *
     * @var string
     */
    private $unicodeType;

    /** @var int */
    private $timeout;

    /** @var string|null Raw body of the most recent response, for logging. */
    private $lastResponse;

    public function __construct($apiKey, $senderId = '', $unicodeType = 'unicode', $timeout = 30)
    {
        $this->apiKey = trim((string) $apiKey);
        $this->senderId = trim((string) $senderId);
        $this->unicodeType = trim((string) $unicodeType) ?: 'unicode';
        $this->timeout = (int) $timeout;
    }

    public function hasCredentials()
    {
        return $this->apiKey !== '';
    }

    public function lastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Send one message body to one or more recipients via smsapiv4.
     *
     * @param string   $message
     * @param string[] $recipients Normalised MSISDNs.
     * @param string|null $schedule "Y-m-d H:i:s" in Asia/Dhaka, or null to send now.
     * @return Result[] Keyed by MSISDN.
     */
    public function send($message, array $recipients, $schedule = null)
    {
        $this->assertCredentials();

        $recipients = array_values(array_unique(array_filter($recipients)));

        if (!$recipients) {
            return [];
        }

        $measured = Text::measure($message);
        $results = [];

        foreach (array_chunk($recipients, self::MAX_RECIPIENTS_PER_REQUEST) as $chunk) {
            $payload = [
                'api_key'           => $this->apiKey,
                'senderid'          => $this->senderId,
                'type'              => $measured['encoding'] === 'unicode' ? $this->unicodeType : 'text',
                'scheduledDateTime' => $schedule === null ? '' : $schedule,
                'msg'               => $message,
                // The docs join bulk contacts with "+", not a comma.
                'contacts'          => implode('+', $chunk),
            ];

            $rows = $this->extractRows($this->postJson(self::ENDPOINT_V4, $payload), $chunk);

            foreach ($rows as $index => $row) {
                $msisdn = isset($row['msisdn']) && $row['msisdn'] !== ''
                    ? Phone::normalise($row['msisdn'])
                    : null;

                if ($msisdn === null) {
                    // Fall back to positional matching if the gateway echoed nothing useful.
                    $msisdn = isset($chunk[$index]) ? $chunk[$index] : null;
                }

                if ($msisdn === null) {
                    continue;
                }

                $results[$msisdn] = new Result(
                    $msisdn,
                    isset($row['status']) ? $row['status'] : StatusCode::MALFORMED_RESPONSE,
                    isset($row['id']) ? (string) $row['id'] : null
                );
            }

            // Anything the gateway did not mention at all still needs a log row.
            foreach ($chunk as $msisdn) {
                if (!isset($results[$msisdn])) {
                    $results[$msisdn] = new Result($msisdn, StatusCode::MALFORMED_RESPONSE);
                }
            }
        }

        return $results;
    }

    /**
     * Send a distinct body per recipient in one request via smsapimany.
     *
     * @param array $messages List of ['msisdn' => string, 'text' => string].
     * @return Result[] Keyed by MSISDN.
     */
    public function sendDynamic(array $messages)
    {
        $this->assertCredentials();

        $messages = array_values(array_filter($messages, function ($item) {
            return !empty($item['msisdn']) && isset($item['text']) && $item['text'] !== '';
        }));

        if (!$messages) {
            return [];
        }

        $results = [];

        foreach (array_chunk($messages, self::MAX_RECIPIENTS_PER_REQUEST) as $chunk) {
            $rows = [];
            $byReference = [];
            $reference = 1;

            foreach ($chunk as $item) {
                $rows[] = [
                    'id'      => $reference,
                    'msisdn'  => $item['msisdn'],
                    'smstext' => $item['text'],
                ];
                $byReference[$reference] = $item['msisdn'];
                $reference++;
            }

            $payload = [
                'apikey'   => $this->apiKey,
                'sender'   => $this->senderId,
                'messages' => $rows,
            ];

            $expected = array_values($byReference);
            $responseRows = $this->extractRows($this->postJson(self::ENDPOINT_DYNAMIC, $payload), $expected);

            foreach ($responseRows as $index => $row) {
                $cid = isset($row['cid']) ? (int) $row['cid'] : null;

                if ($cid !== null && isset($byReference[$cid])) {
                    $msisdn = $byReference[$cid];
                } elseif (!empty($row['msisdn']) && Phone::normalise($row['msisdn']) !== null) {
                    $msisdn = Phone::normalise($row['msisdn']);
                } else {
                    $msisdn = isset($expected[$index]) ? $expected[$index] : null;
                }

                if ($msisdn === null) {
                    continue;
                }

                $results[$msisdn] = new Result(
                    $msisdn,
                    isset($row['status']) ? $row['status'] : StatusCode::MALFORMED_RESPONSE,
                    isset($row['sid']) ? (string) $row['sid'] : null,
                    $cid === null ? null : (string) $cid
                );
            }

            foreach ($expected as $msisdn) {
                if (!isset($results[$msisdn])) {
                    $results[$msisdn] = new Result($msisdn, StatusCode::MALFORMED_RESPONSE);
                }
            }
        }

        return $results;
    }

    /**
     * Plain HTTP transport (smsapiv3). Kept for parity with the docs; the module
     * itself sends through v4.
     *
     * @return Result[]
     */
    public function sendPlain($message, array $recipients)
    {
        $this->assertCredentials();

        $recipients = array_values(array_unique(array_filter($recipients)));

        if (!$recipients) {
            return [];
        }

        $measured = Text::measure($message);

        $params = [
            'apikey'  => $this->apiKey,
            'sender'  => $this->senderId,
            // v3 joins with a comma, unlike v4.
            'msisdn'  => implode(',', $recipients),
            'smstext' => $message,
        ];

        if ($measured['parts'] > 1) {
            $params['type'] = 'long';
        }

        if ($measured['encoding'] === 'unicode') {
            $params['smsformat'] = '8';
        }

        $rows = $this->extractRows($this->postForm(self::ENDPOINT_V3, $params), $recipients);
        $results = [];

        foreach ($rows as $index => $row) {
            $msisdn = !empty($row['msisdn']) ? Phone::normalise($row['msisdn']) : null;

            if ($msisdn === null) {
                $msisdn = isset($recipients[$index]) ? $recipients[$index] : null;
            }

            if ($msisdn === null) {
                continue;
            }

            $results[$msisdn] = new Result(
                $msisdn,
                isset($row['status']) ? $row['status'] : StatusCode::MALFORMED_RESPONSE,
                isset($row['id']) ? (string) $row['id'] : null
            );
        }

        return $results;
    }

    /**
     * Remaining account balance in BDT.
     *
     * @return float
     * @throws Exception when the key is rejected or the API is unreachable.
     */
    public function balance()
    {
        $this->assertCredentials();

        $decoded = $this->get(self::ENDPOINT_BALANCE, ['apikey' => $this->apiKey]);

        if (!array_key_exists('response', $decoded)) {
            throw new Exception(StatusCode::meaning(StatusCode::MALFORMED_RESPONSE));
        }

        $value = $decoded['response'];

        // A bare integer here is a status code, not a balance - e.g. 106 for a bad key.
        if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value) && isset(StatusCode::all()[(int) $value]) && (int) $value !== 0)) {
            throw new Exception(StatusCode::meaning((int) $value));
        }

        if (is_array($value)) {
            $first = reset($value);

            if (is_array($first) && isset($first['status']) && !StatusCode::isSuccess($first['status'])) {
                throw new Exception(StatusCode::meaning($first['status']));
            }

            throw new Exception(StatusCode::meaning(StatusCode::MALFORMED_RESPONSE));
        }

        if (!is_numeric($value)) {
            throw new Exception(StatusCode::meaning(StatusCode::MALFORMED_RESPONSE));
        }

        return (float) $value;
    }

    /**
     * Cheap credential check used when saving settings.
     *
     * @return array{ok: bool, message: string, balance: float|null}
     */
    public function verify()
    {
        try {
            $balance = $this->balance();

            return [
                'ok'      => true,
                'message' => 'API key accepted.',
                'balance' => $balance,
            ];
        } catch (Exception $e) {
            return [
                'ok'      => false,
                'message' => $e->getMessage(),
                'balance' => null,
            ];
        }
    }

    /**
     * Normalise the various documented response envelopes into a list of rows.
     *
     * Success looks like {"response":[{...},{...}]}. Failures have been observed
     * as a bare code, so those are expanded into one failed row per recipient.
     *
     * @param array    $decoded
     * @param string[] $recipients
     * @return array<int, array>
     */
    private function extractRows(array $decoded, array $recipients)
    {
        if (isset($decoded['__transport_error'])) {
            return $this->failAll($recipients, StatusCode::TRANSPORT_ERROR);
        }

        if (!array_key_exists('response', $decoded)) {
            // Some deployments return the array at the top level.
            if ($decoded && isset($decoded[0]) && is_array($decoded[0])) {
                return array_values($decoded);
            }

            return $this->failAll($recipients, StatusCode::MALFORMED_RESPONSE);
        }

        $response = $decoded['response'];

        if (is_array($response)) {
            // A single object rather than a list of them.
            if (isset($response['status']) || isset($response['msisdn'])) {
                return [$response];
            }

            return array_values($response);
        }

        if (is_numeric($response)) {
            return $this->failAll($recipients, (int) $response);
        }

        return $this->failAll($recipients, StatusCode::MALFORMED_RESPONSE);
    }

    private function failAll(array $recipients, $code)
    {
        $rows = [];

        foreach ($recipients as $msisdn) {
            $rows[] = ['status' => $code, 'msisdn' => $msisdn];
        }

        return $rows;
    }

    private function assertCredentials()
    {
        if (!$this->hasCredentials()) {
            throw new Exception('No Automas API key has been configured.');
        }
    }

    private function postJson($endpoint, array $payload)
    {
        return $this->request('POST', $endpoint, [
            'body'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'headers' => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
    }

    private function postForm($endpoint, array $params)
    {
        return $this->request('POST', $endpoint, [
            'body'    => http_build_query($params),
            'headers' => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        ]);
    }

    private function get($endpoint, array $params)
    {
        return $this->request('GET', $endpoint . '?' . http_build_query($params), [
            'headers' => ['Accept: application/json'],
        ]);
    }

    /**
     * @return array Decoded body, or ['__transport_error' => string] when the call failed.
     */
    protected function request($method, $endpoint, array $options)
    {
        $this->lastResponse = null;

        $handle = curl_init();

        curl_setopt_array($handle, [
            CURLOPT_URL            => self::BASE_URL . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => isset($options['headers']) ? $options['headers'] : [],
            CURLOPT_USERAGENT      => 'WHMCS-AutomasSMS/' . AUTOMASSMS_VERSION,
        ]);

        if ($method === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, isset($options['body']) ? $options['body'] : '');
        }

        $body = curl_exec($handle);
        $error = curl_error($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        if ($body === false || $error !== '') {
            $this->lastResponse = 'cURL error: ' . $error;

            return ['__transport_error' => $error ?: 'Request failed'];
        }

        $this->lastResponse = $body;

        if ($httpCode >= 400) {
            $this->lastResponse = 'HTTP ' . $httpCode . ': ' . $body;

            return ['__transport_error' => 'HTTP ' . $httpCode];
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
