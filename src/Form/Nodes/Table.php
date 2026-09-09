<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use Illuminate\Support\Traits\Conditionable;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

/**
 * A listing of rows (e.g. product types, gateways) rendered as a Form Node, backed by the
 * `craft:admin-table` Vue component (`resources/js/modules/forms/AdminTableNode.vue`) on
 * the client.
 *
 * Unlike {@see Heading}/{@see Separator}, a table's rows represent other entities rather
 * than the form's own value at a path, so it deliberately never touches `FormContext`'s
 * values/mode — `props()` is the only channel it uses.
 */
class Table implements Node
{
    use Conditionable;

    /** @var list<array{key: string, label: string}> */
    private array $columns = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    private ?string $emptyMessage = null;

    private ?string $createLabel = null;

    private ?string $createUrl = null;

    private ?string $reorderUrl = null;

    private ?string $deleteUrl = null;

    private ?string $deleteConfirmMessage = null;

    public function __construct(private readonly string $uid) {}

    public static function make(string $uid): self
    {
        return new self($uid);
    }

    /** @param list<array{key: string, label: string}> $columns */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param  list<array<string, mixed>>  $rows  Each row is keyed by column `key`, plus an `id`
     *   entry identifying the row — required when {@see reorderable()} or {@see deletable()} are
     *   used. A cell value may be a plain scalar; an array shaped `['label' => string, 'url' =>
     *   ?string]` to render as a link (or plain text when `url` is null); a list of such arrays
     *   to render several links in one cell; `['label' => string, 'items' => list<array{label:
     *   string, url: ?string}>]` to render a dropdown menu of links; `['icon' => string, 'label' =>
     *   ?string]` to render a single icon (`label` becomes its accessible name, and is what the
     *   non-JS {@see renderHtml()} fallback shows in place of the icon); or `['html' => string]`
     *   for markup none of the above can express (a styled `<code>` value, a compound badge). The
     *   `html` shape is run through the same sanitizer {@see TemplateContent} uses (blocking
     *   `form`, dropping `button`/`input`/`optgroup`/`option`/`select`/`textarea`) as a
     *   defense-in-depth backstop — but sanitizing isn't encoding: the caller is still responsible
     *   for {@see Html::encode()}-ing any user-entered value it interpolates into the string before
     *   it ever reaches here, exactly as for `TemplateContent`. Prefer one of the structured shapes
     *   above when it fits; `html` exists for what doesn't. A row may set `_deletable => false` to
     *   suppress its own delete action even when the table as a whole is {@see deletable()} (e.g. a
     *   "primary" row that can't be removed).
     */
    public function rows(array $rows): static
    {
        $this->rows = array_map(
            fn(array $row) => array_map(self::sanitizeCell(...), $row),
            $rows,
        );

        return $this;
    }

    private static function sanitizeCell(mixed $value): mixed
    {
        if (!is_array($value) || !array_key_exists('html', $value)) {
            return $value;
        }

        $config = app(HtmlSanitizerManager::class)->defaultConfig()
            ->blockElement('form');

        foreach (['button', 'input', 'optgroup', 'option', 'select', 'textarea'] as $element) {
            $config = $config->dropElement($element);
        }

        return ['html' => new HtmlSanitizer($config)->sanitize($value['html'])];
    }

    public function emptyMessage(?string $emptyMessage): static
    {
        $this->emptyMessage = $emptyMessage;

        return $this;
    }

    public function createAction(?string $label, ?string $url): static
    {
        $this->createLabel = $label;
        $this->createUrl = $url;

        return $this;
    }

    /** Enables drag-to-reorder; the new order posts to `$url` as `{ids: list<int|string>}`. */
    public function reorderable(string $url): static
    {
        $this->reorderUrl = $url;

        return $this;
    }

    /**
     * Adds a per-row delete action, posting `{id: <row id>}` to `$url`. Individual rows can
     * opt out via `_deletable => false` in {@see rows()}.
     */
    public function deletable(string $url, ?string $confirmMessage = null): static
    {
        $this->deleteUrl = $url;
        $this->deleteConfirmMessage = $confirmMessage;

        return $this;
    }

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $columns = $node->props['columns'];
        $rows = $node->props['rows'];

        $createAction = $node->props['createUrl'] !== null && $node->props['createLabel'] !== null
            ? Html::a(Html::encode($node->props['createLabel']), $node->props['createUrl'], [
                'class' => ['btn', 'submit', 'add', 'icon'],
            ])
            : '';

        if (empty($rows)) {
            $table = Html::tag('p', Html::encode($node->props['emptyMessage'] ?? ''), [
                'class' => ['zilch'],
            ]);
        } else {
            $renderLink = fn(array $link): string => $link['url'] !== null
                ? Html::a(Html::encode($link['label']), $link['url'])
                : Html::encode($link['label']);

            // Reordering and deleting are inherently interactive (drag handles, confirmation
            // dialogs, CSRF-protected requests) with no sensible plain-HTML equivalent, so this
            // fallback renders a menu's links inline but otherwise omits those two affordances —
            // consistent with the rest of the CP treating this renderer as JS-less read access,
            // not a full replacement for the Vue control.
            $renderCell = function(array $column, array $row) use ($renderLink): string {
                $value = $row[$column['key']] ?? '';

                if (is_array($value) && array_key_exists('items', $value)) {
                    return implode(', ', array_map($renderLink, $value['items']));
                }

                if (is_array($value) && array_key_exists('icon', $value)) {
                    return Html::encode($value['label'] ?? '');
                }

                if (is_array($value) && array_key_exists('html', $value)) {
                    // Already sanitized in rows() — not re-encoded, this is meant to be markup.
                    return $value['html'];
                }

                if (is_array($value) && array_is_list($value)) {
                    return implode(', ', array_map($renderLink, $value));
                }

                if (is_array($value)) {
                    return $renderLink($value);
                }

                return Html::encode((string) $value);
            };

            $head = Html::tag('tr', implode('', array_map(
                fn(array $column) => Html::tag('th', Html::encode($column['label'])),
                $columns,
            )));

            $body = implode('', array_map(
                fn(array $row) => Html::tag('tr', implode('', array_map(
                    fn(array $column) => Html::tag('td', $renderCell($column, $row)),
                    $columns,
                ))),
                $rows,
            ));

            $table = Html::tag('table', Html::tag('thead', $head).Html::tag('tbody', $body), [
                'class' => ['data', 'fullwidth'],
            ]);
        }

        return Html::tag('div', $createAction.$table, [
            'class' => ['grid', 'gap-2'],
            'data-form-node' => $node->uid,
        ]);
    }

    public function component(): string
    {
        return 'craft:admin-table';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'columns' => $this->columns,
            'rows' => $this->rows,
            'emptyMessage' => $this->emptyMessage,
            'createLabel' => $this->createLabel,
            'createUrl' => $this->createUrl,
            'reorderUrl' => $this->reorderUrl,
            'deleteUrl' => $this->deleteUrl,
            'deleteConfirmMessage' => $this->deleteConfirmMessage,
        ];
    }

    public function getControl(): ?Control
    {
        return null;
    }

    public function children(): array
    {
        return [];
    }
}
