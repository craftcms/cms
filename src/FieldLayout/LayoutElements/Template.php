<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\web\twig\CpExtension;
use craft\web\twig\Environment;
use craft\web\View;
use CraftCms\Cms\Support\Html;
use Override;
use Throwable;

use function CraftCms\Cms\t;

class Template extends BaseUiElement
{
    private static Environment $twig;

    private static function twig(): Environment
    {
        if (isset(self::$twig)) {
            return self::$twig;
        }

        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        self::$twig = Craft::$app->getView()->createTwig();
        self::$twig->addExtension(new CpExtension);
        $view->setTemplateMode($templateMode);

        return self::$twig;
    }

    /**
     * @var string The template path
     */
    public string $template = '';

    /**
     * @var string The template mode to use when loading the template.
     */
    public string $templateMode = View::TEMPLATE_MODE_SITE;

    /**
     * {@inheritdoc}
     */
    protected function selectorLabel(): string
    {
        return $this->template ?: t('Template');
    }

    /**
     * {@inheritdoc}
     */
    protected function selectorIcon(): ?string
    {
        return 'file-code';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function selectorLabelAttributes(): array
    {
        $attr = parent::selectorLabelAttributes();
        if ($this->template) {
            $attr['class'][] = 'code';
        }

        return $attr;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function hasCustomWidth(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function settingsHtml(): ?string
    {
        return Cp::autosuggestFieldHtml([
            'label' => t('Template'),
            'instructions' => t('The path to a template file within your `templates/` folder.'),
            'tip' => t('The template will be rendered with an `element` variable.'),
            'class' => 'code',
            'id' => 'template',
            'name' => 'template',
            'suggestTemplates' => true,
            'value' => $this->template,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $this->template) {
            return $this->_error(t('No template path has been chosen yet.'), 'warning');
        }

        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $twig = $view->getTwig();
        $view->setTwig(self::twig());

        try {
            $content = trim((string) $view->renderTemplate($this->template, [
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
     * {@inheritdoc}
     */
    #[Override]
    public function alwaysRefresh(): bool
    {
        return true;
    }

    private function _error(string $error, string $errorClass): string
    {
        $icon = Html::tag('span', '', [
            'data' => [
                'icon' => 'alert',
            ],
        ]);
        $content = Html::tag('p', $icon.' '.Html::encode($error), [
            'class' => $errorClass,
        ]);

        return Html::tag('div', $content);
    }
}
