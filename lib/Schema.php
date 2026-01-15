<?php

namespace AutomasSMS;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Table creation and teardown.
 *
 * The module only ever creates and drops its own mod_automassms_* tables. Tables
 * belonging to any other module are left completely alone.
 */
class Schema
{
    public static function install()
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable(AUTOMASSMS_TABLE_SETTINGS)) {
            $schema->create(AUTOMASSMS_TABLE_SETTINGS, function ($table) {
                $table->string('setting', 64)->primary();
                $table->text('value')->nullable();
            });
        }

        if (!$schema->hasTable(AUTOMASSMS_TABLE_TEMPLATES)) {
            $schema->create(AUTOMASSMS_TABLE_TEMPLATES, function ($table) {
                $table->increments('id');
                $table->string('name', 64);
                $table->enum('user_type', ['client', 'admin']);
                $table->string('admin_numbers', 255)->default('');
                $table->text('content')->nullable();
                $table->string('variables', 500)->default('');
                $table->boolean('is_active')->default(0);
                $table->string('extra_info', 8)->default('');
                $table->string('description', 255)->nullable();
                $table->unique(['name', 'user_type'], 'automassms_template_key');
            });
        }

        if (!$schema->hasTable(AUTOMASSMS_TABLE_MESSAGES)) {
            $schema->create(AUTOMASSMS_TABLE_MESSAGES, function ($table) {
                $table->increments('id');
                $table->string('batch_id', 36)->nullable();
                $table->integer('client_id')->default(0);
                $table->string('source', 64)->default('manual');
                $table->string('sender_id', 64)->default('');
                $table->string('recipient', 20)->default('');
                $table->text('message')->nullable();
                $table->string('encoding', 8)->default('text');
                $table->smallInteger('parts')->default(1);
                $table->string('sms_id', 32)->nullable();
                $table->string('status', 16)->default('failed');
                $table->integer('status_code')->default(0);
                $table->string('status_text', 191)->default('');
                $table->dateTime('created_at');

                $table->index('created_at', 'automassms_created_at');
                $table->index('client_id', 'automassms_client_id');
                $table->index('batch_id', 'automassms_batch_id');
                $table->index('status', 'automassms_status');
            });
        }

        Settings::flush();

        foreach (['api_key', 'sender_id', 'unicode_type', 'admin_numbers', 'log_retention'] as $key) {
            if (Capsule::table(AUTOMASSMS_TABLE_SETTINGS)->where('setting', $key)->count() === 0) {
                Settings::set($key, Settings::get($key, ''));
            }
        }

        Settings::set('version', AUTOMASSMS_VERSION);
    }

    public static function uninstall()
    {
        $schema = Capsule::schema();

        foreach ([AUTOMASSMS_TABLE_MESSAGES, AUTOMASSMS_TABLE_TEMPLATES, AUTOMASSMS_TABLE_SETTINGS] as $table) {
            if ($schema->hasTable($table)) {
                $schema->drop($table);
            }
        }

        Settings::flush();
        Templates::flush();
    }

    public static function isInstalled()
    {
        try {
            return Capsule::schema()->hasTable(AUTOMASSMS_TABLE_SETTINGS)
                && Capsule::schema()->hasTable(AUTOMASSMS_TABLE_TEMPLATES)
                && Capsule::schema()->hasTable(AUTOMASSMS_TABLE_MESSAGES);
        } catch (\Exception $e) {
            return false;
        }
    }
}
