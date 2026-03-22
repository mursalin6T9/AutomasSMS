<?php

namespace AutomasSMS;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Runs a notification definition when its WHMCS hook fires.
 *
 * This is the logic that used to be copy-pasted into all 27 hook files: check the
 * template is enabled, check credentials, resolve recipients, render tokens,
 * send, log. The hook files now only describe what to send and to whom.
 */
class Dispatcher
{
    /**
     * Bind every definition to its WHMCS hook point.
     */
    public static function register()
    {
        foreach (Registry::definitions() as $definition) {
            add_hook($definition['hook'], 1, function ($args) use ($definition) {
                return Dispatcher::handle($definition, is_array($args) ? $args : []);
            });
        }

        // Housekeeping: trim the message log once a day.
        add_hook('DailyCronJob', 100, function () {
            if (Schema::isInstalled()) {
                MessageLog::prune();
            }
        });
    }

    /**
     * @return null
     */
    public static function handle(array $definition, array $args)
    {
        try {
            $template = Templates::find($definition['key'], $definition['type']);

            if (!$template || (int) $template->is_active !== 1) {
                return null;
            }

            if (!Settings::isConfigured()) {
                logActivity('Automas SMS (' . $definition['key'] . '): no API key configured, notification skipped.');

                return null;
            }

            $context = new Context();
            $targets = call_user_func($definition['resolve'], $args, $context, $template);

            if (!is_array($targets) || !$targets) {
                return null;
            }

            $jobs = self::buildJobs($definition, $template, $targets, $context);

            if (!$jobs) {
                return null;
            }

            $summary = Sender::dispatch($jobs, $definition['key']);

            if ($summary['sent'] > 0) {
                $clientId = 0;

                foreach ($jobs as $job) {
                    if (!empty($job['client_id'])) {
                        $clientId = $job['client_id'];
                        break;
                    }
                }

                logActivity(
                    'Automas SMS: ' . Registry::label($definition['key'])
                    . ' notification sent (' . $summary['sent'] . ' message(s)).',
                    $clientId
                );
            }

            if ($summary['failed'] > 0 || $summary['skipped'] > 0) {
                logActivity(
                    'Automas SMS: ' . Registry::label($definition['key'])
                    . ' - ' . $summary['failed'] . ' failed, ' . $summary['skipped'] . ' skipped. '
                    . implode(' | ', array_slice($summary['errors'], 0, 5))
                );
            }
        } catch (\Throwable $e) {
            // A notification must never break the WHMCS action that triggered it.
            logActivity('Automas SMS (' . $definition['key'] . ') error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Turn resolver targets into ready-to-send jobs.
     */
    private static function buildJobs(array $definition, $template, array $targets, Context $context)
    {
        $jobs = [];
        $adminNumbers = null;

        foreach ($targets as $target) {
            $tokens = isset($target['tokens']) && is_array($target['tokens']) ? $target['tokens'] : [];

            // Tokens every template can use, unless the resolver overrode them.
            $tokens += [
                '{company}' => $context->company(),
                '{x}'       => (string) $template->extra_info,
            ];

            $body = Text::render($template->content, $tokens);

            if (trim($body) === '') {
                continue;
            }

            if ($definition['type'] === 'admin') {
                if ($adminNumbers === null) {
                    $adminNumbers = self::adminNumbers($template, $definition['key']);
                }

                foreach ($adminNumbers as $number) {
                    $jobs[] = [
                        'msisdn'    => $number,
                        'text'      => $body,
                        'client_id' => 0,
                    ];
                }

                continue;
            }

            $msisdn = Phone::normalise(isset($target['msisdn']) ? $target['msisdn'] : null);

            if ($msisdn === null) {
                logActivity(
                    'Automas SMS (' . $definition['key'] . '): skipped client #'
                    . (isset($target['client_id']) ? $target['client_id'] : 0)
                    . ' - no valid mobile number on the account.'
                );

                continue;
            }

            $jobs[] = [
                'msisdn'    => $msisdn,
                'text'      => $body,
                'client_id' => isset($target['client_id']) ? (int) $target['client_id'] : 0,
            ];
        }

        return $jobs;
    }

    /**
     * Numbers for an admin notification: the per-template list, falling back to
     * the global list on the Settings tab.
     *
     * @return string[]
     */
    private static function adminNumbers($template, $key)
    {
        $numbers = Phone::normaliseList($template->admin_numbers);

        if (!$numbers['valid']) {
            $numbers = Phone::normaliseList(Settings::get('admin_numbers', ''));
        }

        if ($numbers['invalid']) {
            logActivity(
                'Automas SMS (' . $key . '): ignoring invalid admin number(s) '
                . implode(', ', $numbers['invalid'])
            );
        }

        if (!$numbers['valid']) {
            logActivity('Automas SMS (' . $key . '): enabled but no admin numbers are set.');
        }

        return $numbers['valid'];
    }
}
