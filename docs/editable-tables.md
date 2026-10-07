# Editable tables

Editable tables use the [Control Panel Form system](forms.md). PHP defines the columns and their controls; Vue renders
the cells and manages row edits. The host form owns saving, authorization, and persistence.

There are two related APIs:

| API | Purpose |
| --- | --- |
| `CraftCms\Cms\Field\Table` | A content field with configurable columns, cell normalization, validation, search, and GraphQL support. |
| `CraftCms\Cms\Ui\Controls\Table` | A reusable table editor for a PHP-defined Form, including component settings. Its columns contain Form controls. |

Register a **table cell type** to add an option to the Table field's column type selector. Supply a **Form control**
directly when building a table inside your own settings Form.

## Rows and columns

A Table field stores an ordered list of row maps in JSON. Column IDs such as `col1` identify stored cells. Column handles
provide aliases when Craft normalizes the field value, so templates can access a cell by either its ID or its handle.
Serialization writes the column IDs, not the handle aliases.

For example, this column configuration:

```php
$columns = [
    'col1' => [
        'heading' => 'Name',
        'handle' => 'name',
        'type' => 'singleline',
    ],
    'col2' => [
        'heading' => 'Available',
        'handle' => 'available',
        'type' => 'checkbox',
    ],
];
```

stores a row as `['col1' => 'Example', 'col2' => true]`. Its normalized value also exposes `name` and `available`.
Renaming a handle does not change the column's storage ID.

The built-in Table field types are `singleline`, `multiline`, `number`, `checkbox`, `lightswitch`, `color`, `date`, `time`,
`email`, `url`, `select`, and `heading`. Heading cells display values from the field's default rows and are omitted from
stored cell data. Dropdowns use the configured default option, or the first option when no default is selected.

## Rendering and editing

The Table field asks each cell type for a Form control. The reusable Table control builds a nested Form for each row and
resolves a row template for rows added in the browser. Cells therefore use the same controls, modes, and error handling
as other Control Panel fields.

The current implementation adds, deletes, and reorders rows locally. These operations emit a Form mutation; they do not
require a server request to construct every new row. A host can refresh a reactive Form after a mutation, but the table
does not provide a refresh endpoint of its own. Cell Forms are not independently refreshable.

- Adding a row uses the configured default row values. The field's default rows separately define its initial value.
- Minimum and maximum row counts constrain the row controls. The host must still validate submitted data on the server.
- Static rows follow the field's default rows, retain a `rowId`, and cannot be added, removed, or reordered by the editor.
- Reordering supports dragging and the handle's move-up/down menu. Values and input paths follow the new row order.
- Adding, deleting, and reordering are blocked while submission or a Form refresh is pending. Changing row structure
  clears stale cell errors rather than moving index-addressed errors onto a different row.
- Compatible text cells support tab-separated paste. Enter moves to the next row in the same column; Shift+Enter moves
  back. In multiline cells, use Ctrl+Enter or Cmd+Enter. These features skip static or disabled cells and respect row limits.

PHP-rendered Forms mount the same Vue editor through `<craft-table-form>`, preserving namespaced input names for native
form submission. The table-column settings editor also uses the shared table editor, with a Configure action for each
column's cell-type settings.

## Add a cell type

Extend `CraftCms\Cms\Field\TableCells\TableCell`, or extend a built-in cell type when its value behavior fits your type.
The base class implements `TableCellInterface` and the configurable-component contract.

The following type uses the built-in number normalization, an integer GraphQL type, and a configurable minimum:

```php
<?php

declare(strict_types=1);

namespace Acme\Inventory\TableCells;

use CraftCms\Cms\Field\TableCells\Number;
use CraftCms\Cms\Field\TableCells\TableCellContext;
use CraftCms\Cms\Ui\Controls\Control;
use CraftCms\Cms\Ui\Controls\Number as NumberControl;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\Nodes\Field;
use GraphQL\Type\Definition\Type;

use function CraftCms\Cms\t;

class Quantity extends Number
{
    public int $minimum = 0;

    public static function displayName(): string
    {
        return t('Quantity', category: 'inventory');
    }

    protected function createControl(TableCellContext $context): Control
    {
        return NumberControl::make($context->path)
            ->min($this->minimum)
            ->step(1);
    }

    public function settingsUi(UiContext $context = new UiContext): ?Ui
    {
        return Ui::make([
            Field::make(
                t('Minimum quantity', category: 'inventory'),
                NumberControl::make('minimum')
                    ->min(0)
                    ->step(1)
                    ->value($this->minimum),
            ),
        ]);
    }

    public function getRules(): array
    {
        return ['minimum' => ['integer', 'min:0']];
    }

    public function getValueRules(): array
    {
        return ['nullable', 'integer', "min:{$this->minimum}"];
    }

    public function gqlType(): Type
    {
        return Type::int();
    }
}
```

`createControl()` supplies the editor. The base `formControl()` binds the value through `controlValue()`; you do not need
to call `value()` in every cell type. Use `$context->path` rather than constructing an input name. The context also
provides the current value and an optional locale for translated options or labels.

Public properties declared on a concrete cell class become settings attributes. `settingsUi()` provides their editing
controls in the column's Configure panel. Return `null` when the type has no settings. `getRules()` validates component
settings; `getValueRules()` supplies rules for the cell content. Browser constraints such as `min()` and `step()` do not
replace content validation.

Register the class during your plugin service provider's boot:

```php
use Acme\Inventory\TableCells\Quantity;
use CraftCms\Cms\Field\TableCellTypes;

public function boot(TableCellTypes $cellTypes): void
{
    $cellTypes->register(Quantity::class);
}
```

The selector uses `displayName()`. Built-ins retain their short identities, while plugin cell types use their fully
qualified class name. Keep that class identity stable across releases, or migrate existing column configuration when
renaming it. A type returning `false` from `isSelectable()` is excluded from new column selections.

For programmatic Table field configuration, the custom column can be written as:

```php
use Acme\Inventory\TableCells\Quantity;

$columns = [
    'col1' => [
        'heading' => 'Quantity',
        'handle' => 'quantity',
        'type' => Quantity::class,
        'settings' => ['minimum' => 1],
    ],
];
```

Cell settings can also be stored directly on the column, such as `'minimum' => 1`. A top-level setting takes precedence
over the corresponding value in `settings`. Only attributes declared by the component's `settingsAttributes()` are
applied. Registration is required before Craft resolves the field's columns or their settings Forms.

## Value lifecycle

| Method | Responsibility |
| --- | --- |
| `normalizeValue($value, $fromRequest)` | Converts stored or submitted data into the PHP value used by the field. Use `$fromRequest` when those input formats differ. |
| `serializeValue($value, $forDb)` | Returns a boolean, number, string, or `null`. `$forDb` is `true` for database storage. |
| `controlValue($context)` | Adapts the normalized value for the editing control. The default calls `serializeValue()`. |
| `getValueRules()` | Returns Laravel validation rules for the serialized cell value. The Table field runs validation and attaches messages to the row and column. |
| `searchKeywords($value)` | Returns searchable text. The default converts the serialized value to a string. |
| `gqlType()` / `gqlInputType()` | Describes GraphQL output and mutation input. The base type is String; the default input type uses `gqlType()`. |

PHP values may be richer than their stored representation. Date cells, for example, normalize to date objects, serialize
to strings, and override `controlValue()` for the editor's date format. A plugin's serialized cell value must remain a
scalar or `null`; arrays and objects cannot be persisted as a single cell through this contract.

Rules validate the serialized value, not an arbitrary normalized object. Align rules with that representation, and keep
normalization and serialization compatible so stored values can be read back without changing their meaning. If you
override `serializeValue()` to transform units or formats, review the editor value, validation, search, and GraphQL
representation together.

## Custom editors and settings tables

Reusing an existing Form control requires only PHP registration of the cell type. If it needs a new editor, return a
custom Form control and follow the [custom Node and Control registration](forms.md#custom-nodes-and-controls) guide.
That includes registering the PHP control type and its Vue component, loading plugin assets before the Form mounts,
and preserving editable, read-only, disabled, validation, and accessibility behavior.

A reusable Form Table accepts controls directly:

```php
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\Nodes\Field;

$form = Ui::make([
    Field::make('Quantities', Table::make('quantities')
        ->columns([
            'quantity' => [
                'heading' => 'Quantity',
                'control' => Number::make('quantity')->min(0)->step(1),
            ],
        ])
        ->defaultValues(['quantity' => 0])
        ->allowAdd()
        ->allowDelete()
        ->allowReorder()),
]);
```

For this API, a column's `control` is rebound to its cell path. Its supported shorthand `type` values are resolved by
`TableColumn`; registering a class in `TableCellTypes` does not add a shorthand to that resolver. Supply a control
directly for custom editors. The settings Form's host owns normalization, validation, and saving; it does not run the
Table content field's cell lifecycle automatically.

## Unavailable plugins

If a configured cell type is unregistered or cannot be constructed, Craft uses `MissingTableCell` and makes the Table
field editor read-only. Existing column settings and raw content are preserved. Request normalization keeps the
element's existing field value rather than accepting replacement cell data while the provider is missing.

New column settings cannot select an unavailable type. Existing unavailable configuration can be retained unchanged,
and re-registering the provider restores its normalization and editor. Do not replace missing plugin types with text
cells automatically, as that can change the meaning of stored values.
