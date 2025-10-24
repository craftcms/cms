<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $map = [
        \craft\fields\Addresses::class => \CraftCms\Cms\Field\Addresses::class,
        \craft\fields\Assets::class => \CraftCms\Cms\Field\Assets::class,
        \craft\fields\ButtonGroup::class => \CraftCms\Cms\Field\ButtonGroup::class,
        \craft\fields\Categories::class => \CraftCms\Cms\Field\Categories::class,
        \craft\fields\Checkboxes::class => \CraftCms\Cms\Field\Checkboxes::class,
        \craft\fields\Color::class => \CraftCms\Cms\Field\Color::class,
        \craft\fields\ContentBlock::class => \CraftCms\Cms\Field\ContentBlock::class,
        \craft\fields\Country::class => \CraftCms\Cms\Field\Country::class,
        \craft\fields\Date::class => \CraftCms\Cms\Field\Date::class,
        \craft\fields\Dropdown::class => \CraftCms\Cms\Field\Dropdown::class,
        \craft\fields\Email::class => \CraftCms\Cms\Field\Email::class,
        \craft\fields\Entries::class => \CraftCms\Cms\Field\Entries::class,
        \craft\fields\Icon::class => \CraftCms\Cms\Field\Icon::class,
        \craft\fields\Json::class => \CraftCms\Cms\Field\Json::class,
        \craft\fields\Lightswitch::class => \CraftCms\Cms\Field\Lightswitch::class,
        \craft\fields\Link::class => \CraftCms\Cms\Field\Link::class,
        \craft\fields\Matrix::class => \CraftCms\Cms\Field\Matrix::class,
        \craft\fields\MissingField::class => \CraftCms\Cms\Field\MissingField::class,
        \craft\fields\Money::class => \CraftCms\Cms\Field\Money::class,
        \craft\fields\MultiSelect::class => \CraftCms\Cms\Field\MultiSelect::class,
        \craft\fields\Number::class => \CraftCms\Cms\Field\Number::class,
        \craft\fields\PlainText::class => \CraftCms\Cms\Field\PlainText::class,
        \craft\fields\RadioButtons::class => \CraftCms\Cms\Field\RadioButtons::class,
        \craft\fields\Range::class => \CraftCms\Cms\Field\Range::class,
        \craft\fields\Table::class => \CraftCms\Cms\Field\Table::class,
        \craft\fields\Tags::class => \CraftCms\Cms\Field\Tags::class,
        \craft\fields\Time::class => \CraftCms\Cms\Field\Time::class,
        \craft\fields\Users::class => \CraftCms\Cms\Field\Users::class,
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
