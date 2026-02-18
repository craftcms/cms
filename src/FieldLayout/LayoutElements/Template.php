<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\web\twig\CpExtension;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\Environment;
use CraftCms\Cms\View\TemplateMode;
use Override;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Template extends BaseUiElement
{
    private static Environment $twig;

    private static function twig(): Environment
    {
        if (isset(self::$twig)) {
            return self::$twig;
        }

        TemplateMode::with(TemplateMode::Site, function () {
            self::$twig = Twig::create();
            self::$twig->addExtension(new CpExtension);
        });

        return self::$twig;
    }

    /**
     * @var string The template path
     */
    public string $template = '';

    /**
     * @var string The template mode to use when loading the template.
     */
    public string $templateMode = TemplateMode::Site->value;

    protected function selectorLabel(): string
    {
        return $this->template ?: t('Template');
    }

    protected function selectorIcon(): ?string
    {
        return 'file-code';
    }

    #[Override]
    protected function selectorLabelAttributes(): array
    {
        $attr = parent::selectorLabelAttributes();
        if ($this->template) {
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

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $this->template) {
            return $this->_error(t('No template path has been chosen yet.'), 'warning');
        }

        $templateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::Site);
        $twig = Twig::get();
        Twig::set(self::twig());

        try {
            $content = trim(template($this->template, [
                'element' => $element,
                'static' => $static,
            ], templateMode: TemplateMode::from($this->templateMode)));
        } catch (Throwable $e) {
            return $this->_error($e->getMessage(), 'error');
        } finally {
            Twig::set($twig);
            TemplateMode::set($templateMode);
        }

        if ($content === '') {
            return null;
        }

        return Html::tag('div', $content, $this->containerAttributes($element, $static));
    }

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
