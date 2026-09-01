<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, class-string> */
    private array $map = [
        'craft\fields\Addresses' => Addresses::class,
        'craft\fields\Assets' => Assets::class,
        'craft\fields\ButtonGroup' => ButtonGroup::class,
        'craft\fields\Checkboxes' => Checkboxes::class,
        'craft\fields\Color' => Color::class,
        'craft\fields\ContentBlock' => ContentBlock::class,
        'craft\fields\Country' => Country::class,
        'craft\fields\Date' => Date::class,
        'craft\fields\Dropdown' => Dropdown::class,
        'craft\fields\Email' => Email::class,
        'craft\fields\Entries' => Entries::class,
        'craft\fields\Icon' => Icon::class,
        'craft\fields\Json' => Json::class,
        'craft\fields\Lightswitch' => Lightswitch::class,
        'craft\fields\Link' => Link::class,
        'craft\fields\Matrix' => Matrix::class,
        'craft\fields\MissingField' => MissingField::class,
        'craft\fields\Money' => Money::class,
        'craft\fields\MultiSelect' => MultiSelect::class,
        'craft\fields\Number' => Number::class,
        'craft\fields\PlainText' => PlainText::class,
        'craft\fields\RadioButtons' => RadioButtons::class,
        'craft\fields\Range' => Range::class,
        'craft\fields\Table' => CraftCms\Cms\Field\Table::class,
        'craft\fields\Time' => Time::class,
        'craft\fields\Users' => Users::class,
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

        try {
            $fieldConfigs = $projectConfig->find(fn (array $item) => (
                isset($item['type']) &&
                isset($this->map[$item['type']])
            ));

            foreach ($fieldConfigs as $path => $config) {
                $projectConfig->set("$path.type", $this->map[$config['type']]);
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }
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
