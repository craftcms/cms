<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use yii\helpers\Markdown as MarkdownHelper;

/**
 * Markdown represents a UI element based on Markdown content can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
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

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): string
    {
        return StringHelper::firstLine($this->content) ?: 'Markdown';
    }

    /**
     * @inheritdoc
     */
    protected function selectorIcon(): ?string
    {
        return 'markdown';
    }

    /**
     * @inheritdoc
     */
    protected function selectorLabelAttributes(): array
    {
        $attr = parent::selectorLabelAttributes();
        if ($this->content) {
            $attr['class'][] = 'code';
        }
        return $attr;
    }

    /**
     * @inheritdoc
     */
    public function hasCustomWidth(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasSettings()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return
            Cp::textareaFieldHtml([
                'label' => Craft::t('app', 'Content'),
                'class' => ['code', 'nicetext'],
                'id' => 'content',
                'name' => 'content',
                'value' => $this->content,
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Display content in a pane'),
                'id' => 'display-in-pane',
                'name' => 'displayInPane',
                'on' => $this->displayInPane,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $content = Html::tag('div', MarkdownHelper::process(Html::encode($this->content)), [
            'class' => array_filter([
                'markdown',
                $this->displayInPane ? 'pane' : null,
            ]),
        ]);

        return Html::tag('div', $content, $this->containerAttributes($element, $static));
    }
}
