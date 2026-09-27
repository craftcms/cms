<?php

declare(strict_types=1);

use CraftCms\Cms\Address;
use CraftCms\Cms\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element;
use CraftCms\Cms\Entry;
use CraftCms\Cms\Field;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    private const array ELEMENT_TYPES = [
        'craft\elements\Address' => Address\Elements\Address::class,
        'craft\elements\Asset' => Asset\Elements\Asset::class,
        'craft\elements\ContentBlock' => Field\Elements\ContentBlock::class,
        'craft\elements\Entry' => Entry\Elements\Entry::class,
        'craft\elements\User' => User\Elements\User::class,
    ];

    private const array CONDITION_TYPES = [
        'craft\elements\conditions\addresses\AddressCondition' => Address\Conditions\AddressCondition::class,
        'craft\elements\conditions\addresses\AddressLine1ConditionRule' => Address\Conditions\AddressLine1ConditionRule::class,
        'craft\elements\conditions\addresses\AddressLine2ConditionRule' => Address\Conditions\AddressLine2ConditionRule::class,
        'craft\elements\conditions\addresses\AddressLine3ConditionRule' => Address\Conditions\AddressLine3ConditionRule::class,
        'craft\elements\conditions\addresses\AdministrativeAreaConditionRule' => Address\Conditions\AdministrativeAreaConditionRule::class,
        'craft\elements\conditions\addresses\CountryConditionRule' => Address\Conditions\CountryConditionRule::class,
        'craft\elements\conditions\addresses\DependentLocalityConditionRule' => Address\Conditions\DependentLocalityConditionRule::class,
        'craft\elements\conditions\addresses\FieldConditionRule' => Address\Conditions\FieldConditionRule::class,
        'craft\elements\conditions\addresses\FullNameConditionRule' => Address\Conditions\FullNameConditionRule::class,
        'craft\elements\conditions\addresses\LocalityConditionRule' => Address\Conditions\LocalityConditionRule::class,
        'craft\elements\conditions\addresses\OrganizationConditionRule' => Address\Conditions\OrganizationConditionRule::class,
        'craft\elements\conditions\addresses\OrganizationTaxIdConditionRule' => Address\Conditions\OrganizationTaxIdConditionRule::class,
        'craft\elements\conditions\addresses\PostalCodeConditionRule' => Address\Conditions\PostalCodeConditionRule::class,
        'craft\elements\conditions\addresses\SortingCodeConditionRule' => Address\Conditions\SortingCodeConditionRule::class,
        'craft\elements\conditions\assets\AssetCondition' => Asset\Conditions\AssetCondition::class,
        'craft\elements\conditions\assets\DateModifiedConditionRule' => Asset\Conditions\DateModifiedConditionRule::class,
        'craft\elements\conditions\assets\FilenameConditionRule' => Asset\Conditions\FilenameConditionRule::class,
        'craft\elements\conditions\assets\FileSizeConditionRule' => Asset\Conditions\FileSizeConditionRule::class,
        'craft\elements\conditions\assets\FileTypeConditionRule' => Asset\Conditions\FileTypeConditionRule::class,
        'craft\elements\conditions\assets\HasAltConditionRule' => Asset\Conditions\HasAltConditionRule::class,
        'craft\elements\conditions\assets\HeightConditionRule' => Asset\Conditions\HeightConditionRule::class,
        'craft\elements\conditions\assets\EditableConditionRule' => Asset\Conditions\SavableConditionRule::class,
        'craft\elements\conditions\assets\SavableConditionRule' => Asset\Conditions\SavableConditionRule::class,
        'craft\elements\conditions\assets\UploaderConditionRule' => Asset\Conditions\UploaderConditionRule::class,
        'craft\elements\conditions\assets\ViewableConditionRule' => Asset\Conditions\ViewableConditionRule::class,
        'craft\elements\conditions\assets\VolumeConditionRule' => Asset\Conditions\VolumeConditionRule::class,
        'craft\elements\conditions\assets\WidthConditionRule' => Asset\Conditions\WidthConditionRule::class,
        'craft\elements\conditions\ElementCondition' => Element\Conditions\ElementCondition::class,
        'craft\elements\conditions\DateCreatedConditionRule' => Element\Conditions\DateCreatedConditionRule::class,
        'craft\elements\conditions\DateUpdatedConditionRule' => Element\Conditions\DateUpdatedConditionRule::class,
        'craft\elements\conditions\HasDescendantsRule' => Element\Conditions\HasDescendantsRule::class,
        'craft\elements\conditions\HasUrlConditionRule' => Element\Conditions\HasUrlConditionRule::class,
        'craft\elements\conditions\IdConditionRule' => Element\Conditions\IdConditionRule::class,
        'craft\elements\conditions\LanguageConditionRule' => Element\Conditions\LanguageConditionRule::class,
        'craft\elements\conditions\LevelConditionRule' => Element\Conditions\LevelConditionRule::class,
        'craft\elements\conditions\NotRelatedToConditionRule' => Element\Conditions\NotRelatedToConditionRule::class,
        'craft\elements\conditions\RelatedToConditionRule' => Element\Conditions\RelatedToConditionRule::class,
        'craft\elements\conditions\SiteConditionRule' => Element\Conditions\SiteConditionRule::class,
        'craft\elements\conditions\SiteGroupConditionRule' => Element\Conditions\SiteGroupConditionRule::class,
        'craft\elements\conditions\SlugConditionRule' => Element\Conditions\SlugConditionRule::class,
        'craft\elements\conditions\StatusConditionRule' => Element\Conditions\StatusConditionRule::class,
        'craft\elements\conditions\TitleConditionRule' => Element\Conditions\TitleConditionRule::class,
        'craft\elements\conditions\UriConditionRule' => Element\Conditions\UriConditionRule::class,
        'craft\elements\conditions\entries\EntryCondition' => Entry\Conditions\EntryCondition::class,
        'craft\elements\conditions\entries\AuthorConditionRule' => Entry\Conditions\AuthorConditionRule::class,
        'craft\elements\conditions\entries\AuthorGroupConditionRule' => Entry\Conditions\AuthorGroupConditionRule::class,
        'craft\elements\conditions\entries\ExpiryDateConditionRule' => Entry\Conditions\ExpiryDateConditionRule::class,
        'craft\elements\conditions\entries\FieldConditionRule' => Entry\Conditions\FieldConditionRule::class,
        'craft\elements\conditions\entries\MatrixFieldConditionRule' => Entry\Conditions\FieldConditionRule::class,
        'craft\elements\conditions\entries\PostDateConditionRule' => Entry\Conditions\PostDateConditionRule::class,
        'craft\elements\conditions\entries\EditableConditionRule' => Entry\Conditions\SavableConditionRule::class,
        'craft\elements\conditions\entries\SavableConditionRule' => Entry\Conditions\SavableConditionRule::class,
        'craft\elements\conditions\entries\SectionConditionRule' => Entry\Conditions\SectionConditionRule::class,
        'craft\elements\conditions\entries\TypeConditionRule' => Entry\Conditions\TypeConditionRule::class,
        'craft\elements\conditions\entries\ViewableConditionRule' => Entry\Conditions\ViewableConditionRule::class,
        'craft\fields\conditions\CountryFieldConditionRule' => Field\Conditions\CountryFieldConditionRule::class,
        'craft\fields\conditions\DateFieldConditionRule' => Field\Conditions\DateFieldConditionRule::class,
        'craft\fields\conditions\EmptyFieldConditionRule' => Field\Conditions\EmptyFieldConditionRule::class,
        'craft\fields\conditions\GeneratedFieldConditionRule' => Field\Conditions\GeneratedFieldConditionRule::class,
        'craft\fields\conditions\LightswitchFieldConditionRule' => Field\Conditions\LightswitchFieldConditionRule::class,
        'craft\fields\conditions\LinkFieldConditionRule' => Field\Conditions\LinkFieldConditionRule::class,
        'craft\fields\conditions\MoneyFieldConditionRule' => Field\Conditions\MoneyFieldConditionRule::class,
        'craft\fields\conditions\NumberFieldConditionRule' => Field\Conditions\NumberFieldConditionRule::class,
        'craft\fields\conditions\OptionsFieldConditionRule' => Field\Conditions\OptionsFieldConditionRule::class,
        'craft\fields\conditions\RelationalFieldConditionRule' => Field\Conditions\RelationalFieldConditionRule::class,
        'craft\fields\conditions\TextFieldConditionRule' => Field\Conditions\TextFieldConditionRule::class,
        'craft\elements\conditions\users\UserCondition' => User\Conditions\UserCondition::class,
        'craft\elements\conditions\users\AdminConditionRule' => User\Conditions\AdminConditionRule::class,
        'craft\elements\conditions\users\AffiliatedSiteConditionRule' => User\Conditions\AffiliatedSiteConditionRule::class,
        'craft\elements\conditions\users\CredentialedConditionRule' => User\Conditions\CredentialedConditionRule::class,
        'craft\elements\conditions\users\EmailConditionRule' => User\Conditions\EmailConditionRule::class,
        'craft\elements\conditions\users\FirstNameConditionRule' => User\Conditions\FirstNameConditionRule::class,
        'craft\elements\conditions\users\GroupConditionRule' => User\Conditions\GroupConditionRule::class,
        'craft\elements\conditions\users\LastLoginDateConditionRule' => User\Conditions\LastLoginDateConditionRule::class,
        'craft\elements\conditions\users\LastNameConditionRule' => User\Conditions\LastNameConditionRule::class,
        'craft\elements\conditions\users\UsernameConditionRule' => User\Conditions\UsernameConditionRule::class,
    ];

    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);
        $sources = $projectConfig->get(ProjectConfig::PATH_ELEMENT_SOURCES) ?? [];
        $updatedSources = $this->updateConfig($sources);

        foreach (self::ELEMENT_TYPES as $old => $new) {
            if (! array_key_exists($old, $updatedSources)) {
                continue;
            }

            if (array_key_exists($new, $updatedSources) && $updatedSources[$new] !== $updatedSources[$old]) {
                Log::warning("Both elementSources.$old and elementSources.$new contain source settings. "
                    ."Craft uses elementSources.$new; the legacy settings have been retained at elementSources.$old. "
                    .'This can happen when source settings were saved after upgrading to Craft 6. '
                    .'Compare these project config entries, copy any legacy sources or settings you still need into the modern entry, '
                    .'then remove the legacy entry and apply the project config changes. No automatic merge was performed to avoid overwriting current settings.');

                continue;
            }

            $updatedSources[$new] = $updatedSources[$old];
            unset($updatedSources[$old]);
        }

        foreach ([Table::FIELDLAYOUTS => 'config', Table::FIELDS => 'settings'] as $table => $column) {
            foreach (DB::table($table)->whereNotNull($column)->lazyById() as $row) {
                $config = json_decode($row->$column, flags: JSON_THROW_ON_ERROR);
                $updated = $this->updateConfig($config);

                if ($updated !== $config) {
                    DB::table($table)->where('id', $row->id)->update([
                        $column => json_encode($updated, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
                    ]);
                }
            }
        }

        $muteEvents = $projectConfig->muteEvents;
        $readOnly = $projectConfig->readOnly;
        $projectConfig->muteEvents = true;
        $projectConfig->readOnly = false;

        try {
            foreach ($projectConfig->get() as $key => $config) {
                $updated = $key === ProjectConfig::PATH_ELEMENT_SOURCES && $config !== null
                    ? $updatedSources
                    : $this->updateConfig($config);

                if ($updated !== $config) {
                    $projectConfig->set($key, $updated);
                }
            }

            $projectConfig->saveModifiedConfigData();
        } finally {
            $projectConfig->muteEvents = $muteEvents;
            $projectConfig->readOnly = $readOnly;
        }
    }

    private function updateConfig(mixed $config, string $context = 'config'): mixed
    {
        if (is_string($config)) {
            if ($context === 'elementType') {
                return self::ELEMENT_TYPES[$config] ?? $config;
            }

            if (in_array($context, ['type', 'conditionConfig'], true) && str_starts_with(ltrim($config), '{') && json_validate($config)) {
                $decoded = json_decode($config, flags: JSON_THROW_ON_ERROR);
                $updated = $this->updateConfig($decoded, $context === 'type' ? 'rule' : 'condition');

                return $updated !== $decoded ? json_encode($updated, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION) : $config;
            }

            if (in_array($context, ['condition', 'rule', 'class', 'type'], true)) {
                return self::CONDITION_TYPES[$config] ?? $config;
            }
        }

        if (! is_array($config) && ! $config instanceof stdClass) {
            return $config;
        }

        $values = (array) $config;

        if ($context === 'rule') {
            $attributes = isset($values[ProjectConfig::ASSOC_KEY])
                ? array_column($values[ProjectConfig::ASSOC_KEY], 1, 0)
                : $values;

            if (! isset($attributes['class']) && ! isset($attributes['type']) && isset($attributes['rules'])) {
                $context = 'rules';
            }
        }

        if (isset($values[ProjectConfig::ASSOC_KEY])) {
            foreach ($values[ProjectConfig::ASSOC_KEY] as &$pair) {
                $pair[1] = $this->updateValue($pair[0], $pair[1], $context);
            }
        } else {
            foreach ($values as $key => &$value) {
                $value = $this->updateValue($key, $value, $context);
            }
        }

        if ($config instanceof stdClass) {
            return $values === (array) $config ? $config : (object) $values;
        }

        return $values;
    }

    private function updateValue(string|int $key, mixed $value, string $context): mixed
    {
        $next = match ($context) {
            'config' => in_array($key, ['condition', 'elementCondition', 'userCondition', 'editCondition', 'elementEditCondition', 'selectionCondition', 'assetSelectionCondition', 'defaultFilter'], true)
                ? 'condition' : 'config',
            'condition' => match ($key) {
                'class' => 'class',
                'config' => 'conditionConfig',
                'elementType' => 'elementType',
                'attributes' => 'attributes',
                'conditionRules' => 'rules',
                default => null,
            },
            'attributes' => $key === 'elementType' ? 'elementType' : null,
            'rules' => $key === 'rules' ? 'rules' : (is_int($key) ? 'rule' : null),
            'rule' => match ($key) {
                'class' => 'class',
                'type' => 'type',
                default => null,
            },
            default => null,
        };

        return $next !== null ? $this->updateConfig($value, $next) : $value;
    }

    /** Existing modern types cannot be distinguished from migrated types. */
    public function down(): void {}
};
