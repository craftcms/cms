<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\maxPowerCaptain;

readonly class QueueController
{
    public function __construct(
        private JobProgress $jobProgress,
        private Request $request,
    ) {}

    public function run(): Response
    {
        if (! Cms::config()->runQueueAutomatically) {
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
}
