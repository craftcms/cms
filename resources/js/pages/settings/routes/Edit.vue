<script setup lang="ts">
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import type {MixedInputPart} from '@/pages/settings/routes/types';
    import MixedInput from '@/common/form/MixedInput.vue';
    import Select from '@/common/form/Select.vue';
    import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
    import type {RouteData, RouteFormData} from './types';
    import {destroy, store, update} from '@actions/Settings/RoutesController';
    import {router, useForm} from '@inertiajs/vue3';
    import {t} from '@craftcms/ui';
    import {computed, shallowRef} from 'vue';
    import type {BaseOption, SelectItem} from '@/common/types';
    import CraftCombobox from '@/common/form/CraftCombobox.vue';

    const props = defineProps<{
        title: string;
        route: RouteData;
        tokens: Array<BaseOption>;
        sites: Array<BaseOption>;
        isMultiSite: boolean;
        readOnly?: boolean;
        errors: Record<string, string> | null;
        templateOptions: Array<SelectItem>;
    }>();

    const mixedInput = shallowRef<InstanceType<typeof MixedInput> | null>(null);

    const form = useForm<RouteFormData>({
        uriParts: props.route.uriParts,
        template: props.route.template,
        siteUid: props.route.siteUid ?? '',
    });

    const additionalActions = computed(() => {
        if (!props.route.uid || props.readOnly) {
            return [];
        }

        return [
            {
                variant: 'danger' as const,
                label: t('Delete'),
                onClick: deleteRoute,
            },
        ];
    });

    const routeAction = () =>
        props.route.uid ? update({uid: props.route.uid}) : store();

    const {save: saveRoute} = useSettingsSave(form, routeAction, {
        transform: (data) => ({
            ...data,
            uriParts: normalizedUriParts(),
            siteUid: data.siteUid || null,
        }),
    });

    function addUriToken(token: BaseOption) {
        mixedInput.value?.addToken(token);
    }

    function handleUriTokenClick(event: MouseEvent, token: BaseOption) {
        if (event.detail === 0) {
            addUriToken(token);
        }
    }

    function normalizedUriParts(): Array<MixedInputPart> {
        const parts = [...form.uriParts];

        if (typeof parts[0] === 'string') {
            parts[0] = parts[0].replace(/^\/+/, '');
        }

        return parts.filter((part) =>
            typeof part === 'string' ? part !== '' : true
        );
    }

    function deleteRoute() {
        if (!props.route.uid) {
            return;
        }

        if (!confirm(t('Are you sure you want to delete this route?'))) {
            return;
        }

        router.delete(destroy({uid: props.route.uid}), {
            preserveScroll: true,
        });
    }

    useAppLayout(() => ({
        title: props.title,
        form,
        formAdditionalActions: additionalActions.value,
        onSave: saveRoute,
    }));
</script>

<template>
    <craft-pane appearance="raised">
        <div class="route-form">
            <Select
                :label="t('Site')"
                v-if="isMultiSite"
                class="route-site-select"
                id="route-site"
                name="siteUid"
                v-model="form.siteUid"
                :options="sites"
                :disabled="form.processing || readOnly"
                :aria-label="t('Site')"
            />

            <div class="route-uri-field">
                <craft-input
                    :label="t('If the URI looks like this')"
                    :has-feedback-for="form.errors.uriParts ? 'error' : ''"
                >
                    <MixedInput
                        slot="input"
                        ref="mixedInput"
                        v-model="form.uriParts"
                        class="route-uri-input"
                        :invalid="!!form.errors.uriParts"
                        :disabled="form.processing || readOnly"
                        :aria-label="t('URI')"
                    />

                    <div slot="feedback">
                        <ul class="error-list" v-if="form.errors.uriParts">
                            <li>{{ form.errors.uriParts }}</li>
                        </ul>
                    </div>
                </craft-input>

                <div class="route-token-picker">
                    <h3>{{ t('Add a token') }}</h3>
                    <button
                        v-for="token in tokens"
                        :key="token.label"
                        type="button"
                        class="route-token route-token--button"
                        :disabled="form.processing || readOnly"
                        @mousedown.prevent="addUriToken(token)"
                        @click="handleUriTokenClick($event, token)"
                    >
                        {{ token.label }}
                    </button>
                </div>
            </div>

            <CraftCombobox
                :label="t('Load this template')"
                id="route-template"
                name="template"
                v-model="form.template"
                dir="ltr"
                :disabled="form.processing || readOnly"
                :error="form.errors.template"
                required
                :options="templateOptions"
            />
        </div>
    </craft-pane>
</template>

<style scoped lang="scss">
    .route-form {
        display: grid;
        gap: 22px;
        width: 100%;
    }

    .route-uri-field {
        display: grid;
        gap: 10px;
    }

    .route-uri-field__label {
        font-weight: 600;
    }

    .route-uri-field__controls {
        align-items: flex-start;
        display: flex;
        gap: 8px;
    }

    .route-uri-input {
        flex: 1 1 auto;
        min-width: 0;
    }

    .route-site-select {
        flex: 0 0 134px;
    }

    .route-site-select :deep(select) {
        min-height: var(--c-size-control-md);
        width: 100%;
    }

    .route-token {
        align-items: center;
        background: var(--c-color-neutral-fill-normal);
        border: 0;
        border-radius: var(--c-radius-sm);
        color: var(--c-color-neutral-on-normal);
        display: inline-flex;
        font-family: var(--c-font-mono);
        font-size: var(--c-text-sm);
        gap: 0.25rem;
        line-height: 1.3;
        padding: 0.125rem 0.4rem;
    }

    .route-token--button {
        appearance: none;
        cursor: pointer;
    }

    .route-token--button:disabled {
        cursor: not-allowed;
    }

    .route-token--button:focus {
        box-shadow: 0 0 0 1px var(--c-surface-default);
        outline: 2px solid var(--c-text-link);
        outline-offset: 1px;
    }

    .route-token-picker {
        background: var(--c-surface-default);
        border-radius: var(--c-radius-lg);
        box-shadow: var(--c-shadow-sunken);
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        justify-content: center;
        padding: 13px 24px 14px;
    }

    .route-token-picker h3 {
        flex-basis: 100%;
        font-size: var(--c-text-sm);
        font-weight: 600;
        margin: 0 0 3px;
        text-align: center;
    }

    .route-token-picker .route-token {
        font-family: inherit;
        font-size: 12px;
        line-height: 16px;
        padding: 2px 7px;
    }

    @media (max-width: 720px) {
        .route-site-select {
            flex-basis: auto;
        }

        .route-uri-field__controls {
            display: grid;
        }
    }
</style>
