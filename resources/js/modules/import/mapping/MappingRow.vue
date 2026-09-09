<script setup lang="ts">
    /**
     * One destination column: what it maps to, and how it behaves when the imported
     * value is missing.
     *
     * A container column has no mapping of its own — it opens a nested panel over the
     * same trees instead, which is why every control here writes through
     * `prefixedHandleAsArray` rather than a form input name.
     */
    import '@craftcms/ui/components/button/button';
    import '@craftcms/ui/components/checkbox/checkbox';
    import '@craftcms/ui/components/select/select';
    import {computed, inject, useTemplateRef} from 'vue';
    import {ButtonVariant, t} from '@craftcms/ui';
    import {ignoreModelValueInitialization} from '@/modules/forms/runtime';
    import {checkedValue, getAt, isChecked, setAt} from './paths';
    import {type MappingCol, MappingContextKey} from './types';

    const props = defineProps<{
        col: MappingCol;
    }>();

    const context = inject(MappingContextKey);

    if (!context) {
        throw new Error('MappingRow must be rendered inside a mapping screen.');
    }

    const nestedTrigger = useTemplateRef<HTMLElement>('nestedTrigger');
    const path = computed(() => props.col.prefixedHandleAsArray);

    const mapValue = computed<string>(() => {
        const value = getAt(context.values.map, path.value);

        return value === null || value === undefined ? '' : String(value);
    });

    const matchCriteriaChecked = computed(() =>
        isChecked(getAt(context.values.matchCriteria, path.value))
    );

    const clearChecked = computed(() =>
        isChecked(getAt(context.values.clearableItems, path.value))
    );

    function onMapChanged(event: Event): void {
        if (!(event.target instanceof HTMLSelectElement)) {
            throw new TypeError('Expected a select event target.');
        }

        setAt(context!.values.map, path.value, event.target.value);
    }

    // `craft-checkbox` announces its starting state too, which would write to the
    // trees before the user has touched anything and read as an unsaved change.
    const onMatchCriteriaChanged = ignoreModelValueInitialization((event) => {
        setAt(context!.values.matchCriteria, path.value, checkedValue(event));
    });

    const onClearChanged = ignoreModelValueInitialization((event) => {
        setAt(context!.values.clearableItems, path.value, checkedValue(event));
    });

    function openNested(): void {
        context!.openNested(props.col, nestedTrigger.value);
    }

    /** Whether the container's nested mapping has anything in it yet. */
    const hasNestedMapping = computed(() => {
        const branch = getAt(context!.values.map, path.value);

        return (
            branch !== null &&
            typeof branch === 'object' &&
            Object.keys(branch as object).length > 0
        );
    });
</script>

<template>
    <tr>
        <th scope="row">
            {{ col.label }}<br />
            <code>{{ col.handle }}</code>
        </th>
        <td>
            <craft-button
                v-if="col.isContainer"
                ref="nestedTrigger"
                type="button"
                :variant="ButtonVariant.Dashed"
                :disabled="!context.editable"
                @click="openNested"
            >
                {{ hasNestedMapping ? t('Edit mapping') : t('Map field') }}
            </craft-button>
            <template v-else>
                <craft-select :disabled="!context.editable">
                    <select
                        slot="input"
                        :disabled="!context.editable"
                        :aria-label="col.label"
                        @change="onMapChanged"
                    >
                        <option
                            v-for="option in context.sourceDataCols"
                            :key="option.value"
                            :value="option.value"
                            :selected="option.value === mapValue"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </craft-select>

                <craft-checkbox
                    v-if="col.canBeMatchCriteria"
                    :label="
                        t(
                            'Use this field’s value to match against an existing element.'
                        )
                    "
                    .checked="matchCriteriaChecked"
                    :disabled="!context.editable"
                    @model-value-changed="onMatchCriteriaChanged"
                ></craft-checkbox>

                <craft-checkbox
                    v-if="col.canBeCleared"
                    :label="
                        t(
                            'Clear existing value if no data provided or provided value is empty.'
                        )
                    "
                    .checked="clearChecked"
                    :disabled="!context.editable"
                    @model-value-changed="onClearChanged"
                ></craft-checkbox>
            </template>
        </td>
    </tr>
</template>
