<?php

declare(strict_types=1);

use craft\fields\Addresses;
use craft\fields\Assets;
use craft\fields\ButtonGroup;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\ContentBlock;
use craft\fields\Country;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Entries;
use craft\fields\Icon;
use craft\fields\Json;
use craft\fields\Lightswitch;
use craft\fields\Link;
use craft\fields\Matrix;
use craft\fields\MissingField;
use craft\fields\Money;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Range;
use craft\fields\Time;
use craft\fields\Users;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $map = [
        Addresses::class => CraftCms\Cms\Field\Addresses::class,
        Assets::class => CraftCms\Cms\Field\Assets::class,
        ButtonGroup::class => CraftCms\Cms\Field\ButtonGroup::class,
        Checkboxes::class => CraftCms\Cms\Field\Checkboxes::class,
        Color::class => CraftCms\Cms\Field\Color::class,
        ContentBlock::class => CraftCms\Cms\Field\ContentBlock::class,
        Country::class => CraftCms\Cms\Field\Country::class,
        Date::class => CraftCms\Cms\Field\Date::class,
        Dropdown::class => CraftCms\Cms\Field\Dropdown::class,
        Email::class => CraftCms\Cms\Field\Email::class,
        Entries::class => CraftCms\Cms\Field\Entries::class,
        Icon::class => CraftCms\Cms\Field\Icon::class,
        Json::class => CraftCms\Cms\Field\Json::class,
        Lightswitch::class => CraftCms\Cms\Field\Lightswitch::class,
        Link::class => CraftCms\Cms\Field\Link::class,
        Matrix::class => CraftCms\Cms\Field\Matrix::class,
        MissingField::class => CraftCms\Cms\Field\MissingField::class,
        Money::class => CraftCms\Cms\Field\Money::class,
        MultiSelect::class => CraftCms\Cms\Field\MultiSelect::class,
        Number::class => CraftCms\Cms\Field\Number::class,
        PlainText::class => CraftCms\Cms\Field\PlainText::class,
        RadioButtons::class => CraftCms\Cms\Field\RadioButtons::class,
        Range::class => CraftCms\Cms\Field\Range::class,
        craft\fields\Table::class => CraftCms\Cms\Field\Table::class,
        Time::class => CraftCms\Cms\Field\Time::class,
        Users::class => CraftCms\Cms\Field\Users::class,
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table(Table::FIELDS)
                ->where('type', $old)
                ->update(['type' => $new]);
        }

        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        $fieldConfigs = $projectConfig->find(fn (array $item) => (
            isset($item['type']) &&
            isset($this->map[$item['type']])
        ));

        foreach ($fieldConfigs as $path => $config) {
            $projectConfig->set("$path.type", $this->map[$config['type']]);
        }

        $projectConfig->muteEvents = $muteEvents;
    }

    public function down(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table(Table::FIELDS)
                ->where('type', $new)
                ->update(['type' => $old]);
        }
    }
};
