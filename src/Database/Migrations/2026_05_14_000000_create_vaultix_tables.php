<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Storage Destinations
        Schema::create('vaultix_destinations', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('provider'); // gdrive, s3, r2, sftp
            $blueprint->json('credentials');
            $blueprint->boolean('is_active')->default(true);
            $blueprint->boolean('notify_on_success')->default(true);
            $blueprint->boolean('notify_on_failure')->default(true);
            $blueprint->timestamps();
        });

        // 2. Backup Jobs
        Schema::create('vaultix_jobs', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('destination_id')->constrained('vaultix_destinations')->onDelete('cascade');
            $blueprint->string('name');
            $blueprint->string('type')->default('full'); // full, db_only, files_only
            $blueprint->string('custom_folder_name')->nullable();
            $blueprint->string('notification_email')->nullable();
            $blueprint->boolean('notify_on_success')->default(true);
            $blueprint->boolean('notify_on_failure')->default(true);
            $blueprint->string('frequency'); // hourly, daily, weekly, monthly
            $blueprint->string('backup_time')->default('00:00');
            $blueprint->string('backup_day')->nullable();
            $blueprint->integer('keep_all_backups_for_days')->default(7);
            $blueprint->integer('keep_daily_backups_for_days')->default(16);
            $blueprint->integer('keep_weekly_backups_for_weeks')->default(8);
            $blueprint->integer('keep_monthly_backups_for_months')->default(4);
            $blueprint->boolean('is_enabled')->default(true);
            $blueprint->timestamp('last_run_at')->nullable();
            $blueprint->timestamp('next_run_at')->nullable();
            $blueprint->timestamps();
        });

        // 3. Settings table (Key-Value)
        Schema::create('vaultix_settings', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('key')->unique();
            $blueprint->text('value')->nullable();
            $blueprint->timestamps();
        });

        // 4. Backup Records
        Schema::create('vaultix_backups', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('job_id')->constrained('vaultix_jobs')->onDelete('cascade');
            $blueprint->foreignId('destination_id')->constrained('vaultix_destinations')->onDelete('cascade');
            $blueprint->string('file_path');
            $blueprint->string('file_name');
            $blueprint->unsignedBigInteger('file_size')->default(0);
            $blueprint->string('status')->default('success'); // success, failed
            $blueprint->timestamp('completed_at')->nullable();
            $blueprint->timestamps();
        });

        // 5. Activity Logs
        Schema::create('vaultix_activities', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('user_id')->nullable();
            $blueprint->string('user_name')->nullable();
            $blueprint->string('user_email')->nullable();
            $blueprint->string('action'); // create, update, delete, download, etc.
            $blueprint->string('entity_type')->nullable(); // Job, Destination, Setting
            $blueprint->string('entity_name')->nullable();
            $blueprint->string('ip_address')->nullable();
            $blueprint->string('user_agent')->nullable();
            $blueprint->text('description')->nullable();
            $blueprint->json('old_data')->nullable();
            $blueprint->json('new_data')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vaultix_activities');
        Schema::dropIfExists('vaultix_backups');
        Schema::dropIfExists('vaultix_settings');
        Schema::dropIfExists('vaultix_jobs');
        Schema::dropIfExists('vaultix_destinations');
    }
};
