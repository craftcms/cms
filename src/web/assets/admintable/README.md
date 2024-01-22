# Vue Admin Tables

The following notes describe how to get Vue-based admin tables running in the Craft control panel.

## Asset Bundle

Register the asset bundle in the controller/plugin/module etc.

```php
$this->getView()->registerAssetBundle(AdminTableAsset::class);
```

Alternatively you can register the asset in the template.

```twig
{% do view.registerAssetBundle('craft\\web\\assets\\admintable\\AdminTableAsset') -%}
```

## Markup

The only markup that is required is an element to mount the table on.

```html
<div id="example-admin-table"></div>
```

## Javascript

The table is initialised via JavaScript.

```js
new Craft.VueAdminTable({...options...});
```

### Options

| Name                      | Type     | Default                                      | Description                                                                                                                                              |
|---------------------------|----------|----------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| actions                   | Array    | `[]`                                         | Array of action options to create action buttons in the table toolbar.                                                                                   |
| allowMultipleSelections   | Bool     | true                                         | When using checkboxes, determines whether or not multiple selections are allowed. When set to `false` the select all checkbox is hidden.                 |
| allowMultipleDeletions    | Bool     | true                                         | When using checkboxes, determines whether or not multiple deletions are allowed.                                                                         |
| beforeDelete              | Function | `() => { return new Promise.resolve(true) }` | Function that returns a promise which resolves to a `boolean`. This will determine if the delete process continues.                                      |
| buttons                   | Array    | `[]`                                         | Array of buttons to be placed in the toolbar.                                                                                                            |
| checkboxes                | Bool     | `false`                                      | Whether to show the checkbox column or not.                                                                                                              |
| checkboxStatus            | Function | `true`                                       | Callback function to determine if the row's checkbox should be disabled. [See example below](#checkboxstatus-example)                                    |
| columns                   | Array    | `[]`                                         | Used to define the table columns. See column definition.                                                                                                 |
| container                 | String   | `null`                                       | CSS selector for which element the table should mounted on.                                                                                              |
| deleteAction              | String   | `null`                                       | The action URL used to post to for deleting an item. Enables the delete buttons when not `null`.                                                         |
| deleteCallback            | Function | `null`                                       | Callback function after the delete action has taken place. The ID of the deleted row is passed as an argument                                            |
| deleteConfirmationMessage | String   | `Are you sure you want to delete “{name}”?`  | Message to be displayed in the confirmation message pop up.                                                                                              |
| deleteFailMessage         | String   | ` Couldn’t delete “{name}”.`                 | Message to be displayed as the fail error after a delete failure.                                                                                        |
| deleteSuccessMessage      | String   | `“{name}” deleted.`                          | Message to be displayed as the success notice after successful deletion.                                                                                 |
| emptyMessage              | String   | `No data available.`                         | Message to be displayed when there are no rows in the table data.                                                                                        |
| footerActions             | Array    | `[]`                                         | Array of action options to create action buttons in the table footer.                                                                                    |
| fullPage                  | Bool     | `false`                                      | Set to this to true when the table is the only element on the template. Sets the correct styling classes.                                                |
| fullPane                  | Bool     | `true`                                       | Set this to false when the table isn’t the only UI component in its content pane.                                                                        |
| minItems                  | Int      | `null`                                       | The minimum number of items allowed in the table.                                                                                                        |
| moveToPageAction          | String   | `null`                                       | The action URL used to post to for moving and item to another page. Action item displays when this option is provided.                                   |
| padded                    | Bool     | `false`                                      | Set this to true to add padding around the table.                                                                                                        |
| paginatedReorderAction    | String   | `null`                                       | The action URL used to post to for reordering items when using paginated table data. Reorder draggable handles are display when this option is provided. |
| perPage                   | Int      | `null`                                       | Used with `tableDataEndpoint` to define the number of rows to show per page.                                                                             |
| reorderAction             | String   | `null`                                       | The action URL used to post to for reordering items. Reorder draggable handles are display when this option is provided.                                 |
| reorderSuccessMessage     | String   | `Items reordered`                            | Message to be displayed as the success notice after successful reorder.                                                                                  |
| reorderFailMessage        | String   | `Couldn’t reorder items`                     | Message to be displayed as the fail notice after reorder failure.                                                                                        |
| search                    | Bool     | `false`                                      | Whether or not to show the search field.                                                                                                                 |
| searchPlaceholder         | String   | `Search`                                     | Search placeholder text.                                                                                                                                 |
| tableData                 | Array    | `null`                                       | Array of objects used to populate the table data for data mode.                                                                                          |
| tableDataEndpoint         | String   | `null`                                       | Endpoint for api mode to retrieve table data, pagination and table metadata (e.g. total count).                                                          |

#### `checkboxStatus` example

Below is a simple example of how to use the `checkboxStatus` callback, if you have a `boolean` piece of data each row to determine the status.

Although if you require further logic (calling other data etc) this is also the place it will live.

```js
new Craft.VueAdminTable({
    // ... 
    checkboxStatus: function(row) {
        return row.isCheckboxEnabled
    }
    // ...
});
```

### Properties

| Name          |                                                                                             |
|---------------|---------------------------------------------------------------------------------------------|
| instance      | The Vue instance (table is wrapped in a skeleton Vue app).                                  |
| $table        | The instance of the VueAdminTable component. Gives direct access to properties and methods. |

### Methods

| Name     |                        |
|----------|------------------------|
| reload() | Reload the table data. |

#### `reload()` example

```js
const adminTable = new Craft.VueAdminTable({
    // ...
});

// Reload table every 15 seconds
setInterval(function() {
    adminTable.reload();
}, 15000);
```

### Events

#### JS Events

| Name                | Data               | Return | Scenario                                                                                                                             |
|---------------------|--------------------|--------|--------------------------------------------------------------------------------------------------------------------------------------|
| onSelect            | Array of IDs       |        | When a checkbox or select all is selected or de-selected.                                                                            |
| onData              | Array of objects   |        | On successful load or page change.                                                                                                   |
| onCellClicked       | data, field, event |        | On click of a table cell.                                                                                                            |
| onCellDoubleClicked | data, field, event |        | On double click of a table cell.                                                                                                     |
| onRowClicked        | data, event        |        | On click of a table row.                                                                                                             |
| onRowDoubleClicked  | data, event        |        | On double click of a table row.                                                                                                     |
| onLoaded            | -                  |        | When the table has loaded (regardless of data loading).                                                                              |
| onLoading           | -                  |        | When the table is in a loading state.                                                                                                |
| onPagination        | Object             |        | When pagination has loaded (also occurs on first load). Object contains pagination information (e.g. current page, total pages etc). |
| onQueryParams       | Object             | Object | Called when the query parameters are being generated for the table data enpoint.                                                     |

Example usage:

```js
new Craft.VueAdminTable({
  // ...
  onLoaded: function() { console.log('LOADED!'); },
  onData: function(data) { console.log('Data:', data); },
  onQueryParams: function(params) { 
    console.log('Query Params:', params); 
    
    params.foo = 'bar';
    return params; 
  },
  onCellClicked: function(data, field, event) { console.log('Cell Clicked:', data, field, event); },
  onCellDoubleClicked: function(data, field, event) { console.log('Cell Double Clicked:', data, field, event); },
  onRowClicked: function(data, event) { console.log('Row Clicked:', data, event); },
  onRowDoubleClicked: function(data, event) { console.log('Row Double Clicked:', data, event); },
  // ...
});
```

#### Vue Events

| Name     | Data             | Scenario                                                  |
| -------- | ---------------- | --------------------------------------------------------- |
| onSelect | Array of IDs     | When a checkbox or select all is selected or de-selected. |
| data     | Array of objects | On successful load or page change.                        |

## Table Data

The data for the table can be provided in two different ways.

### Data Mode

This is where the data is passed directly to the component within the javascript options as an array of options.

There are some feature that are **not available** when using data mode, these are list below:

- Column Sorting
- Pagination

 ### API Mode

This mode uses the `tableDataEndpoint` option to pull data into the table. The component expects this data in a specific format.

Below is an example response from a controller required by the admin table

```php
return $this->asJson([
    'pagination' => [
      'total' => (int)$total,
      'per_page' => (int)$limit,
      'current_page' => (int)$page,
      'last_page' => (int)$lastPage,
      'next_page_url' => $nextPageUrl,
      'prev_page_url' => $prevPageUrl,
      'from' => (int)$from,
      'to' => (int)$to,
    ],
    'data' => $rows
]);
```

**Data** is an array that holds of associative arrays that are used to build the rows of the admin table. In each data row the associative key is used to map the data to the correct column specified in columns architecture.

## Columns

Columns are provided as an array of objects in the component options. Each object consists of the following:

| Name                  | Description                                                  |
| --------------------- | ------------------------------------------------------------ |
| name                  | Handle of the column, used to match to the data in the rows. See more information about special columns. |
| title                 | Display title for the column                                 |
| titleClass (optional) | Class added to the cell in the header row                    |
| dataClass (optional)  | Class added to the cell in the data row                      |
| callback              | Callback function allowing the manipulation of the output of the data in the cell. See column callback. |
| sortField (optional)  | Field name on which the sorting takes place. This is only available when using the `tableDataEndpoint`. Data is passed to the endpoint as `{field}|{direction}` e.g. `email|asc` |

### Special Columns

There are a few special column type that provide extra functionality. To use any of the special columns they need to be specified in the columns array with a `__slot:` prefix.

#### Title

The title column allows the use of `status`, `title` and `url` in the data to create a column similar to that of the title column in element index tables.

```javascript
var data = [
  {
    title: 'My First Item',
    status: true,
    url: '/my-first-item',
  },
  {
    title: 'My Second Item',
    status: false,
    url: '/my-second-item',
  }
];

var columns = [
  { name: '__slot:title', title: Craft.t('app', 'Title') },
];

new Craft.VueAdminTable({
  columns: columns,
  tableData: data
});
```

#### Handle

The handle column is used for displaying handle for items these are wrapped in `code` tags.

#### Menu

The menu column allows the output of a dropdown link menu within the column cell.

#### Detail

The detail column shows a clickable attribute to allow the toggling of a detail row. This is a row that shows underneath its "parent" row, giving the ability to show more information.

The table below explains all available attributes for data in the detail column.

| Attribute               | Description                                                                                                                                  |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `handle` (optional)     | The HTML for what will be clicked to show the detail row. If omitted an "info" icon will be displayed.                                       |
| `title` (optional)      | Content for the title attribute on the clickable show/hide toggle.                                                                           |
| `content`               | HTML to be displayed in the detail row. If using the `showAsList` option this can be an array that will be converted to a key -> value list. |
| `showAsList` (optional) | Default: `false`. When set to `true` and `content` is an array of data a key -> value list will be shown in the detail row.                  |

#### Special Column Examples

```javascript
var data = [
  {
    id: 1,
    title: 'My First Item',
    status: true,
    url: '/my-first-item',
    handle: 'myFirstItem',
    menu: {
      showItems: false,
      menuBtnTitle: Craft.t('site', 'Edit Settings'),
      label: Craft.t('site', 'Edit Settings'),
      url: '/settings/1',
      items: null
    },
    detail: {
      handle: '<span data-icon="info"></span>',
      content: '<p>Extra information to show under the main row</p>'
    }
  },
  {
    id: 2,
    title: 'My Second Item',
    status: false,
    url: '/my-second-item',
    handle: 'mySecondItem',
    detail: {
      title: Craft.t('site', 'Further Information'),
      content: { list: 'of', things: ['to', 'show'] },
      showAsList: true    
    },
    menu: {
      showItems: true,
      showCount: true,
      menuBtnTitle: Craft.t('site', 'Edit Sub Options'),
      label: Craft.t('site', 'Edit Sub Options'),
      url: '/settings/subOptions',
      items: [
        {
          label: 'First Item of Second',
          url: '/my-second-item/first-item'
        },
        {
          label: 'Second Item of Second',
          url: '/my-second-item/second-item'
        }
      ]
    }
  }
];

var columns = [
  { name: '__slot:title', title: Craft.t('app', 'Title') },
  { name: '__slot:handle', title: Craft.t('app', 'Handle') },
  { name: '__slot:menu', title: Craft.t('site', 'Sub Options') },
  { name: '__slot:detail', title: '' }
];

new Craft.VueAdminTable({
  columns: columns,
  tableData: data
});
```

### Column Callback

A column callback function can be specified to give you the opportunity to customise the data output when the cell is render. As an example this is a great way to be able insert some custom html. 

The callback function receives the value of the corresponding attribute in the table data.

To do this the function needs to be passed to the `callback` option on the column definition.

```javascript
var data = [
  {
    myColumn: true
  },
  {
    myColumn: false
  }  
];

var columns = [
  { 
    name: 'myColumn', 
    title: Craft.t('site', 'My Column'),
    callback: function(value) {
      if (value) {
        return '<span data-icon="check" title="'+Craft.t('app', 'Yes')+'"></span>';
      }
      
      return '';
    }
  }
];

new Craft.VueAdminTable({
  columns: columns,
  tableData: data
});
```

## Action buttons

Action buttons can be used in conjunction with checkboxes to perform singular or bulk actions e.g. bulk enabling/disabling a set of records.

Action buttons are provided as an array of objects and come in two varieties, a menu button which is a dropdown style button with sub actions or a single action button.

### Menu action button options

| Name            | Description                                                  |
| --------------- | ------------------------------------------------------------ |
| label           | title to show in the top level button                        |
| icon (optional) | icon to show in the top level button                         |
| actions         | array of actions for use in the dropdown when the button is clicked (spec below) |

####Sub action buttons

| Name          | Type   | Description                                                  |
| ------------- | ------ | ------------------------------------------------------------ |
| label         | String | title to show                                                |
| action        | String | action uri to post data to                                   |
| param         | String | name of the post data parameter                              |
| value         | String | value of the post data, used with param to post as a key pair |
| ajax          | Bool   | whether this action should be posted via ajax                |
| status        | string | status icon to pass to the button                            |
| allowMultiple | Bool   | whether or not to allow the action to be run if multiple items are selected |
| separator     | Bool   | optionally add a separating line above the button (not available on single action buttons) |

### Single action button options

Single action buttons have all the same options as **Sub action buttons** *except separator* (see above).

### Example

```js
var actions = [
    {
      	// Menu button example
        label: Craft.t('app', 'Set Status'),
        actions: [
            {
                label: Craft.t('app', 'Enabled'),
                action: 'controller/update-status',
                param: 'status',
                value: 'enabled',
                status: 'enabled'
            },
            {
                label: Craft.t('app', 'Disabled'),
                action: 'controller/update-status',
                param: 'status',
                value: 'disabled',
                status: 'disabled'
            },
            {
                label: Craft.t('app', 'Refresh'),
                action: 'controller/refresh',
                param: 'refresh',
                value: 'all',
                allowMultiple: false,
              	separator: true
            }
        ]
    },
  	{
        label: Craft.t('app', 'Delete'),
        action: 'controller/delete',
    }
];

new Craft.VueAdminTable({
  ...
  actions: actions,
  ...
});
```

## Buttons

The buttons are simple button links that can appear in the top right of the table toolbar. As an example these are useful if you would like to link to the creation of a "New record" for the table.

### Options

The `buttons` option is an array of objects.

Each object has the following parameters. __References to "button" is only from a visual standpoint, buttons are anchor elements.__

| Name | Description |
| ---- | ----------- |
| `label` | The link label |
| `icon` _(optional)_ | The link icon |
| `href` | The link’s `href` attribute |
| `enabled` | Whether the link should be enabled. This can either be a boolean or a callback function that returns a boolean. |


### Example

```js
new Craft.VueAdminTable({
    // ...
    buttons: [
        {
            label: 'Create New Thing',
            icon: 'plus',
            href: '{{ cpUrl("my-plugin/thing/new") }}',
            enabled: () => true,
        }
    ],
});
```

## Before Delete

The before delete hook allows greater control over whether the delete process continues. It will allow you to hook into the process using a function returning a `Promise`.

The `id` param is passed to the before delete method.

### Example

In the example below, after 1.5 seconds the promise will resolve with a `boolean` based on if the `id` is greater than `99`. 

```js
new Craft.VueAdminTable({
    // ...
    beforeDelete: id => {
        return new Promise(function (resolve) {
            setTimeout(() => {
                resolve(id > 99);
            }, 1500);
        });
    },
    // ...
});
```
