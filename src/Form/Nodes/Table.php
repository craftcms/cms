<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Traits\Conditionable;

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

    /** @var list<array{label: string, url: string}>|null */
    private ?array $createMenuItems = null;

    private ?string $reorderUrl = null;

    private ?string $deleteUrl = null;

    private ?string $deleteConfirmMessage = null;

    private bool $bulkDeletable = false;

    /** @var list<array<string, mixed>> */
    private array $bulkActions = [];

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
     *   for markup none of the above can express (a styled `<code>` value, a compound badge, a
     *   working custom element like `<craft-input-copy>` that needs its own real light-DOM
     *   `<input>`). Unlike {@see TemplateContent}, `html` here is rendered completely unsanitized
     *   — some index tables need cells that are more than static display (a real copy-to-clipboard
     *   control, for instance), which a sanitizer that drops `input`/`button`/etc. as defense in
     *   depth would break. That means the caller owns this trust boundary entirely: run untrusted
     *   values through {@see Html::encode()} (or a sanitizer, if the value is itself meant to carry
     *   markup) before interpolating them into the string, exactly as if writing directly to the
     *   page. Prefer one of the structured shapes above when it fits; `html` exists for what
     *   doesn't. A row may set `_deletable => false` to suppress its own delete action even when
     *   the table as a whole is {@see deletable()} (e.g. a "primary" row that can't be removed).
     */
    public function rows(array $rows): static
    {
        $this->rows = $rows;

        return $this;
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
        $this->createMenuItems = null;

        return $this;
    }

    /**
     * Like {@see createAction()}, but for when there's more than one place a new row could come
     * from (e.g. one per store) — renders as a single button that opens a menu of links instead
     * of linking straight to `$url`.
     *
     * @param  list<array{label: string, url: string}>  $items
     */
    public function createActionMenu(string $label, array $items): static
    {
        $this->createLabel = $label;
        $this->createUrl = null;
        $this->createMenuItems = $items;

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
     *
     * `$bulk` additionally renders a row-selection checkbox column and a "N selected" bar with
     * its own bulk delete button, posting `{ids: <row ids>}` to the same `$url` — set it only
     * when that action genuinely handles an `ids` array alongside a single `id` (mirroring the
     * legacy dual `id`/`ids` contract some of these actions still carry). Leave it `false`
     * (the default) for an action that only understands `id`; turning bulk selection on for
     * one of those doesn't add a client-side capability so much as start sending it requests
     * it will reject.
     */
    public function deletable(string $url, ?string $confirmMessage = null, bool $bulk = false): static
    {
        $this->deleteUrl = $url;
        $this->deleteConfirmMessage = $confirmMessage;
        $this->bulkDeletable = $bulk;

        return $this;
    }

    /**
     * Adds bulk action buttons to the selection footer (shown once at least one row is
     * selected, alongside "Clear selection" and — if the table is {@see deletable()} with
     * `bulk: true` — a trailing "Delete" button). Every action posts `{ids: <selected row
     * ids>, ...params}` to its own `url`; there's no client-side notion of what the action
     * does beyond that; the endpoint owns applying it and returning a normal flash response.
     *
     * Each entry in `$actions` is either:
     * - a single action: `['label' => string, 'url' => string, 'params'? => array<string,
     *   mixed>, 'allowMultiple'? => bool]` — `params` is merged into the posted body
     *   alongside `ids`; `allowMultiple` (default `true`) disables the button whenever more
     *   than one row is selected, for an action that only makes sense against one row at a
     *   time (a UI nicety only — the endpoint still gets whatever `ids` a request carries,
     *   and is responsible for enforcing that itself if it matters).
     * - a dropdown menu of single actions in the same shape: `['label'? => string, 'icon'? =>
     *   string, 'items' => list<array{label: string, url: string, params?: array<string,
     *   mixed>, allowMultiple?: bool}>]`. Omit `label` (pairing it with an `icon`) for an
     *   icon-only invoker — the button shows just that icon, no visible text — matching
     *   legacy's own unlabeled gear-icon menu for a single, infrequently-needed item (e.g.
     *   shipping categories' "Set Default Category").
     *
     * Reaches for a row-selection checkbox column the same way `deletable(..., bulk: true)`
     * does — either one turns selection on; a table with both just contributes its own
     * button(s) to the same footer.
     *
     * @param  list<array<string, mixed>>  $actions
     */
    public function bulkActions(array $actions): static
    {
        $this->bulkActions = $actions;

        return $this;
    }

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $columns = $node->props['columns'];
        $rows = $node->props['rows'];

        $createAction = match (true) {
            $node->props['createUrl'] !== null && $node->props['createLabel'] !== null => Html::a(
                Html::encode($node->props['createLabel']),
                $node->props['createUrl'],
                ['class' => ['btn', 'submit', 'add', 'icon']],
            ),
            // No sensible JS-less dropdown-button equivalent — same reasoning renderCell()'s
            // `items` shape gives for menu cells — so this renders the label followed by its
            // items as plain inline links instead.
            !empty($node->props['createMenuItems']) => Html::encode($node->props['createLabel'] ?? '').': '.implode(', ', array_map(
                fn(array $item) => Html::a(Html::encode($item['label']), $item['url']),
                $node->props['createMenuItems'],
            )),
            default => '',
        };

        if (empty($rows)) {
            $table = Html::tag('p', Html::encode($node->props['emptyMessage'] ?? ''), [
                'class' => ['zilch'],
            ]);
        } else {
            $renderLink = fn(array $link): string => $link['url'] !== null
                ? Html::a(Html::encode($link['label']), $link['url'])
                : Html::encode($link['label']);

            // Reordering, deleting, and bulk actions are inherently interactive (drag handles,
            // confirmation dialogs, row selection, CSRF-protected requests) with no sensible
            // plain-HTML equivalent, so this fallback renders a menu's links inline but
            // otherwise omits those affordances — consistent with the rest of the CP treating
            // this renderer as JS-less read access, not a full replacement for the Vue control.
            $renderCell = function(array $column, array $row) use ($renderLink): string {
                $value = $row[$column['key']] ?? '';

                if (is_array($value) && array_key_exists('items', $value)) {
                    return implode(', ', array_map($renderLink, $value['items']));
                }

                if (is_array($value) && array_key_exists('icon', $value)) {
                    return Html::encode($value['label'] ?? '');
                }

                if (is_array($value) && array_key_exists('html', $value)) {
                    // Not re-encoded — this is meant to be markup, and rows() no longer
                    // sanitizes it (see its docblock); the caller owns that trust boundary.
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
            'createMenuItems' => $this->createMenuItems,
            'reorderUrl' => $this->reorderUrl,
            'deleteUrl' => $this->deleteUrl,
            'deleteConfirmMessage' => $this->deleteConfirmMessage,
            'bulkDeletable' => $this->bulkDeletable,
            'bulkActions' => $this->bulkActions,
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
