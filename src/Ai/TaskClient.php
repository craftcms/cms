<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use Closure;
use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Enums\AiUnavailableReason;
use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Ai\Events\TaskCompleted;
use CraftCms\Cms\Ai\Events\TaskFailed;
use CraftCms\Cms\Ai\Events\TaskRunning;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\Jobs\InvokeTaskAgent;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\AgentInput;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\PendingResponses\PendingAudioGeneration;
use Laravel\Ai\PendingResponses\PendingClassification;
use Laravel\Ai\PendingResponses\PendingEmbeddingsGeneration;
use Laravel\Ai\PendingResponses\PendingImageGeneration;
use Laravel\Ai\PendingResponses\PendingReranking;
use Laravel\Ai\PendingResponses\PendingTranscriptionGeneration;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\AudioResponse;
use Laravel\Ai\Responses\ClassificationResponse;
use Laravel\Ai\Responses\EmbeddingsResponse;
use Laravel\Ai\Responses\ImageResponse;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\RerankingResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Responses\TranscriptionResponse;
use LogicException;
use Throwable;

/**
 * Runs `laravel/ai` operations with a task’s provider and model.
 *
 * Every method resolves the task first, and throws {@see AiUnavailableException}
 * before any request is sent if it can’t. Requests dispatch {@see TaskRunning}
 * before they’re sent, then {@see TaskCompleted} or {@see TaskFailed}.
 *
 * @since 6.0.0
 */
readonly class TaskClient
{
    /**
     * @param  class-string<TaskInterface>  $task
     */
    public function __construct(
        private Ai $ai,
        public string $task,
    ) {}

    /**
     * @throws AiUnavailableException
     */
    public function resolve(): ResolvedProvider
    {
        return $this->ai->resolve($this->task::handle());
    }

    /**
     * @param  list<mixed>  $attachments
     *
     * @throws AiUnavailableException
     */
    public function prompt(
        Agent $agent,
        AgentInput|UserMessage|Decisions|string $prompt,
        array $attachments = [],
        ?int $timeout = null,
    ): AgentResponse {
        return $this->run(Modality::Text, $agent, fn (ResolvedProvider $resolved): AgentResponse => $agent->prompt(
            $prompt, $attachments, $resolved->provider, $resolved->model, $timeout,
        ));
    }

    /**
     * @param  list<mixed>  $attachments
     *
     * @throws AiUnavailableException
     */
    public function stream(
        Agent $agent,
        AgentInput|UserMessage|Decisions|string $prompt,
        array $attachments = [],
        ?int $timeout = null,
    ): StreamableAgentResponse {
        [$resolved, $started] = $this->start(Modality::Text, $agent);

        return $agent->stream($prompt, $attachments, $resolved->provider, $resolved->model, $timeout)
            ->then(fn (StreamedAgentResponse $response) => $this->complete($resolved, $response, $started))
            ->catch(fn (Throwable $exception) => $this->fail($resolved, $exception, $started));
    }

    /**
     * Prompts the agent in a queued job.
     *
     * The job resolves the task when it runs, so it uses the settings at that time.
     * If the task is unavailable by then, the job fails with {@see AiUnavailableException}.
     *
     * @param  list<mixed>  $attachments  Attachments must be serializable, such as stored files.
     *
     * @throws AiUnavailableException if the task is unavailable when the job is dispatched
     */
    public function queue(Agent $agent, Decisions|string $prompt, array $attachments = []): QueuedAgentResponse
    {
        $this->resolveFor(Modality::Text);

        return new QueuedAgentResponse(
            dispatch(new InvokeTaskAgent($this->task::handle(), $agent, $prompt, $attachments)),
        );
    }

    /**
     * Generates an image, audio, a transcription, or embeddings from a pending `laravel/ai` request.
     *
     * ```php
     * $image = Ai::task('blog:heroImage')->generate(Image::of('A lighthouse at dusk')->landscape());
     * ```
     *
     * @throws AiUnavailableException
     */
    public function generate(
        PendingImageGeneration|PendingAudioGeneration|PendingTranscriptionGeneration|PendingEmbeddingsGeneration $request,
    ): ImageResponse|AudioResponse|TranscriptionResponse|EmbeddingsResponse {
        $modality = match (true) {
            $request instanceof PendingImageGeneration => Modality::Image,
            $request instanceof PendingAudioGeneration => Modality::Audio,
            $request instanceof PendingTranscriptionGeneration => Modality::Transcription,
            $request instanceof PendingEmbeddingsGeneration => Modality::Embeddings,
        };

        return $this->run($modality, $request, fn (ResolvedProvider $resolved): object => $request->generate(
            $resolved->provider, $resolved->model,
        ));
    }

    /**
     * @throws AiUnavailableException
     */
    public function rerank(PendingReranking $request, string $query): RerankingResponse
    {
        return $this->run(Modality::Reranking, $request, fn (ResolvedProvider $resolved): RerankingResponse => $request->rerank(
            $query, $resolved->provider, $resolved->model,
        ));
    }

    /**
     * @throws AiUnavailableException
     */
    public function classify(PendingClassification $request): ClassificationResponse
    {
        return $this->run(Modality::Classification, $request, fn (ResolvedProvider $resolved): ClassificationResponse => $request->classify(
            $resolved->provider, $resolved->model,
        ));
    }

    /**
     * Runs a request, dispatching {@see TaskRunning} before it and {@see TaskCompleted} or {@see TaskFailed} after it.
     *
     * @template TResponse of object
     *
     * @param  Closure(ResolvedProvider): TResponse  $request
     * @return TResponse
     */
    private function run(Modality $modality, object $subject, Closure $request): object
    {
        [$resolved, $started] = $this->start($modality, $subject);

        try {
            $response = $request($resolved);
        } catch (Throwable $exception) {
            $this->fail($resolved, $exception, $started);

            throw $exception;
        }

        $this->complete($resolved, $response, $started);

        return $response;
    }

    /**
     * @return array{ResolvedProvider, int|float}
     */
    private function start(Modality $modality, object $subject): array
    {
        $resolved = $this->resolveFor($modality);
        $event = new TaskRunning($this->task, $resolved, $subject);
        event($event);

        if ($event->cancel) {
            throw new AiUnavailableException($this->task::handle(), AiUnavailableReason::Canceled);
        }

        return [$resolved, hrtime(true)];
    }

    private function complete(ResolvedProvider $resolved, object $response, float|int $started): void
    {
        event(new TaskCompleted($this->task, $resolved, $response, $response->usage ?? null, $this->elapsed($started)));
    }

    private function fail(ResolvedProvider $resolved, Throwable $exception, float|int $started): void
    {
        event(new TaskFailed($this->task, $resolved, $exception, $this->elapsed($started)));
    }

    private function elapsed(float|int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function resolveFor(Modality $modality): ResolvedProvider
    {
        $handle = $this->task::handle();
        $taskModality = $this->task::modality();

        if ($taskModality !== $modality) {
            throw new LogicException("The AI task [{$handle}] has the {$taskModality->value} modality, so it can’t run {$modality->value} operations.");
        }

        return $this->resolve();
    }
}
