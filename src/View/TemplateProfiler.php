<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Craft;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Auth;

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

        Craft::beginProfile($this->profileToken($type, $name, $count), 'Twig template');
    }

    public function endProfile(string $type, string $name): void
    {
        if (! $this->shouldProfile()) {
            return;
        }

        $count = $this->profileCounters[$type][$name]--;
        Craft::endProfile($this->profileToken($type, $name, $count), 'Twig template');
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

        return $this->shouldProfile = $user->admin && $user->getPreference('profileTemplates');
    }

    private function profileToken(string $type, string $name, int $count): string
    {
        return "render $type: $name".($count === 1 ? '' : " ($count)");
    }
}
