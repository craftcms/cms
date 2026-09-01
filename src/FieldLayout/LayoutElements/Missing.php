<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Component\Concerns\MissingComponentTrait;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\Missing as MissingNode;
use InvalidArgumentException;

class Missing extends FieldLayoutElement implements MissingComponentInterface
{
    use MissingComponentTrait;

    public function selectorHtml(): string
    {
        return $this->getPlaceholderHtml();
    }

    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted missing FieldLayout elements require stable UIDs.');
        }

        return MissingNode::make($this->uid, $this->expectedType);
    }
}
