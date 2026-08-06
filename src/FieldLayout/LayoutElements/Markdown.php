<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\MarkdownContent;
use CraftCms\Cms\Support\Str;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class Markdown extends BaseUiElement
{
    /**
     * @var string The Markdown content
     */
    public string $content = '';

    /**
     * @var bool Whether the content should be displayed in a pane.
     */
    public bool $displayInPane = true;

    public static function make(string $content): static
    {
        return app(static::class)->content($content);
    }

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function displayInPane(bool $displayInPane = true): static
    {
        $this->displayInPane = $displayInPane;

        return $this;
    }

    protected function selectorLabel(): string
    {
        return Str::firstLine($this->content) ?: 'Markdown';
    }

    protected function selectorIcon(): ?string
    {
        return 'markdown';
    }

    /** @return array{class?: list<string>} */
    #[Override]
    protected function selectorLabelAttributes(): array
    {
        $attr = parent::selectorLabelAttributes();

        if ($this->content) {
            $attr['class'][] = 'code';
        }

        return $attr;
    }

    #[Override]
    public function hasCustomWidth(): bool
    {
        return true;
    }

    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    protected function settingsHtml(): ?string
    {
        return
            FormFields::textareaFieldHtml([
                'label' => t('Content'),
                'class' => ['code', 'nicetext'],
                'id' => 'content',
                'name' => 'content',
                'value' => $this->content,
            ]).
            FormFields::lightswitchFieldHtml([
                'label' => t('Display content in a pane'),
                'id' => 'display-in-pane',
                'name' => 'displayInPane',
                'on' => $this->displayInPane,
            ]);
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Markdown FieldLayout elements require stable UIDs.');
        }

        return MarkdownContent::make($this->uid, $this->content)
            ->displayInPane($this->displayInPane)
            ->width($this->width);
    }
}
