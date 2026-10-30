<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Jobs;

use CraftCms\Cms\Ai\Tasks\GenerateChatTitle;
use CraftCms\Cms\Queue\Job;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;

use function CraftCms\Cms\t;

/**
 * Replaces an AI assistant conversation’s title, the start of its first message, with one generated from the message.
 * A title the user gave it in the meantime stays, as does the one it has if no title can be generated.
 *
 * @internal
 *
 * @since 6.0.0
 */
class NameConversation extends Job
{
    public function __construct(
        public string $threadId,
        public string $message,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $title = GenerateChatTitle::generate($this->message);

        if ($title !== null) {
            Conversation::query()
                ->whereKey($this->threadId)
                ->where('title', Str::limit($this->message, 100))
                ->update(['title' => $title]);
        }
    }

    #[\Override]
    protected function defaultDescription(): string
    {
        return t('Naming an AI chat');
    }
}
