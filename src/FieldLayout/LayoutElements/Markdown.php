<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\base\ElementInterface;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Facades\Markdown as MarkdownFacade;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
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

    protected function selectorLabel(): string
    {
        return Str::firstLine($this->content) ?: 'Markdown';
    }

    protected function selectorIcon(): ?string
    {
        return 'markdown';
    }

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

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $content = Html::tag('div', MarkdownFacade::parse(Html::encode($this->content)), [
            'class' => array_filter([
                'markdown',
                $this->displayInPane ? 'pane' : null,
            ]),
        ]);

        return Html::tag('div', $content, $this->containerAttributes($element, $static));
    }
}
