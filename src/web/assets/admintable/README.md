# Vue Admin Tables

The following notes describe how to get Vue based admin tables running in the Craft CP.

## Asset Bundle

Register the asset bundle in the controller/plugin/module etc

```php
$this->getView()->registerAssetBundle(AdminTableAsset::class);
```

## Markup

The markup starts simple:

```twig
<div id="admin-table"></div>
```

Options are passed to the admin table via data attributes.

| Name               | Type         | Description                                                  |
| ------------------ | ------------ | ------------------------------------------------------------ |
| endpoint           | String       | an endpoint to retrieve the data and pagination that is required by the admin table |
| checkbox           | Bool         | whether or not to show checkboxes in the table               |
| reorder            | Bool         | whether or not to allow re-ordering                          |
| reorder-action-url | String       | the endpoint to post to when a re-order happens              |
| per-page           | Int          | number of rows per page                                      |
| columns            | Array (json) | heading columns for the table (see example)                  |
| action-buttons     | Array (json) | an array of objects to determine the action buttons for the table |
| delete-action-url  | String       | endpoint to retrieve delete post                             |

Below is a full dummy example

```twig
{% set actions = [
    {
      label: 'Set Status'|t,
      actions: [
        {
          label: 'Enabled'|t,
          action: 'commerce/discounts/update-statuses',
          param: 'status',
          value: 'enabled',
          status: 'enabled'
        },
        {
          label: 'Disabled'|t,
          action: 'commerce/discounts/update-statuses',
          param: 'status',
          value: 'disabled',
          status: 'disabled'
        }
			]
		},
		{
      label: '',
      icon: 'settings',
      actions: [
        {
          label: 'Move up'|t,
          action: 'commerce/discounts/update-sort-order',
          param: 'move',
          value: 'up',
          ajax: true
        },
				{
          label: 'Move down'|t,
          action: 'commerce/discounts/update-sort-order',
          param: 'move',
          value: 'down',
          ajax: true
        },
        {
          label: 'Move To Top'|t,
          action: 'commerce/discounts/update-sort-order',
          param: 'move',
          value: 'top',
          ajax: true
        },
        {
          label: 'Move To Bottom'|t,
          action: 'commerce/discounts/update-sort-order',
          param: 'move',
          value: 'bottom',
          ajax: true
        }
      ]
    }
] %}

<div id="admin-table"
		data-endpoint="commerce/discounts/get-admin-table"
		data-checkboxes="true"
		data-reorder="true"
		data-reorder-action-url="commerce/discounts/update-sort-order"
    data-per-page="3"
    data-columns='{{ [
      {
        name: 'name',
        title: 'Name'|t('commmerce'),
        dataClass: 'cell-bold'
      },
      {
        name: 'code',
        title: 'Code'|t('commerce')
      },
      {
        name: 'duration',
        title: 'Duration'|t('commerce')
      },
      {
        name: 'totalUses',
        title: 'Times Used'|t('commerce')
      },
      {
        name: 'stopProcessing',
        title: 'Stop Processing?'|t('commerce')
      }
    ]|json_encode|raw }}'
    data-action-buttons='{{ actions|json_encode|raw }}'
    data-delete-action-url="commerce/discounts/delete"></div>
```

## Columns spec

Columns are provided as an array of objects. Each object consists of the following:

| Name                  | Description                                                  |
| --------------------- | ------------------------------------------------------------ |
| name                  | the handle of the column, used to match to the data in the rows |
| title                 | display title for the column                                 |
| titleClass (optional) | class added to the cell in the header row                    |
| dataClass (optional)  | class added to the cell in the data row                      |

## Action buttons spec

Action buttons are provided as an array of objects.

| Name            | Description                                                  |
| --------------- | ------------------------------------------------------------ |
| label           | title to show in the top level button                        |
| icon (optional) | icon to show in the top level button                         |
| actions         | array of actions for use in the dropdown when the button is clicked (spec below) |

###Actions spec

| Name   | Type   | Description                                                  |
| ------ | ------ | ------------------------------------------------------------ |
| label  | String | title to show                                                |
| action | String | action uri to post data to                                   |
| param  | String | name of the post data parameter                              |
| value  | String | value of the post data, used with param to post as a key pair |
| Ajax   | Bool   | whether this action should be posted via ajax                |

## Endpoint

Below is an example response from a controller required by the admin table

```php
return $this->asJson([
  'links' => [
    'pagination' => [
      'total' => (int)$total,
      'per_page' => (int)$limit,
      'current_page' => (int)$page,
      'last_page' => (int)$lastPage,
      'next_page_url' => $nextPageUrl,
      'prev_page_url' => $prevPageUrl,
      'from' => (int)$from,
      'to' => (int)$to,
    ]
  ],
  'data' => $rows
]);
```

**Data** is an array of data that holds of associative arrays that is used to build the rows of the admin table. In each data row the associative key is used to map the data to the correct column specified in columns architecture.

