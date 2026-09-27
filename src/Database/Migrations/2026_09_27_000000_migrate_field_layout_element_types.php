<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\FieldLayout\LayoutElements;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, class-string> */
    private array $map = [
        'craft\fieldlayoutelements\CustomField' => LayoutElements\CustomField::class,
        'craft\fieldlayoutelements\FullNameField' => LayoutElements\FullNameField::class,
        'craft\fieldlayoutelements\Heading' => LayoutElements\Heading::class,
        'craft\fieldlayoutelements\HorizontalRule' => LayoutElements\HorizontalRule::class,
        'craft\fieldlayoutelements\LineBreak' => LayoutElements\LineBreak::class,
        'craft\fieldlayoutelements\Markdown' => LayoutElements\Markdown::class,
        'craft\fieldlayoutelements\TextareaField' => LayoutElements\TextareaField::class,
        'craft\fieldlayoutelements\TextField' => LayoutElements\TextField::class,
        'craft\fieldlayoutelements\Tip' => LayoutElements\Tip::class,
        'craft\fieldlayoutelements\TitleField' => LayoutElements\TitleField::class,
        'craft\fieldlayoutelements\addresses\AddressField' => LayoutElements\Addresses\AddressField::class,
        'craft\fieldlayoutelements\addresses\CountryCodeField' => LayoutElements\Addresses\CountryCodeField::class,
        'craft\fieldlayoutelements\addresses\LabelField' => LayoutElements\Addresses\LabelField::class,
        'craft\fieldlayoutelements\addresses\LatLongField' => LayoutElements\Addresses\LatLongField::class,
        'craft\fieldlayoutelements\addresses\OrganizationField' => LayoutElements\Addresses\OrganizationField::class,
        'craft\fieldlayoutelements\addresses\OrganizationTaxIdField' => LayoutElements\Addresses\OrganizationTaxIdField::class,
        'craft\fieldlayoutelements\assets\AltField' => LayoutElements\Assets\AltField::class,
        'craft\fieldlayoutelements\assets\AssetTitleField' => LayoutElements\Assets\AssetTitleField::class,
        'craft\fieldlayoutelements\entries\EntryTitleField' => LayoutElements\Entries\EntryTitleField::class,
        'craft\fieldlayoutelements\users\AffiliatedSiteField' => LayoutElements\Users\AffiliatedSiteField::class,
        'craft\fieldlayoutelements\users\EmailField' => LayoutElements\Users\EmailField::class,
        'craft\fieldlayoutelements\users\FullNameField' => LayoutElements\Users\FullNameField::class,
        'craft\fieldlayoutelements\users\PhotoField' => LayoutElements\Users\PhotoField::class,
        'craft\fieldlayoutelements\users\UsernameField' => LayoutElements\Users\UsernameField::class,
    ];

    public function up(): void
    {
        foreach (DB::table(Table::FIELDLAYOUTS)->whereNotNull('config')->lazyById() as $layout) {
            $config = json_decode($layout->config, true, flags: JSON_THROW_ON_ERROR);
            $updated = $this->updateTypes($config, ['fieldLayout']);

            if ($updated !== $config) {
                DB::table(Table::FIELDLAYOUTS)->where('id', $layout->id)->update([
                    'config' => json_encode($updated, JSON_THROW_ON_ERROR),
                ]);
            }
        }

        $projectConfig = app(ProjectConfig::class);
        $muteEvents = $projectConfig->muteEvents;
        $readOnly = $projectConfig->readOnly;
        $projectConfig->muteEvents = true;
        $projectConfig->readOnly = false;

        try {
            foreach ($projectConfig->get() as $key => $config) {
                $updated = $this->updateTypes($config, [$key]);

                if ($updated !== $config) {
                    $projectConfig->set($key, $updated);
                }
            }
        } finally {
            $projectConfig->muteEvents = $muteEvents;
            $projectConfig->readOnly = $readOnly;
        }
    }

    /** @param list<string|int> $path */
    private function updateTypes(mixed $config, array $path): mixed
    {
        if (! is_array($config)) {
            return is_string($config) && isset($this->map[$config]) && $this->isElementTypePath($path)
                ? $this->map[$config]
                : $config;
        }

        if (isset($config[ProjectConfig::ASSOC_KEY])) {
            foreach ($config[ProjectConfig::ASSOC_KEY] as &$pair) {
                $pair[1] = $this->updateTypes($pair[1], [...$path, $pair[0]]);
            }

            return $config;
        }

        foreach ($config as $key => &$value) {
            $value = $this->updateTypes($value, [...$path, $key]);
        }

        return $config;
    }

    /** @param list<string|int> $path */
    private function isElementTypePath(array $path): bool
    {
        $path = array_reverse($path);

        return ($path[0] ?? null) === 'type'
            && ($path[2] ?? null) === 'elements'
            && ($path[4] ?? null) === 'tabs'
            && (($path[5] ?? null) === 'fieldLayout' || ($path[6] ?? null) === 'fieldLayouts');
    }

    /** Existing modern types cannot be distinguished from migrated types. */
    public function down(): void {}
};
