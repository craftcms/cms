<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Routing;

use CraftCms\Cms\Cms;
use Illuminate\Http\Request;

readonly class ActionRoute
{
    /** @param list<string> $segments */
    public function __construct(
        public array $segments,
        public string $uri,
        public bool $isCp,
    ) {}

    /** @param array<array-key, string> $segments */
    public static function fromSegments(array $segments, bool $isCp): ?self
    {
        $segments = array_values($segments);

        if ($segments === []) {
            return null;
        }

        return new self(
            segments: $segments,
            uri: self::uriForSegments($segments, $isCp),
            isCp: $isCp,
        );
    }

    /** @param list<string> $segments */
    public static function uriForSegments(array $segments, bool $isCp): string
    {
        return '/'.implode('/', array_filter([
            $isCp ? trim((string) Cms::config()->cpTrigger, '/') : null,
            trim(Cms::config()->actionTrigger, '/'),
            ...$segments,
        ], fn (?string $value) => $value !== null && $value !== ''));
    }

    public function matches(Request $request): bool
    {
        return '/'.ltrim($request->path(), '/') === $this->uri;
    }
}
