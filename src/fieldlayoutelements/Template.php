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
use craft\web\twig\CpExtension;
use craft\web\twig\Environment;
use craft\web\View;
use Throwable;

/**
 * Template represents a UI element based on a custom template that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Template extends BaseUiElement
{
    private static Environment $twig;

    /**
     * @return Environment
     */
    private static function twig(): Environment
    {
        if (!isset(self::$twig)) {
            $view = Craft::$app->getView();
            $templateMode = $view->getTemplateMode();
            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
            self::$twig = Craft::$app->getView()->createTwig();
            self::$twig->addExtension(new CpExtension());
            $view->setTemplateMode($templateMode);
        }

        return self::$twig;
    }

    /**
     * @var string The template path
     */
    public string $template = '';

    /**
     * @var string The template mode to use when loading the template.
     * @since 5.5.0
     */
    public string $templateMode = View::TEMPLATE_MODE_SITE;

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
    protected function selectorIcon(): ?string
    {
        return 'file-code';
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
    public function hasSettings()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Cp::autosuggestFieldHtml([
            'label' => Craft::t('app', 'Template'),
            'instructions' => Craft::t('app', 'The path to a template file within your `templates/` folder.'),
            'tip' => Craft::t('app', 'The template will be rendered with an `element` variable.'),
            'class' => 'code',
            'id' => 'template',
            'name' => 'template',
            'suggestTemplates' => true,
            'value' => $this->template,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$this->template) {
            return $this->_error(Craft::t('app', 'No template path has been chosen yet.'), 'warning');
        }

        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $twig = $view->getTwig();
        $view->setTwig(self::twig());

        try {
            $content = trim($view->renderTemplate($this->template, [
                'element' => $element,
                'static' => $static,
            ], $this->templateMode));
        } catch (Throwable $e) {
            return $this->_error($e->getMessage(), 'error');
        } finally {
            $view->setTwig($twig);
            $view->setTemplateMode($templateMode);
        }

        if ($content === '') {
            return null;
        }

        return Html::tag('div', $content, $this->containerAttributes($element, $static));
    }

    /**
     * @inheritdoc
     */
    public function alwaysRefresh(): bool
    {
        return true;
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
            ],
        ]);
        $content = Html::tag('p', $icon . ' ' . Html::encode($error), [
            'class' => $errorClass,
        ]);

        return Html::tag('div', $content);
    }
}
