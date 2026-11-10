<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Illuminate\Container\Attributes\Scoped;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request-scoped cache directives. No freshness lifetime is supplied by core.
 *
 * @since 6.0.0
 */
#[Scoped]
class CacheControl
{
    /** @var array<string, int|bool> */
    private array $options = [];

    /** @var list<array{code: string, target: string, message: string, source?: string}> */
    private array $reasons = [];

    public function setMaxAge(int $value, ?string $reason = null): static
    {
        return $this->setOption('max_age', $value, $reason);
    }

    /**
     * Sets a template expiry duration. The latest duration wins; explicit storage
     * restrictions remain independent of it.
     */
    public function setDuration(int $value, ?string $reason = null): static
    {
        return $this->setOption('duration', $value, $reason);
    }

    public function setSharedMaxAge(int $value, ?string $reason = null): static
    {
        return $this->setOption('s_maxage', $value, $reason);
    }

    public function setStaleWhileRevalidate(int $value, ?string $reason = null): static
    {
        return $this->setOption('stale_while_revalidate', $value, $reason);
    }

    public function setStaleIfError(int $value, ?string $reason = null): static
    {
        return $this->setOption('stale_if_error', $value, $reason);
    }

    public function setPublic(?string $reason = null): static
    {
        unset($this->options['private']);

        return $this->setOption('public', true, $reason);
    }

    public function setPrivate(?string $reason = null): static
    {
        unset($this->options['public']);

        return $this->setOption('private', true, $reason);
    }

    public function setNoCache(?string $reason = null): static
    {
        return $this->setOption('no_cache', true, $reason);
    }

    public function setNoStore(?string $reason = null): static
    {
        return $this->setOption('no_store', true, $reason);
    }

    /** @return list<array{code: string, target: string, message: string, source?: string}> */
    public function reasons(): array
    {
        return $this->reasons;
    }

    public function applyTo(Response $response): void
    {
        $options = $this->options;
        $duration = $options['duration'] ?? null;
        unset($options['duration']);

        if ($duration !== null) {
            $options = [
                ...($duration > 0
                    ? ['public' => true, 'max_age' => $duration]
                    : ['private' => true, 'no_cache' => true, 'no_store' => true]),
                ...$options,
            ];
        }

        $response->setCache($options);
    }

    private function setOption(string $option, int|bool $value, ?string $reason): static
    {
        $this->options[$option] = $value;
        $entry = [
            'code' => 'policy',
            'target' => in_array($option, ['s_maxage', 'private'], true) ? 'shared' : 'both',
            'message' => $reason ?? sprintf('Requested %s: %s.', str_replace('_', '-', $option), $value === true ? 'true' : $value),
        ];

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            if (! isset($frame['file']) || $frame['file'] === __FILE__ || str_ends_with($frame['file'], '/Illuminate/Support/Facades/Facade.php')) {
                continue;
            }

            $entry['source'] = $frame['file'].':'.$frame['line'];
            break;
        }

        $this->reasons[] = $entry;

        return $this;
    }
}
