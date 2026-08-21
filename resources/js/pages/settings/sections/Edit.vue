<script setup lang="ts">
    import type {UrlMethodPair} from '@inertiajs/core';
    import {toUriFormat} from '@craftcms/ui';
    import {ref} from 'vue';
    import type {EntryType, SectionSiteSettingsData} from '@/common/types';
    import EntryTypeSelect from '@/modules/entry-types/components/EntryTypeSelect.vue';
    import type {
        FormChange,
        FormChangeKind,
        FormControlOverrideProps,
        FormPayload,
    } from '@/modules/forms/types';
    import {isRecord, pathsMatch} from '@/modules/forms/runtime';
    import PreviewTargetsTable from '@/modules/sections/components/PreviewTargetsTable.vue';
    import SiteSettingsTable from '@/modules/sections/components/SiteSettingsTable.vue';
    import FormPage from '@/pages/Form.vue';

    type SiteSettings = Record<string, Omit<SectionSiteSettingsData, 'handle'>>;
    type PreviewTarget = {
        label: string;
        urlFormat: string;
        refresh: boolean;
    };

    const props = defineProps<{
        form: FormPayload;
        submit: UrlMethodPair;
        refreshUrl: string | null;
        brandNew: boolean;
        entryTypes: EntryType[];
        homepageUri: string;
        templateOptions: Array<unknown>;
        isMultiSite: boolean;
        headlessMode: boolean;
    }>();

    const formPage = ref<{
        setValue(path: string[], value: unknown, kind?: FormChangeKind): void;
    }>();

    function selectedEntryTypes(value: unknown): EntryType[] {
        if (!Array.isArray(value)) {
            return [];
        }

        return value.flatMap((id) => {
            const entryType = props.entryTypes.find(
                (candidate) => candidate.id === Number(id)
            );

            return entryType ? [entryType] : [];
        });
    }

    function setEntryTypes(
        entryTypes: EntryType[],
        setValue: FormControlOverrideProps['setValue']
    ): void {
        setValue(
            entryTypes.map((entryType) => entryType.id),
            'discrete'
        );
    }

    function siteSettings(value: unknown): SiteSettings {
        return isRecord(value) ? (value as SiteSettings) : {};
    }

    function previewTargets(value: unknown): PreviewTarget[] {
        return Array.isArray(value) ? (value as PreviewTarget[]) : [];
    }

    function onChange(change: FormChange, values: FormPayload['values']): void {
        if (!props.brandNew || !pathsMatch(change.path, ['name'])) {
            return;
        }

        const sites = siteSettings(values.sites);
        const uri = toUriFormat(String(values.name ?? ''));
        const generated = Object.fromEntries(
            Object.entries(sites).map(([handle, site]) => [
                handle,
                {
                    ...site,
                    singleUri:
                        uri && !site.singleHomepage
                            ? uri
                            : (site.singleUri ?? ''),
                    uriFormat: uri ? `${uri}/{slug}` : '',
                    template: uri ? `${uri}/_entry.twig` : '',
                },
            ])
        );

        formPage.value?.setValue(['sites'], generated, change.kind);
    }
</script>

<template>
    <FormPage
        ref="formPage"
        :form="form"
        :submit="submit"
        :refresh-url="refreshUrl ?? undefined"
        @change="onChange"
    >
        <template #entryTypes="{value, setValue, editable}">
            <EntryTypeSelect
                :entry-types="entryTypes"
                :model-value="selectedEntryTypes(value)"
                @update:model-value="setEntryTypes($event, setValue)"
            />
        </template>

        <template #sites="{value, values, setValue, editable}">
            <SiteSettingsTable
                :is-multisite="isMultiSite"
                :is-headless="headlessMode"
                :selected-type="String(values.type ?? '')"
                :model-value="siteSettings(value)"
                :disabled="!editable"
                @update:model-value="setValue($event, 'typing')"
            />
        </template>

        <template #previewTargets="{value, setValue, editable}">
            <PreviewTargetsTable
                :model-value="previewTargets(value)"
                :disabled="!editable"
                @update:model-value="setValue($event, 'typing')"
            />
        </template>
    </FormPage>
</template>
