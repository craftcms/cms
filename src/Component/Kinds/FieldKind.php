<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Kinds;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Ui\SettingsSchema;
use Override;

/**
 * Field types.
 *
 * @since 6.0.0
 */
readonly class FieldKind extends TypeRegistryKind
{
    public function __construct(
        SettingsSchema $settingsSchema,
        private FieldTypes $fieldTypes,
        private Fields $fields,
    ) {
        parent::__construct($settingsSchema);
    }

    public static function handle(): string
    {
        return 'field';
    }

    public function description(): string
    {
        return 'Field types.';
    }

    public function usage(): string
    {
        return 'configuration.create with type: fields takes the class as attributes.type and its settings as attributes.settings.';
    }

    protected function registry(): TypeRegistry
    {
        return $this->fieldTypes;
    }

    #[Override]
    protected function createComponent(string $type): ConfigurableComponentInterface
    {
        return $this->fields->createField(['type' => $type]);
    }
}
