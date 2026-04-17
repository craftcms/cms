<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use function CraftCms\Cms\debugbar;

#[Scoped]
class TemplateProfiler
{
    public const string PROFILE_TYPE_TEMPLATE = 'template';

    public const string PROFILE_TYPE_BLOCK = 'block';

    public const string PROFILE_TYPE_MACRO = 'macro';

    public const string PROFILE_STAGE_BEGIN = 'begin';

    public const string PROFILE_STAGE_END = 'end';

    private ?bool $shouldProfile = null;

    /** @var array<string, array<string, int>> */
    private array $profileCounters = [];

    /** @var array<string, int> */
    private array $timers = [];

    public function beginProfile(string $type, string $name): void
    {
        if (! $this->shouldProfile()) {
            return;
        }

        if (! isset($this->profileCounters[$type][$name])) {
            $count = $this->profileCounters[$type][$name] = 1;
        } else {
            $count = ++$this->profileCounters[$type][$name];
        }

        $this->timers[$this->profileToken($type, $name, $count)] = hrtime(true);

        debugbar()->startMeasure($this->profileToken($type, $name, $count), "{$type} {$name}");

        Log::debug('profile begin', [
            'name' => $name,
            'type' => $type,
            'count' => $count,
        ]);
    }

    public function endProfile(string $type, string $name): void
    {
        if (! $this->shouldProfile()) {
            return;
        }

        $count = $this->profileCounters[$type][$name]--;

        debugbar()->stopMeasure($this->profileToken($type, $name, $count));

        $start = $this->timers[$this->profileToken($type, $name, $count)] ?? null;

        if ($start === null) {
            Log::debug('profile end without start', [
                'name' => $name,
                'type' => $type,
                'count' => $count,
            ]);

            return;
        }

        $durationMs = round((hrtime(true) - $start) / 1_000_000, 2);

        Log::debug('profile end', [
            'name' => $name,
            'type' => $type,
            'count' => $count,
            'duration_ms' => $durationMs,
        ]);
    }

    private function shouldProfile(): bool
    {
        if ($this->shouldProfile !== null) {
            return $this->shouldProfile;
        }

        if (app()->hasDebugModeEnabled()) {
            return $this->shouldProfile = true;
        }

        $user = Auth::user();

        if (! $user) {
            return $this->shouldProfile = false;
        }

        return $this->shouldProfile = ($user->admin && $user->getPreference('profileTemplates'));
    }

    private function profileToken(string $type, string $name, int $count): string
    {
        return "render $type: $name".($count === 1 ? '' : " ($count)");
    }
}
