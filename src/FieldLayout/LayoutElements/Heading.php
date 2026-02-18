<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Support\Html;
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

    protected function settingsHtml(): ?string
    {
        return Cp::textFieldHtml([
            'label' => t('Heading'),
            'id' => 'heading',
            'name' => 'heading',
            'value' => $this->heading,
        ]);
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Html::tag('h2', Html::encode(t($this->heading, category: 'site')));
    }
}
