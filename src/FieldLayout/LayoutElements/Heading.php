<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Heading as HeadingNode;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * Heading represents an `<h2>` UI element that can be included in field layouts.
 */
class Heading extends BaseUiElement
{
    /**
     * @var string The heading text
     */
    public string $heading = '';

    public static function make(string $heading): static
    {
        return app(static::class)->heading($heading);
    }

    public function heading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    protected function selectorLabel(): string
    {
        return $this->heading ?: t('Heading');
    }

    protected function selectorIcon(): ?string
    {
        return 'hashtag';
    }

    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    #[Override]
    protected function settingsNodes(FormContext $context): array
    {
        return [
            Field::make(t('Heading'), Text::make('heading')->value($this->heading)),
        ];
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Heading FieldLayout elements require stable UIDs.');
        }

        return HeadingNode::make($this->uid, t($this->heading, category: 'site'));
    }
}
