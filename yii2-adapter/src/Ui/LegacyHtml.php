<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Ui;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\InputNamespace;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Ui\Contracts\LegacySettingsComponent;
use CraftCms\Yii2Adapter\Ui\Controls\LegacyHtmlControl;
use CraftCms\Yii2Adapter\Ui\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Ui\Nodes\LegacyHtmlField;
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
        bool $inline = false,
    ): ?LegacyHtmlField {
        $input = $inline
            ? ($field instanceof InlineEditableFieldInterface
                ? fn(): string => $field->getInlineInputHtml($value, $element)
                : throw new InvalidArgumentException('Inline legacy fields must support inline editing.'))
            : fn(): string => $field->getInputHtml($value, $element);

        return $this->capture(
            $path,
            match ($mode) {
                LegacyHtmlMode::Editable => $input,
                LegacyHtmlMode::Static => fn(): string => $element === null
                    ? throw new InvalidArgumentException('Static legacy fields require an element.')
                    : $field->getStaticHtml($value, $element),
                LegacyHtmlMode::ReadOnly, LegacyHtmlMode::Disabled => fn(): string => Html::disableInputs($input),
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

        // Editable tables render their inputs client-side, so their values come from the UI payload
        $tables = $xpath->query('//*[@id="legacy-html-root"]//craft-table-ui[@name][@data-payload]');

        foreach ($tables ?: [] as $table) {
            if ($table instanceof DOMElement) {
                $this->appendTableValues($values, $table->getAttribute('name'), Json::decode($table->getAttribute('data-payload')));
            }
        }

        return $values;
    }

    /**
     * @param  array<string, string|list<string>>  $values
     * @param  array<string, mixed>  $payload
     */
    private function appendTableValues(array &$values, string $name, array $payload): void
    {
        $control = $payload['nodes'][0]['control'] ?? [];
        $rows = $payload['values'] ?? [];

        foreach ($control['path'] ?? [] as $segment) {
            $rows = $rows[$segment] ?? [];
        }

        $columns = array_filter(
            $control['props']['columns'] ?? [],
            fn(array $column): bool => !($column['html'] ?? false) && !in_array($column['type'] ?? null, ['heading', 'html'], true),
        );

        foreach (is_array($rows) ? $rows : [] as $rowKey => $row) {
            foreach (array_keys($columns) as $columnKey) {
                $this->appendNested($values, "{$name}[$rowKey][$columnKey]", $row[$columnKey] ?? null);
            }
        }
    }

    /** @param array<string, string|list<string>> $values */
    private function appendNested(array &$values, string $name, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->appendNested($values, "{$name}[$key]", $item);
            }

            return;
        }

        $this->append($values, $name, match (true) {
            $value === null, $value === false => '',
            $value === true => '1',
            default => (string) $value,
        });
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

    /**
     * Returns a field's value from the flat input map its legacy HTML captured, or `$value` itself when it isn't one.
     *
     * A nested field's legacy HTML submits that map as-is until its inputs are edited, such as when a sibling Matrix
     * entry changes, rather than the expanded value its inputs post.
     *
     * @param string|null $paramNamespace The element's field param namespace, such as `fields.links.entries.uid:<uid>.fields`
     */
    public function capturedFieldValue(mixed $value, string $handle, ?string $paramNamespace = null): mixed
    {
        if (!is_array($value) || $value === [] || array_is_list($value)) {
            return $value;
        }

        $root = null;
        $inputs = [];

        foreach ($value as $name => $item) {
            $segments = is_string($name) ? $this->inputSegments($name) : null;
            // Requests convert empty strings to null, including in lists
            $item = is_array($item) ? array_map(fn(mixed $option) => $option ?? '', $item) : $item ?? '';
            $isInputValue = is_string($item) || (is_array($item) && array_is_list($item) && array_all($item, fn(mixed $option) => is_string($option)));

            if ($segments === null || !$isInputValue) {
                return $value;
            }

            $inputs[] = [$segments, $item];

            $length = 0;

            while (isset($segments[$length]) && ($root === null || ($root[$length] ?? null) === $segments[$length])) {
                $length++;
            }

            $root = array_slice($segments, 0, $length);
        }

        // The field's inputs are named after the request param it's posted to, with nested entries keyed by their bare UID
        $valueIndex = null;

        if ($paramNamespace !== null) {
            $path = [
                ...array_map(fn(string $segment) => Str::chopStart($segment, 'uid:'), explode('.', $paramNamespace)),
                $handle,
            ];

            if (array_slice($root, 0, count($path)) === $path) {
                $valueIndex = count($path);
            }
        }

        // Without it, the field's path is taken to end at the last `[fields][<handle>]` pair its inputs share
        if ($valueIndex === null) {
            foreach ($root as $index => $segment) {
                if ($index > 0 && $segment === $handle && $root[$index - 1] === 'fields') {
                    $valueIndex = $index + 1;
                }
            }
        }

        if ($valueIndex === null) {
            return $value;
        }

        // Unlike `parse_str()`, this isn't limited to `max_input_vars` inputs
        $field = null;

        foreach ($inputs as [$segments, $item]) {
            foreach ((array) $item as $option) {
                $this->setInputValue($field, array_slice($segments, $valueIndex), $option);
            }
        }

        return $field;
    }

    /**
     * Returns the segments of a bracketed input name, as `parse_str()` reads them, with `''` for list appends.
     *
     * @return list<string>|null
     */
    private function inputSegments(string $name): ?array
    {
        if (!preg_match('/^([^\[\]]+)((?:\[[^\[\]]*\])+)$/', $name, $match)) {
            return null;
        }

        preg_match_all('/\[([^\[\]]*)\]/', $match[2], $keys);

        return [str_replace(['.', ' '], '_', $match[1]), ...$keys[1]];
    }

    /**
     * Sets an input's value at its path, as `parse_str()` does.
     *
     * @param list<string> $path
     */
    private function setInputValue(mixed &$target, array $path, string $value): void
    {
        if ($path === []) {
            $target = $value;

            return;
        }

        if (!is_array($target)) {
            $target = [];
        }

        $segment = array_shift($path);

        if ($segment === '') {
            $target[] = null;
            $segment = array_key_last($target);
        }

        $this->setInputValue($target[$segment], $path, $value);
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
