<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\maxPowerCaptain;

readonly class QueueController
{
    use RespondsWithFlash;

    public function __construct(
        private JobProgress $jobProgress,
        private Request $request,
        private Utilities $utilities,
    ) {}

    public function run(): Response
    {
        if (app()->isDownForMaintenance() || ! Cms::config()->runQueueAutomatically) {
            return response()->make();
        }

        if ($this->jobProgress->hasReservedJobs() || ! $this->jobProgress->hasPendingJobs()) {
            return response()->make();
        }

        app()->terminating(function () {
            maxPowerCaptain();

            Artisan::call('queue:work', [
                '--queue' => collect([Cms::config()->queueName, Cms::config()->lowPriorityQueueName])
                    ->unique()
                    ->join(','),
                '--memory' => Cms::config()->phpMaxMemoryLimit ?: '1536M',
                '--once',
            ]);
        });

        return response()->make();
    }

    public function jobInfo(): JsonResponse
    {
        $limit = $this->request->integer('limit') ?: null;

        return new JsonResponse([
            'total' => $this->jobProgress->getTotalJobs(),
            'jobs' => $this->jobProgress->getJobInfo($limit),
        ]);
    }

    public function cancel(string $id): Response
    {
        if (! $this->utilities->checkAuthorization(Utilities\QueueManager::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }

        $this->jobProgress->cancel($id);

        return $this->asSuccess();
    }

    public function cancelAll(): Response
    {
        if (! $this->utilities->checkAuthorization(Utilities\QueueManager::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }

        $this->jobProgress->clear();

        return $this->asSuccess();
    }

    public function retry(string $id): Response
    {
        if (! $this->utilities->checkAuthorization(Utilities\QueueManager::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }

        Artisan::call('queue:retry', [
            'id' => $id,
        ]);

        $this->jobProgress->delete($id);
        $this->run();

        return $this->asSuccess();
    }

    public function retryAll(): Response
    {
        if (! $this->utilities->checkAuthorization(Utilities\QueueManager::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }

        Artisan::call('queue:retry', [
            'id' => 'all',
        ]);

        $this->jobProgress->clearFailed();
        $this->run();

        return $this->asSuccess();
    }
}
