<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Support\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        app(LaravelMigrations::class)->ensureNotificationsTable();

        if (! Schema::hasTable('announcements')) {
            return;
        }

        /** @var class-string<Model> $authModel */
        $authModel = config('auth.providers.users.model');
        $notifiableType = (new $authModel)->getMorphClass();

        DB::table('announcements')
            ->leftJoin('plugins', 'announcements.pluginId', '=', 'plugins.id')
            ->select([
                'announcements.id',
                'announcements.userId',
                'announcements.heading',
                'announcements.body',
                'announcements.dateRead',
                'announcements.dateCreated',
                'plugins.handle as pluginHandle',
            ])
            ->where(function (Builder $query): void {
                $query
                    ->where('announcements.unread', true)
                    ->orWhere('announcements.dateRead', '>', now()->subDays(7));
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('announcements.pluginId')
                    ->orWhereNotNull('plugins.handle');
            })
            ->orderBy('announcements.id')
            ->chunk(100, function (Collection $announcements) use ($notifiableType): void {
                DB::table('notifications')->insert($announcements->map(fn (object $announcement): array => [
                    'id' => (string) Str::uuid(),
                    'type' => CpNotification::TYPE,
                    'notifiable_type' => $notifiableType,
                    'notifiable_id' => $announcement->userId,
                    'data' => Json::encode(Arr::whereNotNull([
                        'kind' => 'announcement',
                        'title' => $announcement->heading,
                        'message' => $announcement->body,
                        'byline' => $announcement->pluginHandle ?? 'Craft CMS',
                        'icon' => $announcement->pluginHandle === null ? 'custom-icons/craft-cms' : null,
                        'buttons' => [],
                    ])),
                    'read_at' => $announcement->dateRead,
                    'created_at' => $announcement->dateCreated,
                    'updated_at' => $announcement->dateCreated,
                ])->all());
            });

        Schema::drop('announcements');
    }
};
