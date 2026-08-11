<?php

declare(strict_types=1);

use CraftCms\Cms\Console\ConsoleServiceProvider;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Override;

afterEach(function () {
    ClearCaches::flushState();
});

it('registers cache commands after all service providers have booted', function () {
    $container = Container::getInstance();

    try {
        $application = new class extends Application
        {
            public ?Closure $capturedBootedCallback = null;

            public function booted($callback)
            {
                $this->capturedBootedCallback = $callback;
            }
        };
    } finally {
        Container::setInstance($container);
    }

    $provider = new class($application) extends ConsoleServiceProvider
    {
        public bool $registeredCacheCommand = false;

        public bool $registeredTagCommand = false;

        #[Override]
        public function commands($commands): void
        {
            foreach (is_array($commands) ? $commands : [$commands] as $command) {
                if (! $command instanceof Command) {
                    continue;
                }

                $this->registeredCacheCommand = $this->registeredCacheCommand || $command->getName() === 'craft:clear-caches:plugin';
                $this->registeredTagCommand = $this->registeredTagCommand || $command->getName() === 'craft:invalidate-tags:plugin';
            }
        }
    };

    $provider->boot();

    ClearCaches::add('plugin', [
        'label' => 'Plugin caches',
        'action' => fn () => null,
    ]);
    ClearCaches::addTag('plugin', 'Plugin caches');

    $application->capturedBootedCallback?->__invoke();

    expect($provider->registeredCacheCommand)->toBeTrue()
        ->and($provider->registeredTagCommand)->toBeTrue();
});
