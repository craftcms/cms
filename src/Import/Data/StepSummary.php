<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * An import step at a glance, for the plan's list of steps: what its importer type's own
 * settings are set to, and what its destination columns are called.
 *
 * @since 6.0.0
 */
readonly class StepSummary implements JsonSerializable
{
    /**
     * @param  list<array{label: string, value: string}>  $settings
     * @param  array<string, array{label: string, handle: string, icon: string|null}>  $destinationLabels  Keyed by each column's top-level key in the step's map, and by its handle.
     */
    public function __construct(
        #[LiteralTypeScriptType('Array<{label: string, value: string}>')]
        public array $settings = [],
        #[LiteralTypeScriptType('Record<string, {label: string, handle: string, icon: string | null}>')]
        public array $destinationLabels = [],
    ) {}

    public static function fromImporter(?BaseImporter $importer): self
    {
        if ($importer === null) {
            return new self;
        }

        $hasDestination = ! $importer instanceof ElementImporter || ! empty($importer->fieldLayout);

        return new self(
            settings: self::settings($importer),
            destinationLabels: $hasDestination ? $importer->getDestinationLabels() : [],
        );
    }

    /**
     * Each shown setting with a value, as its field's label and the value's label, read from
     * the importer type's own settings form.
     *
     * @return list<array{label: string, value: string}>
     */
    private static function settings(BaseImporter $importer): array
    {
        $nodes = $importer->settingsUi();

        if ($nodes === []) {
            return [];
        }

        $payload = app(UiResolver::class)->resolve(Ui::make($nodes), new UiContext);

        return self::nodeSettings($payload->nodes, $payload->values);
    }

    /**
     * @param  list<NodePayload>  $nodes
     * @param  array<array-key, mixed>  $values
     * @return list<array{label: string, value: string}>
     */
    private static function nodeSettings(array $nodes, array $values): array
    {
        $settings = [];

        foreach ($nodes as $node) {
            if ($node->props['hidden'] ?? false) {
                continue;
            }

            if ($node->control !== null && isset($node->props['label'])) {
                $value = self::valueLabel(data_get($values, $node->control->path), $node->control->props['options'] ?? null);

                if ($value !== null) {
                    $settings[] = ['label' => (string) $node->props['label'], 'value' => $value];
                }
            }

            array_push($settings, ...self::nodeSettings($node->children ?? [], $values));
        }

        return $settings;
    }

    /** @param  list<array<string, mixed>>|null  $options */
    private static function valueLabel(mixed $value, ?array $options): ?string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        foreach ($options ?? [] as $option) {
            if (isset($option['value']) && (string) $option['value'] === (string) $value) {
                return (string) ($option['label'] ?? $value);
            }
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'settings' => $this->settings,
            'destinationLabels' => (object) $this->destinationLabels,
        ];
    }
}
