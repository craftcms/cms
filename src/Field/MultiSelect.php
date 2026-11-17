<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Combobox;

use function CraftCms\Cms\t;

/**
 * MultiSelect represents a Multi-select field.
 *
 * @since 6.0.0
 */
class MultiSelect extends BaseOptionsField
{
    #[\Override]
    protected static bool $multi = true;

    #[\Override]
    protected static bool $optgroups = true;

    #[\Override]
    protected static bool $optionIcons = true;

    #[\Override]
    protected static bool $optionColors = true;

    #[\Override]
    public static function displayName(): string
    {
        return t('Multi-select');
    }

    #[\Override]
    public static function icon(): string
    {
        return 'list-check';
    }

    #[\Override]
    public function uiControl(FieldContext $context): Control
    {
        return Combobox::make($context->path)
            ->multiple()
            ->requireOptionMatch()
            ->showAllOnEmpty()
            ->options($this->comboboxOptions($context))
            ->value($this->encodeValue($context->value));
    }

    #[\Override]
    protected function optionsSettingLabel(): string
    {
        return t('Multi-select Options');
    }
}
