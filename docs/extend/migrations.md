# Migrations

Migrations are PHP classes that make one-time changes to the system.

For the most part, migrations in Craft work similarly to [Yii’s implementation](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations), but unlike Yii, Craft manages three different types of migrations:

- **App migrations** – Craft’s own internal migrations.
- **Plugin migrations** – Each installed plugin has its own migration track.
- **Content migrations** – Your Craft project itself can have migrations, too.

## Creating Migrations

::: tip
If your Craft install is running from a Vagrant box, you will need to SSH into the box to run these commands.
:::

To create a new migration for your plugin or project, open up your terminal and go to your Craft project:

```bash
cd /path/to/project
```

Then run the following command to generate a new migration file for your plugin or project (replacing `<migration_name>` with your migration’s name in snake_case, and `<plugin-handle>` with your plugin handle in kebab-case):

::: code

```bash Plugin Migration
./craft migrate/create <migration_name> --plugin=<plugin-handle>
```

```bash Content Migration
./craft migrate/create <migration_name>
```

:::

Enter `yes` at the prompt, and a new migration file will be created for you. You can find it at the file path output by the command.

If this is a plugin migration, increase your plugin’s [schema version](api:craft\base\PluginTrait::$schemaVersion), so Craft knows to check for new plugin migrations as people update to your new version.

### What Goes Inside

Migration classes contain methods: [safeUp()](api:yii\db\Migration::safeUp()) and [safeDown()](api:yii\db\Migration::safeDown()). `safeUp()` is run when your migration is _applied_, and `safeDown()` is run when your migration is _reverted_.

::: tip
You can usually ignore the `safeDown()` method, as Craft doesn’t have a way to revert migrations from the Control Panel.
:::

You have full access to [Craft’s API](https://docs.craftcms.com/api/v3/) from your `safeUp()` method, but plugin migrations should try to avoid calling the plugin’s own APIs here. As your plugin’s database schema changes over time, so will your API’s assumptions about the schema. If an old migration calls a service method that relies on database changes that haven’t been applied yet, it will result in a SQL error. So in general you should execute all SQL queries directly from your own migration class. It may feel like you’re duplicating code, but it will be more future-proof.

### Manipulating Database Data

Your migration class extends <api:craft\db\Migration>, which provides several methods for working with the database. It’s better to use these than their <api:craft\db\Command> counterparts, because the migration methods are both simpler to use, and they’ll output a status message to the terminal for you.

```php
// Bad:
$this->db->createCommand()
    ->insert('{{%tablename}}', $rows)
    ->execute();

// Good:
$this->insert('{{%tablename}}', $rows);
```  

::: warning
The <api:api:yii\db\Migration::insert()>, [batchInsert()](api:craft\db\Migration::batchInsert()), and [update()](api:yii\db\Migration::update()) migration methods will automatically insert/update data in the `dateCreated`, `dateUpdated`, `uid` table columns in addition to whatever you specified in the `$columns` argument. If the table you’re working with does’t have those columns, make sure you pass `false` to the `$includeAuditColumns` argument so you don’t get a SQL error.
:::

::: tip
<api:craft\db\Migration> doesn’t have a method for _selecting_ data, so you will still need to go through Yii’s [Query Builder](https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder) for that.

```php
use craft\db\Query;

$result = (new Query())
    // ...
    ->all();
```
:::

### Logging

If you want to log any messages in your migration code, echo it out rather than calling [Craft::info()](api:yii\BaseYii::info()):

```php
echo "    > some note\n";
```

If the migration is being run from a console request, this will ensure the message is seen by whoever is executing the migration, as the message will be output into the terminal. If it’s a web request, Craft will capture it and log it to `storage/logs/` just as if you had used `Craft::info()`.

## Executing Migrations

You can have Craft apply your new migration from the terminal:

::: code

```bash Plugin Migration
./craft migrate/up --plugin=<plugin-handle>
```

```bash Content Migration
./craft migrate/up
```

:::

Or you can have Craft apply all new migrations across all migration tracks:

```bash
./craft migrate/all
```

Craft will also check for new plugin migrations on Control Panel requests, for any plugins that have a new [schema version](api:craft\base\PluginTrait::$schemaVersion), and content migrations can be applied from the Control Panel by going to Utilities → Migrations.

## Plugin Install Migrations

Plugins can have a special “Install” migration which handles the installation and uninstallation of the plugin. Install migrations live at `migrations/Install.php` alongside normal migrations. They should follow this template:

```php
<?php
namespace ns\prefix\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        // ...
    }

    public function safeDown()
    {
        // ...
    }
}
```

You can give your plugin an install migration with the `migrate/create` command if you pass the migration name “`install`”:

```bash
./craft migrate/create install --plugin=<plugin-handle>
```

When a plugin has an Install migration, its `safeUp()` method will be called when the plugin is installed, and its `safeDown()` method will be called when the plugin is uninstalled (invoked by the plugin’s [install()](api:craft\base\Plugin::install()) and [uninstall()](api:craft\base\Plugin::uninstall()) methods).

::: tip
It is *not* a plugin’s responsibility to manage its row in the `plugins` database table. Craft takes care of that for you.
:::

## Examples

**Fields**

Values required to build each field type can be found at `craftcms/cms/src/fields`

```php
// Variable to keep track of created ids in case safeDown() is called
private $fieldIds = [];

public function safeUp()
{
    // Query to get the group id
    $groupId = null;
    if ($group = (new \craft\db\Query)
            ->select('id')
            ->from('fieldgroups')
            ->where(['name' => 'Products'])
            ->one()) {
        $groupId = $group['id'];       
    }

    // Add an Entry Field
    try {
    
        // Get the Entry Type for this Entry field
        $sectionId = 0;
        foreach (Craft::$app->getSections()->getSectionByHandle('sectionHandle')->getEntryTypes() as $entryType) {
            if ($entryType->handle == 'entryTypeHandle') {
                $sectionId = $entryType->sectionId;
                break;
            }
        }
        
        // Make sure the field doesn't already exist
        if (!Craft::$app->getFields()->getFieldByHandle('myField')) {
        
            // Build the field
            $field = new \craft\fields\Entries([
                'groupId' => $groupId,
                'name' => 'My Field',
                'handle' => 'myField',
                'limit' => 1,
                'sources' => ['section:' . $sectionId]
            ]);

            // Add the new field
            Craft::$app->getFields()->saveField($field);
            
            // Save the id
            $this->fieldIds[] = $field->id;
        }
        
    } catch (\Throwable $e) {
        echo $e->getMessage();
        return false;
    }
    
    // Add a Numeric Field
    try {
    
        // Make sure the field doesn't already exist
        if (!Craft::$app->getFields()->getFieldByHandle('myNumber')) {
        
            // Build the field
            $field = new \craft\fields\Number([
                'groupId' => $groupId,
                'name' => 'My Number',
                'handle' => 'myNumber',
                'min' => 0,
                'decimals' => 2
            ]);

            // Add the new field
            Craft::$app->getFields()->saveField($field);
            
            // Save the id
            $this->fieldIds[] = $field->id;
        }
    } catch (\Throwable $e) {
        echo $e->getMessage();
        return false;
    }
    
    // Add a Lightswitch field
    try {
        
        // Make sure the field doesn't already exist
        if (!Craft::$app->getFields()->getFieldByHandle('isOn')) {
        
            // Build the field
            $field = new \craft\fields\Lightswitch([
                'groupId' => $groupId,
                'name' => 'Is On',
                'handle' => 'isOn',
                'default' => 'on'
            ]);

            // Add the new field
            Craft::$app->getFields()->saveField($field);
            
            // Save the id
            $this->fieldIds[] = $field->id;
        }
    } catch (\Throwable $e) {
        echo $e->getMessage();
        return false;
    }
    
    return true;
}

public function safeDown()
{
    $return = true;

    foreach ($fieldIds as $fieldId) {
        try {
            Craft::$app->getFields()->deleteFieldById($fieldId);
        } catch (\Throwable $e) {
            echo $e->getMessage();
            $return = $false;
        }
    }
    
    return $return;
}
```

**Sections and Entry Types**

```php
// Variables to keep track of created ids in case safeDown() is called
private $sectionIds = [];
private $entryTypeIds = [];

public function safeUp()
{
    // Fetch or add a section
    try {
            
        // Check to see if section already exists
        if (!$section = Craft::$app->getSections()->getSectionByHandle('mySection')) {
        
            $siteSettings = new \craft\models\Section_SiteSettings([
                'siteId' => Craft::$app->getSites()->currentSite->id,
                'hasUrls' => false
            ]);
            
            // Build section
            $section = new \craft\models\Section([
                'name' => 'My Section',
                'handle' => 'mySection',
                'type' => 'channel'
            ]);
            
            // Assign settings to new section
            $section->setSiteSettings([$siteSettings]);
            
            // Save the section
            Craft::$app->getSections()->saveSection($section);
            
            // Save the id
            $this->sectionIds[] = $section->id;
        }
    } catch (\Throwable $e) {
        echo $e->getMessage();
        return false;
    }
    
    // Crete Entry Type
    try {
        if (!Craft::$app->getSections()->getEntryTypesByHandle('myEntryType')) {
            
            // Build Entry Type
            $entryType = new \craft\models\EntryType([
                'name' => 'My Entry Typw',
                'handle' => 'myEntryType',
                'hasTitleField' => true,
                'titleLabel' => 'Title',
                'sectionId' => $section->id
            ]);
    
            // Save Entry Type
            Craft::$app->getSections()->saveEntryType($retailerProducts);
            
            // Save the id
            $this->entryTypeIds[] = $entryType->id;
        }
    } catch (\Throwable $e) {
        echo $e->getMessage();
        return false;
    }
    
    return true;
}

public function safeDown()
{
    $return = true;

    foreach ($this->entryTypeIds as $entryTypeId) {
        try {
            Craft::$app->getSections()->deleteEntryTypeById($entryTypeId);
        } catch (\Throwable $e) {
            echo $e->getMessage();
            $return = false;
        }
    }

    foreach ($this->sectionIds as $sectionId) {
        try {
            Craft::$app->getSections()->deleteSectionById($sectionId);
        } catch (\Throwable $e) {
            echo $e->getMessage();
            $return = false;
        }
    }
    
    return $return;
}
```

**Add Fields to Entry Type**

```php
public function safeUp()
{
    try {
    
        // Find the entry type if it exists
        foreach (Craft::$app->getSections()->getSectionByHandle('sectionHandle')->getEntryTypes() as $entryType) {
            if ($entryType->handle == 'entryTypeHandle') {
                
                // Define fields to be added to entry type (they must already all exist by this point)
                $fields = [
                    [ 'handle' => 'myField', 'required' => true ],
                    [ 'handle' => 'myNumber', 'required' => true ],
                    [ 'handle' => 'isOn', 'required' => true ],
                ];
    
                // Build the Field Records array
                $fieldRecords = [];
                
                foreach ($fields as $index => $field) {
    
                    /** @var \craft\fields\Entries $f */
                    if ($f = Craft::$app->getFields()->getFieldByHandle($field['handle'])) {
                        $fieldRecord = new \craft\records\FieldLayoutField([
                            'id' => $f->id,
                            'required' => $field['required'],
                            'sortOrder' => $index
                        ]);
                        $fieldRecords[] = $fieldRecord;
                    }
                }
                
                // Create a new tab
                $fieldLayoutTab = new \craft\models\FieldLayoutTab([
                    'name' => 'My Tab',
                    'sortOrder' => 1,
                    'layoutId' => $entryType->getFieldLayoutId()
                ]);

                // Add fields to tab
                $fieldLayoutTab->setFields($fieldRecords);

                // Create a new Field Layout
                $fieldLayout = new \craft\models\FieldLayout();
                $fieldLayout->type = \craft\elements\Entry::class;
                $fieldLayout->setTabs([$fieldLayoutTab]);
                $fieldLayout->id = $entryType->getFieldLayoutId();

                // Save Layout
                Craft::$app->getFields()->saveLayout($fieldLayout);

                // Save Entry Type
                Craft::$app->getSections()->saveEntryType($entryType);
                
                // There may be many Entry Types in one section, so break after you get the one you need
                break;
            }
        }
        
    } catch (\Throwable $e) {
        echo $e->getMessage();
        return false;
    }
    
    return true;
}

public function safeDown()
{
    // In this scenario, the Entry Type and Layout already exist so there is really nothing to roll back
    return true;
}

```