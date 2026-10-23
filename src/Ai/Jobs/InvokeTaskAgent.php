<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Jobs;

use CraftCms\Cms\Ai\Ai;
use CraftCms\Cms\Queue\Job;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Jobs\Concerns\InvokesQueuedResponseCallbacks;
use Laravel\Ai\Responses\AgentResponse;

use function CraftCms\Cms\t;

/**
 * Prompts an agent for a task, resolving the task when the job runs.
 *
 * @since 6.0.0
 */
class InvokeTaskAgent extends Job
{
    use InvokesQueuedResponseCallbacks;

    /**
     * @param  list<mixed>  $attachments
     */
    public function __construct(
        public string $task,
        public Agent $agent,
        public Decisions|string $prompt = '',
        public array $attachments = [],
    ) {
        parent::__construct();
    }

    public function handle(Ai $ai): void
    {
        $this->withCallbacks(fn (): AgentResponse => $ai->task($this->task)->prompt(
            $this->agent, $this->prompt, $this->attachments,
        ));
    }

    #[\Override]
    protected function defaultDescription(): string
    {
        return t('Running the {task} AI task', ['task' => $this->task]);
    }
}
