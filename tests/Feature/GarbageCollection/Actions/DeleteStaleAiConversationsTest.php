<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleAiConversations;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('deletes the assistant’s conversations not continued for 30 days, and those of deleted users, but not other agents’', function () {
    $userId = User::findOne()->id;
    $conversation = function (int $participantId, DateTimeInterface $updatedAt, string $agent = AssistantAgent::class): string {
        $id = (string) Str::uuid();

        DB::table(Table::AGENT_CONVERSATIONS)->insert([
            'id' => $id,
            'participant_type' => User::class,
            'participant_id' => $participantId,
            'title' => 'Hi',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);

        DB::table(Table::AGENT_CONVERSATION_MESSAGES)->insert([
            'id' => (string) Str::uuid(),
            'conversation_id' => $id,
            'participant_type' => User::class,
            'participant_id' => $participantId,
            'agent' => $agent,
            'role' => 'user',
            'content' => 'Hi',
            'attachments' => '[]',
            'steps' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'status' => 'completed',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);

        return $id;
    };

    $conversation($userId, now()->subDays(30)->subMinute());
    $recent = $conversation($userId, now()->subDays(29));
    $conversation($userId + 1000, now());
    $othersStale = $conversation($userId, now()->subDays(31), 'Plugin\\SupportAgent');

    app(DeleteStaleAiConversations::class)();

    expect(DB::table(Table::AGENT_CONVERSATIONS)->pluck('id')->all())->toEqualCanonicalizing([$recent, $othersStale])
        ->and(DB::table(Table::AGENT_CONVERSATION_MESSAGES)->pluck('conversation_id')->all())->toEqualCanonicalizing([$recent, $othersStale]);
});
