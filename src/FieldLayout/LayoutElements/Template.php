<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Callout;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\TemplateContent;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Twig\Environment;
use CraftCms\Cms\Twig\Extensions\CpExtension;
use CraftCms\Cms\View\TemplateMode;
use InvalidArgumentException;
use Override;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Renders a Twig template as sanitized, non-interactive field layout content.
 *
 * Form controls, scripts, registered assets, and other interactive behavior are not supported.
 */
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

    #[Override]
    protected function settingsNodes(FormContext $context): array
    {
        return [
            Field::make(t('Template'), Combobox::make('template')
                ->options(SelectOptions::getTemplateSuggestions())
                ->showAllOnEmpty()
                ->value($this->template))
                ->instructions(t('The path to a template file within your `templates/` folder.'))
                ->tip(t('The template receives `element` and `static` variables. Its output is sanitized and displayed as non-interactive content; form controls, scripts, and registered assets are not supported.')),
        ];
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Template FieldLayout elements require stable UIDs.');
        }

        if (! $this->template) {
            return Callout::make($this->uid, t('No template path has been chosen yet.'))
                ->variant('warning')
                ->width($this->width);
        }

        $templateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::Site);
        $twig = Twig::get();
        Twig::set(self::twig());

        try {
            $fragment = HtmlStack::capture(fn (): string => trim(template($this->template, [
                'element' => $context->element,
                'static' => true,
            ], templateMode: TemplateMode::from($this->templateMode))));
        } catch (Throwable $exception) {
            return Callout::make($this->uid, $exception->getMessage())
                ->variant('danger')
                ->width($this->width);
        } finally {
            Twig::set($twig);
            TemplateMode::set($templateMode);
        }

        if ($fragment->html === '') {
            return null;
        }

        return TemplateContent::make($this->uid, $fragment->html)
            ->width($this->width);
    }

    private static function twig(): Environment
    {
        if (! isset(self::$twig)) {
            TemplateMode::with(TemplateMode::Site, function () {
                self::$twig = Twig::create();
                self::$twig->addExtension(new CpExtension);
            });
        }

        return self::$twig;
    }
}
