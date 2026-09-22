<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Traits\Conditionable;
use function CraftCms\Cms\t;

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

    private ?string $dataUrl = null;

    private int $perPage = 100;

    private ?string $moveToPageUrl = null;

    private ?string $emptyMessage = null;

    private ?string $createLabel = null;

    private ?string $createUrl = null;

    /** @var list<array{label: string, url: string}>|null */
    private ?array $createMenuItems = null;

    private ?string $reorderUrl = null;

    private ?string $reorderSuccessMessage = null;

    private ?string $reorderFailMessage = null;

    private ?string $deleteUrl = null;

    private ?string $deleteConfirmMessage = null;

    private bool $bulkDeletable = false;

    /** @var list<array<string, mixed>> */
    private array $bulkActions = [];

    /** @var list<array<string, mixed>> */
    private array $statusActions = [];

    private bool $searchable = false;

    private ?string $searchPlaceholder = null;

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
     *   A row may also set `_status` to get a colored status indicator dot before the *first*
     *   column's own content — the same dot a Craft element index chip shows, for a row whose
     *   underlying model isn't a real element (so an actual chip, and the `Statusable` status it'd
     *   need, aren't available). `true`/`false` renders the plain enabled/disabled dot most rows
     *   want; a string is resolved the same way {@see \CraftCms\Cms\Shared\Enums\Color::
     *   tryFromStatus()} resolves one elsewhere (`'live'`, `'pending'`, `'expired'`, an arbitrary
     *   custom status name, ...), for a row whose model has a richer status than a plain boolean.
     *   When {@see searchable()} is on, a row may also set `_search` to the plain text its own
     *   search box should match against — needed whenever anything worth searching isn't itself a
     *   visible column (a discount's description, or its coupon codes, say — legacy's own search
     *   matched both, neither ever a column here either). A row without `_search` falls back to
     *   matching its declared columns' own rendered text.
     */
    public function rows(array $rows): static
    {
        $this->rows = self::prepareRows($rows);
        $this->dataUrl = null;

        return $this;
    }

    /**
     * Resolves `_status` the same way for a row whether it's handed to {@see rows()} directly
     * (upfront mode) or built by a controller's own paginated `tableData()`-style action
     * ({@see dataUrl()} mode, where that action's response bypasses `rows()` entirely) — call
     * this on a page's worth of rows before returning them as JSON, so both modes resolve
     * `_status` identically.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function prepareRows(array $rows): array
    {
        return array_map(self::resolveRowStatus(...), $rows);
    }

    /** @param array<string, mixed> $row */
    private static function resolveRowStatus(array $row): array
    {
        if (!array_key_exists('_status', $row) || $row['_status'] === null) {
            return $row;
        }

        $status = $row['_status'];
        $status = is_bool($status) ? ($status ? 'enabled' : 'disabled') : $status;

        $row['_status'] = [
            'fill' => (Color::tryFromStatus($status) ?? Color::Gray)->value,
            'label' => ucfirst($status),
        ];

        return $row;
    }

    /**
     * Alternative to {@see rows()} for a list too large to hand over upfront: the client fetches
     * each page (and, when {@see searchable()} is also on, searches) from `$url` instead, posting
     * `{page, per_page, search}` and expecting back `{data: <rows, same shape rows() documents>,
     * pagination: {total, per_page, current_page, last_page, next_page_url, prev_page_url, from,
     * to}}` — build that response the same way `CraftCms\Cms\Http\ViewModels\
     * ContentIndexViewModel::pagination()` does (a plain `Illuminate\Pagination\
     * LengthAwarePaginator` over an already-sliced array; no Eloquent query required), and run
     * each page's rows through {@see prepareRows()} before responding.
     *
     * Mutually exclusive with {@see rows()} — calling one clears whichever the other set.
     */
    public function dataUrl(string $url, int $perPage = 100): static
    {
        $this->dataUrl = $url;
        $this->perPage = $perPage;
        $this->rows = [];

        return $this;
    }

    /**
     * Only meaningful alongside {@see dataUrl()}: the endpoint a "Move to page…" control posts
     * `{id, page}` to, for moving a row to a different page than the one it's currently on (the
     * within-page drag-and-drop {@see reorderable()} already offers can't reach a page that
     * isn't loaded). The endpoint owns computing the row's new absolute position from `page` and
     * applying it — this Node has no opinion on how.
     */
    public function moveToPageUrl(string $url): static
    {
        $this->moveToPageUrl = $url;

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

    /**
     * Enables drag-to-reorder; the new order posts to `$url` as `{ids: list<int|string>}`.
     * `$successMessage`/`$failMessage` are shown as a toast after the request settles — omit
     * either (or both) to fall back to a generic message client-side.
     */
    public function reorderable(string $url, ?string $successMessage = null, ?string $failMessage = null): static
    {
        $this->reorderUrl = $url;
        $this->reorderSuccessMessage = $successMessage;
        $this->reorderFailMessage = $failMessage;

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
     * Adds items to the selection footer's single "Actions" menu (shown once at least one
     * row is selected, alongside "Clear selection") — matching the real Entries index's own
     * selection-footer convention: one "Actions" menu holding every uncommon per-selection
     * action (a "move to page…" control when {@see moveToPageUrl()} is set, a trailing
     * destructive "Delete" when the table is {@see deletable()} with `bulk: true`, and every
     * entry here), never a separate button per action or a disclosure control on each row.
     * Every action posts `{ids: <selected row ids>, ...params}` to its own `url`; there's no
     * client-side notion of what the action does beyond that; the endpoint owns applying it
     * and returning a normal flash response.
     *
     * Each entry in `$actions` is either:
     * - a single action: `['label' => string, 'url' => string, 'params'? => array<string,
     *   mixed>, 'allowMultiple'? => bool]` — `params` is merged into the posted body
     *   alongside `ids`; `allowMultiple` (default `true`) disables the item whenever more
     *   than one row is selected, for an action that only makes sense against one row at a
     *   time (a UI nicety only — the endpoint still gets whatever `ids` a request carries,
     *   and is responsible for enforcing that itself if it matters).
     * - a labeled group of single actions in the same shape: `['label'? => string, 'items' =>
     *   list<array{label: string, url: string, params?: array<string, mixed>, allowMultiple?:
     *   bool}>]`, shown as a heading followed by its items within the same "Actions" menu.
     *   Omitting `label` folds the items in unheaded, blended into the flat list — legacy's
     *   own unlabeled gear-icon menu for a single, infrequently-needed item (e.g. shipping
     *   categories' "Set Default Category") reads this way now that there's one shared
     *   invoker rather than its own separate icon-only button; an `icon` alone no longer
     *   produces a distinct invoker and is ignored.
     *
     * Reaches for a row-selection checkbox column the same way `deletable(..., bulk: true)`
     * does — either one turns selection on; a table with both just contributes to the same
     * footer's "Actions" menu.
     *
     * @param  list<array<string, mixed>>  $actions
     */
    public function bulkActions(array $actions): static
    {
        $this->bulkActions = $actions;

        return $this;
    }

    /**
     * Adds a dedicated "Set status" button to the selection footer, kept separate from
     * {@see bulkActions()}'s own "Actions" menu — matching the real Craft element index's own
     * `BulkActionsBar.vue`, which always pulls its Set Status button out of the generic
     * actions menu (it identifies it there by a well-known PHP action class key) specifically
     * *because* it's the most-reached-for action, not because it's a different kind of thing.
     * "Set status" is the button's own fixed label, not configurable here — `$items` is just
     * its menu content, the same shape as a {@see bulkActions()} single action: `list<array{
     * label: string, url: string, params?: array<string, mixed>, allowMultiple?: bool}>`
     * (typically `Enabled`/`Disabled`, each posting a different `status` param to one `url`).
     *
     * Reaches for a row-selection checkbox column the same way `deletable(..., bulk: true)`/
     * `bulkActions()` do — any of the three turns selection on.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function statusActions(array $items): static
    {
        $this->statusActions = $items;

        return $this;
    }

    /**
     * Shows a text search box above the table. With {@see rows()} (the default), it filters
     * entirely client-side — every row is already loaded. With {@see dataUrl()}, the query is
     * instead sent as a `search` param to that endpoint (resetting to page 1), matching the
     * legacy `Craft.VueAdminTable` screens this usually replaces, which searched server-side
     * against a paginated result set — see {@see dataUrl()} for the exact request/response
     * contract. See {@see rows()} for how a row controls what it matches against in the
     * client-side case. Most tables don't need this — reach for it only where legacy actually
     * had `search: true`.
     */
    public function searchable(?string $placeholder = null): static
    {
        $this->searchable = true;
        $this->searchPlaceholder = $placeholder;

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

        if ($node->props['dataUrl'] !== null) {
            // Same "no sensible non-JS equivalent" reasoning already applied to reorder/delete/
            // bulk-actions/search: fetching and paginating rows client-side has no plain-HTML
            // fallback, so this renders neither an empty table nor (potentially misleadingly)
            // the table's own `emptyMessage`, which describes a genuinely empty table, not one
            // this fallback simply can't populate.
            $table = Html::tag('p', Html::encode(t('This table requires JavaScript.')), [
                'class' => ['zilch'],
            ]);
        } elseif (empty($rows)) {
            $table = Html::tag('p', Html::encode($node->props['emptyMessage'] ?? ''), [
                'class' => ['zilch'],
            ]);
        } else {
            $renderLink = fn(array $link): string => $link['url'] !== null
                ? Html::a(Html::encode($link['label']), $link['url'])
                : Html::encode($link['label']);

            $firstColumnKey = $columns[0]['key'] ?? null;

            // Reordering, deleting, and bulk actions are inherently interactive (drag handles,
            // confirmation dialogs, row selection, CSRF-protected requests) with no sensible
            // plain-HTML equivalent, so this fallback renders a menu's links inline but
            // otherwise omits those affordances — consistent with the rest of the CP treating
            // this renderer as JS-less read access, not a full replacement for the Vue control.
            $renderCell = function(array $column, array $row) use ($renderLink, $firstColumnKey): string {
                $value = $row[$column['key']] ?? '';

                $rendered = match (true) {
                    is_array($value) && array_key_exists('items', $value) => implode(', ', array_map($renderLink, $value['items'])),
                    is_array($value) && array_key_exists('icon', $value) => Html::encode($value['label'] ?? ''),
                    // Not re-encoded — this is meant to be markup, and rows() no longer
                    // sanitizes it (see its docblock); the caller owns that trust boundary.
                    is_array($value) && array_key_exists('html', $value) => $value['html'],
                    is_array($value) && array_is_list($value) => implode(', ', array_map($renderLink, $value)),
                    is_array($value) => $renderLink($value),
                    default => Html::encode((string) $value),
                };

                // The dot itself is inherently visual, so — same reasoning as the icon-cell
                // case above — this fallback substitutes its resolved label as plain text
                // instead, right before whatever the first column already rendered.
                if ($column['key'] === $firstColumnKey && !empty($row['_status']['label'])) {
                    $rendered = Html::encode($row['_status']['label']).': '.$rendered;
                }

                return $rendered;
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
            'dataUrl' => $this->dataUrl,
            'perPage' => $this->perPage,
            'moveToPageUrl' => $this->moveToPageUrl,
            'emptyMessage' => $this->emptyMessage,
            'createLabel' => $this->createLabel,
            'createUrl' => $this->createUrl,
            'createMenuItems' => $this->createMenuItems,
            'reorderUrl' => $this->reorderUrl,
            'reorderSuccessMessage' => $this->reorderSuccessMessage,
            'reorderFailMessage' => $this->reorderFailMessage,
            'deleteUrl' => $this->deleteUrl,
            'deleteConfirmMessage' => $this->deleteConfirmMessage,
            'bulkDeletable' => $this->bulkDeletable,
            'bulkActions' => $this->bulkActions,
            'statusActions' => $this->statusActions,
            'searchable' => $this->searchable,
            'searchPlaceholder' => $this->searchPlaceholder,
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
