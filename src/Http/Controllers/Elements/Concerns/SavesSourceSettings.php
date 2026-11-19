<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Cp\Enums\ChipThumbSize;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Facades\Conditions;
use Illuminate\Validation\Rule;

/**
 * Reads the view an index posts to be saved onto a source.
 */
trait SavesSourceSettings
{
    /** @return array<string, mixed> */
    private function sourceSettingRules(): array
    {
        return [
            'defaultSort' => ['nullable', 'array'],
            'defaultViewMode' => ['nullable', 'string'],
            'tableAttributes' => ['nullable', 'array'],
            'defaultThumbSizes' => ['nullable', 'array:'.implode(',', array_keys(ChipThumbSize::INDEX_VIEW_SIZES))],
            ...collect(ChipThumbSize::INDEX_VIEW_SIZES)->mapWithKeys(fn (array $sizes, string $view) => [
                "defaultThumbSizes.$view" => ['nullable', Rule::in($sizes)],
            ])->all(),
            'condition' => ['nullable', 'array'],
        ];
    }

    /**
     * The sort, view mode, columns and thumbnail sizes posted, as a source
     * stores them.
     *
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function postedViewSettings(ElementIndexRequest $request, array $source = []): array
    {
        $settings = [];

        if ($request->filled('defaultSort')) {
            $settings['defaultSort'] = $this->defaultSort($request->input('defaultSort'));
        }

        if ($request->filled('defaultViewMode')) {
            $settings['defaultViewMode'] = $request->string('defaultViewMode')->value();
        }

        if ($request->has('tableAttributes')) {
            $settings['tableAttributes'] = $this->tableAttributeKeys($request->array('tableAttributes'));
        }

        // Only the views posted change; the source keeps its size for the other.
        if ($thumbSizes = array_filter($request->array('defaultThumbSizes'))) {
            $settings['defaultThumbSizes'] = [...($source['defaultThumbSizes'] ?? []), ...$thumbSizes];
        }

        return $settings;
    }

    /**
     * An index's filters as a source's own condition.
     *
     * @param  array<string, mixed>  $condition
     * @return array<string, mixed>
     */
    private function sourceCondition(array $condition): array
    {
        /** @var ElementConditionInterface $sourceCondition */
        $sourceCondition = Conditions::createCondition($condition);
        $sourceCondition->forQuery = false;

        return $sourceCondition->getConfig();
    }

    /**
     * Accepts the UI's `['attr' => …, 'dir' => …]` and the legacy
     * `[attr, dir]` list alike.
     */
    private function defaultSort(mixed $defaultSort): mixed
    {
        if (is_array($defaultSort) && (isset($defaultSort['attr']) || isset($defaultSort['dir']))) {
            return array_values(array_filter(
                [$defaultSort['attr'] ?? null, $defaultSort['dir'] ?? null],
                fn (mixed $value) => $value !== null && $value !== '',
            ));
        }

        return $defaultSort;
    }

    /**
     * @param  array<array-key, mixed>  $attributes
     * @return list<mixed>|string
     */
    private function tableAttributeKeys(array $attributes): array|string
    {
        $attributes = collect($attributes)
            ->map(fn (mixed $attribute) => data_get($attribute, 'value', $attribute))
            ->filter()
            ->values()
            ->all();

        return $attributes ?: '-';
    }
}
