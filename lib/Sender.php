<?php

namespace AutomasSMS;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * The single place messages leave the module.
 *
 * Takes rendered jobs, picks the cheapest Automas transport for them, writes one
 * log row per recipient and returns a summary.
 */
class Sender
{
    /**
     * @param array  $jobs   List of ['msisdn' => string, 'text' => string, 'client_id' => int].
     * @param string $source Hook key, or 'manual' for the Send SMS tab.
     * @param string|null $schedule "Y-m-d H:i:s" (Asia/Dhaka) for a future send.
     * @return array{sent: int, failed: int, skipped: int, errors: string[], batch: string}
     */
    public static function dispatch(array $jobs, $source = 'manual', $schedule = null)
    {
        $batch = self::batchId();

        $summary = [
            'sent'    => 0,
            'failed'  => 0,
            'skipped' => 0,
            'errors'  => [],
            'batch'   => $batch,
        ];

        if (!Settings::isConfigured()) {
            $summary['errors'][] = 'No Automas API key has been configured.';
            logActivity('Automas SMS (' . $source . '): no API key configured, message not sent.');

            return $summary;
        }

        // Normalise, drop anything unusable, and collapse duplicate recipients.
        $prepared = [];

        foreach ($jobs as $job) {
            $msisdn = Phone::normalise(isset($job['msisdn']) ? $job['msisdn'] : null);
            $text = isset($job['text']) ? trim((string) $job['text']) : '';

            if ($msisdn === null) {
                $summary['skipped']++;
                $summary['errors'][] = 'Invalid mobile number: ' . (isset($job['msisdn']) ? $job['msisdn'] : '(empty)');
                continue;
            }

            if ($text === '') {
                $summary['skipped']++;
                $summary['errors'][] = 'Empty message body for ' . $msisdn;
                continue;
            }

            $prepared[$msisdn] = [
                'msisdn'    => $msisdn,
                'text'      => $text,
                'client_id' => isset($job['client_id']) ? (int) $job['client_id'] : 0,
            ];
        }

        if (!$prepared) {
            return $summary;
        }

        $api = Settings::api();
        $senderId = (string) Settings::get('sender_id', '');

        try {
            $bodies = array_unique(array_column($prepared, 'text'));

            if (count($bodies) === 1 || $schedule !== null) {
                // One body for everyone (or a scheduled send, which smsapimany
                // does not support) - smsapiv4 handles the whole list in one call.
                $results = [];

                foreach (self::groupByText($prepared) as $text => $recipients) {
                    $results += $api->send($text, $recipients, $schedule);
                }
            } else {
                // Personalised bodies - one smsapimany call instead of N sends.
                $results = $api->sendDynamic(array_values($prepared));
            }
        } catch (\Exception $e) {
            $results = [];

            foreach ($prepared as $msisdn => $job) {
                $results[$msisdn] = new Result($msisdn, StatusCode::TRANSPORT_ERROR);
            }

            $summary['errors'][] = $e->getMessage();
        }

        foreach ($prepared as $msisdn => $job) {
            $result = isset($results[$msisdn])
                ? $results[$msisdn]
                : new Result($msisdn, StatusCode::MALFORMED_RESPONSE);

            $measured = Text::measure($job['text']);

            MessageLog::record([
                'batch_id'    => $batch,
                'client_id'   => $job['client_id'],
                'source'      => $source,
                'sender_id'   => $senderId,
                'recipient'   => $msisdn,
                'message'     => $job['text'],
                'encoding'    => $measured['encoding'],
                'parts'       => $measured['parts'],
                'sms_id'      => $result->smsId,
                'status'      => $result->status(),
                'status_code' => $result->statusCode,
                'status_text' => $result->meaning(),
            ]);

            if ($result->isSuccess()) {
                $summary['sent']++;
                continue;
            }

            $summary['failed']++;
            $summary['errors'][] = $msisdn . ': ' . $result->meaning();

            if (StatusCode::isAccountLevel($result->statusCode)) {
                logActivity(
                    'Automas SMS (' . $source . '): ' . $result->meaning()
                    . ' - check the module settings and account balance.'
                );
            }
        }

        return $summary;
    }

    /**
     * Convenience wrapper for the common "one client, one message" case.
     */
    public static function sendTo($msisdn, $text, $clientId = 0, $source = 'manual')
    {
        $summary = self::dispatch(
            [['msisdn' => $msisdn, 'text' => $text, 'client_id' => $clientId]],
            $source
        );

        return $summary['sent'] > 0;
    }

    /**
     * @return array<string, string[]> text => MSISDNs
     */
    private static function groupByText(array $prepared)
    {
        $groups = [];

        foreach ($prepared as $job) {
            $groups[$job['text']][] = $job['msisdn'];
        }

        return $groups;
    }

    private static function batchId()
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            return uniqid('', true);
        }
    }
}
