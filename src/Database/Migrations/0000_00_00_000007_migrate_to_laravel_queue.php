<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrates from Yii2 Queue to Laravel Queue.
 *
 * - Drops the legacy `queue` table (used by Yii2 Queue)
 * - Creates the `jobprogress` table (used by Laravel Queue for progress tracking)
 *
 * The Laravel Queue system uses its own tables (jobs, failed_jobs) which are
 * managed by the host application, not by Craft.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jobprogress')) {
            Schema::create('jobprogress', function (Blueprint $table) {
                $table->string('uid')->primary();
                $table->string('description')->nullable();
                $table->unsignedTinyInteger('status')->default(1); // JobStatus::Pending
                $table->unsignedTinyInteger('progress')->default(0);
                $table->unsignedInteger('delay')->nullable();
                $table->string('progressLabel')->nullable();
                $table->text('error')->nullable();
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
            });
        }

        Schema::dropIfExists('queue');
    }

    public function down(): void
    {
        if (! Schema::hasTable('queue')) {
            Schema::create('queue', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('channel')->default('queue');
                $table->binary('job');
                $table->text('description')->nullable();
                $table->integer('timePushed');
                $table->integer('ttr');
                $table->integer('delay')->default(0);
                $table->unsignedInteger('priority')->default(1024);
                $table->dateTime('dateReserved')->nullable();
                $table->integer('timeUpdated')->nullable();
                $table->smallInteger('progress')->default(0);
                $table->string('progressLabel')->nullable();
                $table->integer('attempt')->nullable();
                $table->boolean('fail')->default(false)->nullable();
                $table->dateTime('dateFailed')->nullable();
                $table->text('error')->nullable();
            });

            Schema::createIndex('queue', ['channel', 'fail', 'timeUpdated', 'timePushed']);
            Schema::createIndex('queue', ['channel', 'fail', 'timeUpdated', 'delay']);
        }

        Schema::dropIfExists('jobprogress');
    }
};
