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

/** A listing of rows supplied through node props, not form values. */
class Table implements Node
{
    use Conditionable;

    /** @var list<array{key: string, label: string}> */
    private array $columns = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    private ?string $dataUrl = null;

    private int $perPage = 100;

    /** @var list<int> */
    private array $perPageOptions = [50, 100, 250];

    private ?string $moveToPageUrl = null;

    private ?string $emptyMessage = null;

    private ?string $createLabel = null;

    private ?string $createUrl = null;

    /** @var list<array{label: string, url: string}>|null */
    private ?array $createMenuItems = null;

    private bool $createActionInPageHeader = false;

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

    /** @var list<array{value: string, label: string}>|null */
    private ?array $statusFilterOptions = null;

    private bool $searchable = false;

    private ?string $searchPlaceholder = null;

    private bool $bordered = false;

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
     * Rows are keyed by column `key`, with an `id` when reordering or deleting.
     * Cells accept scalars, `['label' => string, 'url' => ?string]` links, lists of
     * links, `['label' => string, 'items' => list<links>]` menus, `['icon' => string,
     * 'label' => ?string]` icons, or `['html' => string]` markup. HTML is rendered
     * without sanitization in both renderers. Encode untrusted content with
     * {@see Html::encode()} before passing it.
     *
     * `_deletable => false` suppresses deletion of one row. `_status` accepts a
     * boolean or status string and renders an indicator in the first column.
     * `_search` overrides client-side search text; otherwise columns' text is used.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function rows(array $rows): static
    {
        $this->rows = self::prepareRows($rows);
        $this->dataUrl = null;

        return $this;
    }

    /**
     * Resolve `_status` on rows returned by a {@see dataUrl()} endpoint; {@see rows()}
     * calls this automatically for upfront rows.
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
        if (! array_key_exists('_status', $row) || $row['_status'] === null) {
            return $row;
        }

        $status = $row['_status'];
        $status = is_bool($status) ? ($status ? 'enabled' : 'disabled') : $status;

        $row['_status'] = [
            'value' => $status,
            'fill' => self::statusFill($status),
            'label' => ucfirst($status),
        ];

        return $row;
    }

    private static function statusFill(string $status): string
    {
        return (Color::tryFromStatus($status) ?? Color::Gray)->value;
    }

    /**
     * Fetch rows by posting `{page, per_page, search, status}` to `$url`. The response must contain
     * `{data: <rows>, pagination: {total, per_page, current_page, last_page, next_page_url,
     * prev_page_url, from, to}}`. Pass the page's rows through {@see prepareRows()} first.
     * Calling this clears {@see rows()}, and vice versa.
     *
     * Users can switch `per_page` between `$perPageOptions` (plus `$perPage`), so the
     * endpoint must honor the posted value rather than assume `$perPage`.
     *
     * @param  list<int>|null  $perPageOptions
     */
    public function dataUrl(string $url, int $perPage = 100, ?array $perPageOptions = null): static
    {
        $this->dataUrl = $url;
        $this->perPage = $perPage;
        $this->rows = [];

        if ($perPageOptions !== null) {
            $this->perPageOptions = $perPageOptions;
        }

        return $this;
    }

    /**
     * In {@see dataUrl()} mode, posts `{id, page, per_page}` to `$url` to move a row across
     * pages. The endpoint computes the new absolute position.
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
     * Render a create button with a menu of links instead of one {@see createAction()} URL.
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
     * Render the {@see createAction()} or {@see createActionMenu()} button in the page header
     * instead of the table's toolbar.
     */
    public function createActionInPageHeader(bool $inPageHeader = true): static
    {
        $this->createActionInPageHeader = $inPageHeader;

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
     * `$bulk` enables row selection and posts `{ids: <row ids>}` to the same endpoint.
     * Enable it only if the endpoint handles `ids` as well as `id`.
     */
    public function deletable(string $url, ?string $confirmMessage = null, bool $bulk = false): static
    {
        $this->deleteUrl = $url;
        $this->deleteConfirmMessage = $confirmMessage;
        $this->bulkDeletable = $bulk;

        return $this;
    }

    /**
     * Adds items to the selection footer's "Actions" menu. Each action posts
     * `{ids: <selected row ids>, ...params}` to its `url`.
     *
     * Entries may be `['label' => string, 'url' => string, 'params'? => array,
     * 'allowMultiple'? => bool]` or groups with `['label'? => string, 'items' => list<actions>]`.
     * An omitted group label leaves its items unheaded; `icon` is ignored. `allowMultiple`
     * defaults to true and only disables the UI for multiple rows. Endpoints must enforce
     * their own constraints.
     *
     * @param  list<array<string, mixed>>  $actions
     */
    public function bulkActions(array $actions): static
    {
        $this->bulkActions = $actions;

        return $this;
    }

    /**
     * Adds a separate "Set status" menu to the selection footer. Items use the same
     * shape as single {@see bulkActions()} entries, plus an optional `fill` (a colored
     * status dot — any `craft-indicator` `fill` value); the button label is fixed.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function statusActions(array $items): static
    {
        $this->statusActions = $items;

        return $this;
    }

    /**
     * Adds a status dropdown to the toolbar, defaulting to enabled/disabled. Filters
     * {@see rows()} locally on `_status`, or sends the chosen `status` to the {@see dataUrl()}
     * endpoint and resets to page 1. "All" sends no `status`.
     *
     * @param  list<array{value: string, label: string}>|null  $options
     */
    public function statusFilter(?array $options = null): static
    {
        $this->statusFilterOptions = $options ?? [
            ['value' => 'enabled', 'label' => t('Enabled')],
            ['value' => 'disabled', 'label' => t('Disabled')],
        ];

        return $this;
    }

    /**
     * Search {@see rows()} locally, or send `search` to the {@see dataUrl()} endpoint
     * and reset to page 1. See {@see rows()} for the `_search` override.
     */
    public function searchable(?string $placeholder = null): static
    {
        $this->searchable = true;
        $this->searchPlaceholder = $placeholder;

        return $this;
    }

    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;

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
            ! empty($node->props['createMenuItems']) => Html::encode($node->props['createLabel'] ?? '').': '.implode(', ', array_map(
                fn (array $item) => Html::a(Html::encode($item['label']), $item['url']),
                $node->props['createMenuItems'],
            )),
            default => '',
        };

        if ($node->props['dataUrl'] !== null) {
            // Without JavaScript, endpoint rows cannot be fetched or treated as an empty table.
            $table = Html::tag('p', Html::encode(t('This table requires JavaScript.')), [
                'class' => ['zilch'],
            ]);
        } elseif (empty($rows)) {
            $table = Html::tag('p', Html::encode($node->props['emptyMessage'] ?? ''), [
                'class' => ['zilch'],
            ]);
        } else {
            $renderLink = fn (array $link): string => $link['url'] !== null
                ? Html::a(Html::encode($link['label']), $link['url'])
                : Html::encode($link['label']);

            $firstColumnKey = $columns[0]['key'] ?? null;

            $renderCell = function (array $column, array $row) use ($renderLink, $firstColumnKey): string {
                $value = $row[$column['key']] ?? '';

                $rendered = match (true) {
                    is_array($value) && array_key_exists('items', $value) => implode(', ', array_map($renderLink, $value['items'])),
                    is_array($value) && array_key_exists('icon', $value) => Html::encode($value['label'] ?? ''),
                    is_array($value) && array_key_exists('html', $value) => $value['html'],
                    is_array($value) && array_is_list($value) => implode(', ', array_map($renderLink, $value)),
                    is_array($value) => $renderLink($value),
                    default => Html::encode((string) $value),
                };

                if ($column['key'] === $firstColumnKey && ! empty($row['_status']['label'])) {
                    $rendered = Html::encode($row['_status']['label']).': '.$rendered;
                }

                return $rendered;
            };

            $head = Html::tag('tr', implode('', array_map(
                fn (array $column) => Html::tag('th', Html::encode($column['label'])),
                $columns,
            )));

            $body = implode('', array_map(
                fn (array $row) => Html::tag('tr', implode('', array_map(
                    fn (array $column) => Html::tag('td', $renderCell($column, $row)),
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
            'perPageOptions' => $this->resolvePerPageOptions(),
            'moveToPageUrl' => $this->moveToPageUrl,
            'emptyMessage' => $this->emptyMessage,
            'createLabel' => $this->createLabel,
            'createUrl' => $this->createUrl,
            'createMenuItems' => $this->createMenuItems,
            'createActionInPageHeader' => $this->createActionInPageHeader,
            'reorderUrl' => $this->reorderUrl,
            'reorderSuccessMessage' => $this->reorderSuccessMessage,
            'reorderFailMessage' => $this->reorderFailMessage,
            'deleteUrl' => $this->deleteUrl,
            'deleteConfirmMessage' => $this->deleteConfirmMessage,
            'bulkDeletable' => $this->bulkDeletable,
            'bulkActions' => $this->bulkActions,
            'statusActions' => $this->statusActions,
            'statusFilterOptions' => $this->resolveStatusFilterOptions(),
            'searchable' => $this->searchable,
            'searchPlaceholder' => $this->searchPlaceholder,
            'bordered' => $this->bordered,
        ];
    }

    /** @return list<array{value: string, label: string, fill: string|null}> */
    private function resolveStatusFilterOptions(): array
    {
        if ($this->statusFilterOptions === null) {
            return [];
        }

        return [
            ['value' => '', 'label' => t('All'), 'fill' => null],
            ...array_map(fn (array $option) => [
                'value' => $option['value'],
                'label' => $option['label'],
                'fill' => self::statusFill($option['value']),
            ], $this->statusFilterOptions),
        ];
    }

    /** @return list<int> */
    private function resolvePerPageOptions(): array
    {
        $options = array_unique([...$this->perPageOptions, $this->perPage]);
        sort($options);

        return $options;
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
