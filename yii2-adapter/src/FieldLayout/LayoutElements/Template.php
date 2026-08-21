<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout\LayoutElements;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\Environment;
use CraftCms\Cms\Twig\Extensions\CpExtension;
use CraftCms\Cms\View\TemplateMode;
use Override;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Template extends BaseUiElement
{
    private static Environment $twig;

    public string $template = '';

    public string $templateMode = TemplateMode::Site->value;

    public static function make(string $template): static
    {
        return app(static::class)->template($template);
    }

    public function template(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function templateMode(TemplateMode|string $templateMode): static
    {
        $this->templateMode = $templateMode instanceof TemplateMode
            ? $templateMode->value
            : TemplateMode::from($templateMode)->value;

        return $this;
    }

    protected function selectorLabel(): string
    {
        return $this->template ?: t('Template');
    }

    protected function selectorIcon(): ?string
    {
        return 'file-code';
    }

    /** @return array{class?: list<string>} */
    #[Override]
    protected function selectorLabelAttributes(): array
    {
        $attributes = parent::selectorLabelAttributes();

        if ($this->template) {
            $attributes['class'][] = 'code';
        }

        return $attributes;
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

    protected function settingsNodes(FormContext $context): array
    {
        return [
            Field::make(t('Template'), Combobox::make('template')
                ->options(SelectOptions::getTemplateSuggestions())
                ->showAllOnEmpty()
                ->value($this->template))
                ->instructions(t('The path to a template file within your `templates/` folder.'))
                ->tip(t('The template will be rendered with an `element` variable.')),
        ];
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$this->template) {
            return $this->error(t('No template path has been chosen yet.'), 'warning');
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
        } catch (Throwable $exception) {
            return $this->error($exception->getMessage(), 'error');
        } finally {
            Twig::set($twig);
            TemplateMode::set($templateMode);
        }

        return $content === ''
            ? null
            : Html::tag('div', $content, $this->containerAttributes($element, $static));
    }

    public function alwaysRefresh(): bool
    {
        return true;
    }

    private static function twig(): Environment
    {
        if (!isset(self::$twig)) {
            TemplateMode::with(TemplateMode::Site, function() {
                self::$twig = Twig::create();
                self::$twig->addExtension(new CpExtension());
            });
        }

        return self::$twig;
    }

    private function error(string $error, string $errorClass): string
    {
        $icon = Html::tag('span', '', ['data' => ['icon' => 'alert']]);
        $content = Html::tag('p', $icon . ' ' . Html::encode($error), ['class' => $errorClass]);

        return Html::tag('div', $content);
    }
}
