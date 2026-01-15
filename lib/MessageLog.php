<?php

namespace AutomasSMS;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Message history.
 *
 * Automas returns a final per-recipient status in the send response and offers no
 * delivery-report endpoint, so rows are written once and never re-polled. Nothing
 * here stores a "pending" state or chases a report API on later page loads.
 */
class MessageLog
{
    public static function record(array $values)
    {
        $row = [
            'batch_id'    => isset($values['batch_id']) ? $values['batch_id'] : null,
            'client_id'   => isset($values['client_id']) ? (int) $values['client_id'] : 0,
            'source'      => isset($values['source']) ? $values['source'] : 'manual',
            'sender_id'   => isset($values['sender_id']) ? $values['sender_id'] : '',
            'recipient'   => isset($values['recipient']) ? $values['recipient'] : '',
            'message'     => isset($values['message']) ? $values['message'] : '',
            'encoding'    => isset($values['encoding']) ? $values['encoding'] : 'text',
            'parts'       => isset($values['parts']) ? (int) $values['parts'] : 1,
            'sms_id'      => isset($values['sms_id']) ? $values['sms_id'] : null,
            'status'      => isset($values['status']) ? $values['status'] : 'failed',
            'status_code' => isset($values['status_code']) ? (int) $values['status_code'] : StatusCode::MALFORMED_RESPONSE,
            'status_text' => isset($values['status_text']) ? $values['status_text'] : '',
            'created_at'  => self::now(),
        ];

        try {
            Capsule::table(AUTOMASSMS_TABLE_MESSAGES)->insert($row);
        } catch (\Exception $e) {
            logActivity('Automas SMS: could not write message log - ' . $e->getMessage());
        }
    }

    /**
     * @return array{rows: object[], total: int, pages: int, page: int}
     */
    public static function paginate(array $filters, $page = 1, $perPage = 25)
    {
        $page = max(1, (int) $page);
        $perPage = max(5, min(200, (int) $perPage));

        $query = Capsule::table(AUTOMASSMS_TABLE_MESSAGES . ' as m')
            ->leftJoin('tblclients as c', 'c.id', '=', 'm.client_id')
            ->select(
                'm.*',
                'c.firstname',
                'c.lastname',
                'c.id as uid'
            );

        if (!empty($filters['status'])) {
            $query->where('m.status', $filters['status']);
        }

        if (!empty($filters['source'])) {
            $query->where('m.source', $filters['source']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('m.recipient', 'like', $search)
                    ->orWhere('m.message', 'like', $search)
                    ->orWhere('c.firstname', 'like', $search)
                    ->orWhere('c.lastname', 'like', $search);
            });
        }

        $total = (clone $query)->count();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $rows = $query->orderBy('m.id', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->all();

        return [
            'rows'  => $rows,
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
        ];
    }

    public static function delete($id)
    {
        return Capsule::table(AUTOMASSMS_TABLE_MESSAGES)->where('id', (int) $id)->delete();
    }

    public static function truncate()
    {
        return Capsule::table(AUTOMASSMS_TABLE_MESSAGES)->delete();
    }

    /**
     * Drop rows older than the configured retention window. Called from the
     * daily cron so the table cannot grow without bound.
     */
    public static function prune()
    {
        $days = (int) Settings::get('log_retention', 90);

        if ($days <= 0) {
            return 0;
        }

        $cutoff = new \DateTime('now', new \DateTimeZone(AUTOMASSMS_TIMEZONE));
        $cutoff->modify('-' . $days . ' days');

        return Capsule::table(AUTOMASSMS_TABLE_MESSAGES)
            ->where('created_at', '<', $cutoff->format('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * Counts for the dashboard strip on the logs tab.
     *
     * @return array{total: int, sent: int, failed: int, parts: int}
     */
    public static function summary()
    {
        try {
            $since = new \DateTime('now', new \DateTimeZone(AUTOMASSMS_TIMEZONE));
            $since->modify('-30 days');
            $since = $since->format('Y-m-d H:i:s');

            $base = Capsule::table(AUTOMASSMS_TABLE_MESSAGES)->where('created_at', '>=', $since);

            return [
                'total'  => (clone $base)->count(),
                'sent'   => (clone $base)->where('status', 'sent')->count(),
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'parts'  => (int) (clone $base)->where('status', 'sent')->sum('parts'),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'parts' => 0];
        }
    }

    /**
     * Distinct sources, for the log filter dropdown.
     */
    public static function sources()
    {
        try {
            return Capsule::table(AUTOMASSMS_TABLE_MESSAGES)
                ->distinct()
                ->orderBy('source')
                ->pluck('source')
                ->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function now()
    {
        $now = new \DateTime('now', new \DateTimeZone(AUTOMASSMS_TIMEZONE));

        return $now->format('Y-m-d H:i:s');
    }
}
