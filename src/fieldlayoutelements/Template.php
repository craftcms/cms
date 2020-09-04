<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\web\View;

/**
 * Template represents a UI element based on a custom template that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Template extends BaseUiElement
{
    /**
     * @var string The template path
     */
    public $template;

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): string
    {
        return $this->template ?: Craft::t('app', 'Template');
    }

    /**
     * @inheritdoc
     */
    protected function selectorIcon()
    {
        return '@appicons/template.svg';
    }

    /**
     * @inheritdoc
     */
    protected function selectorLabelAttributes(): array
    {
        $attr = parent::selectorLabelAttributes();
        if ($this->template) {
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
    public function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Template'),
                'instructions' => Craft::t('app', 'The path to a template file within your `templates/` folder.'),
                'tip' => Craft::t('app', 'The template will be rendered with an `element` variable.'),
                'class' => 'code',
                'id' => 'template',
                'name' => 'template',
                'value' => $this->template,
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(ElementInterface $element = null, bool $static = false)
    {
        if (!$this->template) {
            return $this->_error(Craft::t('app', 'No template path has been chosen yet.'), 'warning');
        }

        try {
            $content = trim(Craft::$app->getView()->renderTemplate($this->template, [
                'element' => $element,
                'static' => $static,
            ], View::TEMPLATE_MODE_SITE));
        } catch (\Throwable $e) {
            return $this->_error($e->getMessage(), 'error');
        }

        if ($content === '') {
            return null;
        }

        return Html::tag('div', $content, $this->containerAttributes($element, $static));
    }

    /**
     * Renders an error message.
     *
     * @param string $error
     * @param string $errorClass
     * @return string
     */
    private function _error(string $error, string $errorClass): string
    {
        $icon = Html::tag('span', '', [
            'data' => [
                'icon' => 'alert',
            ]
        ]);
        $content = Html::tag('p', $icon . ' ' . Html::encode($error), [
            'class' => $errorClass,
        ]);

        return Html::tag('div', $content, [
            'class' => 'pane',
        ]);
    }
}
