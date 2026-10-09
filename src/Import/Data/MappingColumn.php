<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\Optional;

/**
 * A destination column on an import step's mapping screen.
 *
 * `prefixedHandleAsArray` is the column's path from the root of the mapping trees. The
 * `prefixedHandleFor*` strings are the bracket-named form inputs the column's values are posted under.
 *
 * @since 6.0.0
 */
readonly class MappingColumn implements JsonSerializable
{
    /**
     * @param  list<string>  $prefixedHandleAsArray
     * @param  list<FieldMappingSetting>|null  $importSettings
     */
    public function __construct(
        public string $handle,
        public string $label,
        public string $prefixedHandle,
        public array $prefixedHandleAsArray,
        public string $prefixedHandleForMap,
        public string $prefixedHandleForMatchCriteria,
        public string $prefixedHandleForClear,
        public bool $isContainer = false,
        public bool $canBeMatchCriteria = false,
        public bool $canBeCleared = false,
        #[Optional]
        public ?bool $canBeSet = null,
        #[Optional]
        public ?bool $canKeepMissingNestedElements = null,
        #[Optional]
        public ?bool $isProperty = null,
        #[Optional]
        public ?string $fieldUid = null,
        #[Optional]
        public ?string $prefixedHandleForKeep = null,
        #[Optional]
        public ?string $prefixedHandleForKeepFlag = null,
        #[Optional]
        public ?array $importSettings = null,
    ) {}

    /**
     * Creates a column, deriving its path and form input names from its prefixed handle.
     *
     * @param  list<FieldMappingSetting>|null  $importSettings
     * @param  bool  $withKeepInputs  Whether to include the keep-missing-nested-elements inputs, for a container field.
     */
    public static function make(
        string $handle,
        string $label,
        ?string $prefixedHandle = null,
        bool $isContainer = false,
        bool $canBeMatchCriteria = false,
        bool $canBeCleared = false,
        ?bool $canBeSet = null,
        ?bool $canKeepMissingNestedElements = null,
        ?bool $isProperty = null,
        ?string $fieldUid = null,
        ?array $importSettings = null,
        bool $withKeepInputs = false,
    ): self {
        $prefixedHandle ??= $handle;

        return new self(
            handle: $handle,
            label: $label,
            prefixedHandle: $prefixedHandle,
            prefixedHandleAsArray: Arr::bracketsToArray($prefixedHandle),
            prefixedHandleForMap: Html::namespaceInputName($prefixedHandle, 'map'),
            prefixedHandleForMatchCriteria: Html::namespaceInputName($prefixedHandle, 'matchCriteria'),
            prefixedHandleForClear: Html::namespaceInputName($prefixedHandle, 'clearableItems'),
            isContainer: $isContainer,
            canBeMatchCriteria: $canBeMatchCriteria,
            canBeCleared: $canBeCleared,
            canBeSet: $canBeSet,
            canKeepMissingNestedElements: $canKeepMissingNestedElements,
            isProperty: $isProperty,
            fieldUid: $fieldUid,
            prefixedHandleForKeep: $withKeepInputs ? Html::namespaceInputName($prefixedHandle, 'keepMissingNestedElements') : null,
            prefixedHandleForKeepFlag: $withKeepInputs ? Html::namespaceInputName($prefixedHandle.'[__keep__]', 'keepMissingNestedElements') : null,
            importSettings: $importSettings,
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $payload = [
            'handle' => $this->handle,
            'label' => $this->label,
            'prefixedHandle' => $this->prefixedHandle,
            'prefixedHandleAsArray' => $this->prefixedHandleAsArray,
            'prefixedHandleForMap' => $this->prefixedHandleForMap,
            'prefixedHandleForMatchCriteria' => $this->prefixedHandleForMatchCriteria,
            'prefixedHandleForClear' => $this->prefixedHandleForClear,
            'isContainer' => $this->isContainer,
            'canBeMatchCriteria' => $this->canBeMatchCriteria,
            'canBeCleared' => $this->canBeCleared,
        ];

        $optional = [
            'canBeSet' => $this->canBeSet,
            'canKeepMissingNestedElements' => $this->canKeepMissingNestedElements,
            'isProperty' => $this->isProperty,
            'fieldUid' => $this->fieldUid,
            'prefixedHandleForKeep' => $this->prefixedHandleForKeep,
            'prefixedHandleForKeepFlag' => $this->prefixedHandleForKeepFlag,
            'importSettings' => $this->importSettings === null
                ? null
                : array_map(fn (FieldMappingSetting $setting): array => $setting->jsonSerialize(), $this->importSettings),
        ];

        return $payload + array_filter($optional, fn (mixed $value): bool => $value !== null);
    }
}
