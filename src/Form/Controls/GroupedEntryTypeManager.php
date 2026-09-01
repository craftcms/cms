<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Support\Collection;

use function CraftCms\Cms\template;

class GroupedEntryTypeManager extends Control
{
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return self::managerHtml(
            is_array($value) ? $value : [],
            $attributes['name'],
            $attributes['name'] === null,
        );
    }

    /** @param list<array<string, mixed>|int|string> $value */
    public static function managerHtml(array $value, ?string $name, bool $disabled): string
    {
        $entryTypesService = app(EntryTypes::class);
        $entryTypes = array_values(array_filter(array_map(
            $entryTypesService->getEntryType(...),
            $value,
        )));
        $newSelect = $disabled ? null : InputNamespace::with(
            namespace: null,
            callback: fn (): string => FormFields::entryTypeSelectHtml([
                'id' => 'TEMP_ID',
                'name' => 'entryTypes[]',
                'renderDefaultInput' => false,
                'allowOverrides' => true,
                'create' => true,
                'jsClass' => false,
            ]),
        );
        $namespace = $name === null ? null : self::parentInputName($name);

        return InputNamespace::namespaceInputs(fn (): string => template(
            '_components/fieldtypes/Matrix/grouped-entry-type-manager-control',
            [
                'entryTypes' => Collection::make($entryTypes),
                'entryTypeSelectHtml' => $newSelect,
                'disabled' => $disabled,
            ],
        ), $namespace);
    }

    public function component(): string
    {
        return 'craft:grouped-entry-type-manager';
    }
}
