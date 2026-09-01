<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\TemplateContent;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use InvalidArgumentException;
use Override;

/**
 * Renders an HTML fragment as sanitized, non-interactive field layout content.
 */
class Html extends FieldLayoutElement
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly string $html, array $config = [])
    {
        parent::__construct($config);
    }

    #[Override]
    public function selectorHtml(): string
    {
        throw new NotSupportedException(sprintf('%s should not be included in user-modifiable field layouts.', self::class));
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted HTML FieldLayout elements require stable UIDs.');
        }

        return TemplateContent::make($this->uid, $this->html);
    }
}
