<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Element\ElementIndexSourceSettings;
use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Http\Contracts\EmbeddedIndexScope;

/**
 * Scopes an embedded element index to the criteria of an {@see EmbeddedElementIndex}.
 *
 * @since 6.0.0
 */
class EmbeddedElementIndexScope implements EmbeddedIndexScope
{
    private ?ElementIndexSourceSettings $indexSource = null;

    public function __construct(private readonly EmbeddedElementIndex $index) {}

    public static function fromToken(string $token): self
    {
        $index = EmbeddedElementIndex::fromToken($token);

        abort_if($index === null, 400, 'Invalid embedded index token');

        return new self($index);
    }

    public function indexSource(string $elementType, ?array $config = null): ElementIndexSourceSettings
    {
        abort_unless($elementType === $this->index->elementType(), 400, 'Invalid element type');

        return $this->indexSource ??= $this->index->sourceSettings();
    }

    public function canReorder(): bool
    {
        return false;
    }
}
