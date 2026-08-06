<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\InputNamespace;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Form\Contracts\LegacySettingsComponent;
use CraftCms\Yii2Adapter\Form\Controls\LegacyHtmlControl;
use CraftCms\Yii2Adapter\Form\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Form\Nodes\LegacyHtmlField;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class LegacyHtml
{
    public function __construct(
        private readonly HtmlStack $htmlStack,
        private readonly InputNamespace $inputNamespace,
    ) {
    }

    /** @param string|list<string> $path */
    public function settings(
        LegacySettingsComponent $component,
        string|array $path,
        ?string $namespace = null,
        LegacyHtmlMode $mode = LegacyHtmlMode::Editable,
    ): ?LegacyHtmlField {
        return $this->capture(
            $path,
            match ($mode) {
                LegacyHtmlMode::Editable => $component->getSettingsHtml(...),
                LegacyHtmlMode::Static, LegacyHtmlMode::ReadOnly => $component->getReadOnlySettingsHtml(...),
                LegacyHtmlMode::Disabled => fn(): ?string => Html::disableInputs($component->getSettingsHtml(...)),
            },
            $namespace,
            $mode,
        );
    }

    /** @param string|list<string> $path */
    public function field(
        LegacyField $field,
        mixed $value,
        ?ElementInterface $element,
        string|array $path,
        ?string $namespace = null,
        LegacyHtmlMode $mode = LegacyHtmlMode::Editable,
        string|array|null $deltaGroup = null,
    ): ?LegacyHtmlField {
        return $this->capture(
            $path,
            match ($mode) {
                LegacyHtmlMode::Editable => fn(): string => $field->getInputHtml($value, $element),
                LegacyHtmlMode::Static => fn(): string => $element === null
                    ? throw new InvalidArgumentException('Static legacy fields require an element.')
                    : $field->getStaticHtml($value, $element),
                LegacyHtmlMode::ReadOnly, LegacyHtmlMode::Disabled => fn(): string => Html::disableInputs(
                    fn(): string => $field->getInputHtml($value, $element),
                ),
            },
            $namespace,
            $mode,
            $deltaGroup,
        );
    }

    /** @param string|list<string> $path */
    public static function namespace(string|array $path): ?string
    {
        $segments = is_string($path)
            ? ($path === '' ? [] : explode('.', $path))
            : $path;
        $namespace = array_shift($segments);

        foreach ($segments as $segment) {
            $namespace .= "[{$segment}]";
        }

        return $namespace;
    }

    /**
     * @param  string|list<string>  $path
     * @param  callable(): ?string  $hook
     */
    public function capture(
        string|array $path,
        callable $hook,
        ?string $namespace = null,
        LegacyHtmlMode $mode = LegacyHtmlMode::Editable,
        string|array|null $deltaGroup = null,
    ): ?LegacyHtmlField {
        $html = null;
        $resolvedNamespace = $namespace === null
            ? $this->inputNamespace->get()
            : $this->inputNamespace->namespaceInputName($namespace);
        try {
            $fragment = $this->htmlStack->capture(function() use ($hook, $resolvedNamespace, &$html): string {
                $render = function() use ($hook, &$html): string {
                    $html = $hook();

                    return $html ?? '';
                };

                return $resolvedNamespace === null
                    ? $render()
                    : $this->inputNamespace->with(
                        $resolvedNamespace,
                        fn(): string => $this->inputNamespace->namespaceInputs($render(), $resolvedNamespace),
                    );
            });
        } catch (Throwable $exception) {
            $identity = $resolvedNamespace ?? 'root';

            throw new RuntimeException(
                "Failed to capture legacy HTML at [{$identity}] in [{$mode->name}] mode: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if ($html === null) {
            return null;
        }

        $control = LegacyHtmlControl::make($path)
            ->fragment($fragment, $resolvedNamespace)
            ->value($this->parse($fragment->html))
            ->mode($mode->controlMode());

        if ($deltaGroup !== null) {
            $control->deltaGroup($deltaGroup);
        }

        return new LegacyHtmlField($control);
    }

    /** @return array<string, string|list<string>> */
    public function parse(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<!doctype html><html><body><form id="legacy-html-root">' . $html . '</form></body></html>',
                LIBXML_NONET,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded) {
            throw new InvalidArgumentException('Failed to parse captured legacy HTML.');
        }

        $values = [];
        $xpath = new DOMXPath($document);
        $controls = $xpath->query('//*[@id="legacy-html-root"]//*[self::input or self::select or self::textarea][@name]');

        if ($controls === false) {
            throw new InvalidArgumentException('Failed to locate controls in captured legacy HTML.');
        }

        foreach ($controls as $control) {
            if (!$control instanceof DOMElement || $control->hasAttribute('disabled')) {
                continue;
            }

            $name = $control->getAttribute('name');
            $tag = $control->tagName;
            $type = strtolower($control->getAttribute('type'));

            if ($tag === 'input' && in_array($type, ['file', 'submit', 'button', 'reset', 'image'], true)) {
                continue;
            }

            if ($tag === 'input' && in_array($type, ['checkbox', 'radio'], true) && !$control->hasAttribute('checked')) {
                continue;
            }

            if ($tag === 'select') {
                $options = $xpath->query('.//option[@selected and not(@disabled)]', $control);

                if ($options === false) {
                    throw new InvalidArgumentException("Failed to parse legacy select [{$name}].");
                }

                if ($options->length === 0 && !$control->hasAttribute('multiple')) {
                    $options = $xpath->query('.//option[not(@disabled)][1]', $control);
                }

                foreach ($options ?: [] as $option) {
                    if ($option instanceof DOMElement) {
                        $this->append($values, $name, $option->hasAttribute('value') ? $option->getAttribute('value') : $option->textContent);
                    }
                }

                continue;
            }

            $value = $tag === 'textarea'
                ? $control->textContent
                : ($control->hasAttribute('value') ? $control->getAttribute('value') : ($type === 'checkbox' || $type === 'radio' ? 'on' : ''));
            $this->append($values, $name, $value);
        }

        return $values;
    }

    /**
     * @param  array<string, string|list<string>>  $values
     * @return array<string, mixed>
     */
    public function expand(array $values): array
    {
        $parts = [];

        foreach ($values as $name => $value) {
            $items = is_array($value) ? $value : [$value];

            foreach ($items as $item) {
                if (!is_string($item)) {
                    throw new InvalidArgumentException("Legacy input [{$name}] contains a non-string value.");
                }

                $parts[] = rawurlencode($name) . '=' . rawurlencode($item);
            }
        }

        parse_str(implode('&', $parts), $expanded);

        return $expanded;
    }

    /** @param array<string, string|list<string>> $values */
    private function append(array &$values, string $name, string $value): void
    {
        if (!array_key_exists($name, $values)) {
            $values[$name] = $value;

            return;
        }

        $values[$name] = is_array($values[$name])
            ? [...$values[$name], $value]
            : [$values[$name], $value];
    }
}
