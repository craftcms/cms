<script setup lang="ts">
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import {t, toHandle, toUriFormat} from '@craftcms/cp';
  import {computed} from 'vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import EntryTypeSelect from '@/modules/entry-types/components/EntryTypeSelect.vue';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import type {
    SectionResource,
    SectionSiteSettingsData,
    SelectOption,
  } from '@/common/types';
  import SiteSettingsTable from '@/modules/sections/components/SiteSettingsTable.vue';
  import PreviewTargetsTable from '@/modules/sections/components/PreviewTargetsTable.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import Pane from '@/common/components/Pane.vue';
  import {store} from '@actions/Settings/SectionsController';
  import useCraftData from '@/common/composables/useCraftData';

  const props = defineProps<{
    title: string;
    crumbs: Array<any>;
    section: SectionResource;
    brandNew: boolean;
    typeOptions: Array<SelectOption>;
    entryTypes: Array<any>;
    propagationOptions: Array<SelectOption>;
    placementOptions: Array<SelectOption>;
    siteSettings: Array<SectionSiteSettingsData>;
    isMultiSite: boolean;
    headlessMode: boolean;
    flash?: Record<any, any>;
    errors: Record<any, any> | null;
  }>();

  const {readOnly} = useCraftData();

  const form = useForm({
    sectionId: props.section.id,
    name: props.section.name ?? '',
    handle: props.section.handle ?? '',
    type: props.section.type,
    entryTypes: (props.section.entryTypes ?? []).map(
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      ({actions, ...rest}) => rest
    ),
    enableVersioning: props.section.enableVersioning,
    maxAuthors: props.section.maxAuthors ?? 1,
    maxLevels: props.section.maxLevels ?? '',
    propagationMethod: props.section.propagationMethod,
    defaultPlacement: props.section.defaultPlacement,
    previewTargets: props.section.previewTargets ?? [],
    sites: Object.fromEntries(
      props.siteSettings.map((site) => [
        site.handle,
        {
          enabled: site.enabled,
          siteId: site.siteId ?? null,
          name: site.name ?? '',
          singleHomepage: false,
          singleUri: site.uriFormat ?? '',
          uriFormat: site.uriFormat ?? '',
          template: site.template ?? '',
          enabledByDefault: site.enabledByDefault,
        },
      ])
    ),
  });

  const isStructure = computed(() => form.type === 'structure');
  const isChannelOrStructure = computed(
    () => form.type === 'channel' || form.type === 'structure'
  );

  // Auto-generate handle from name for new sections
  const handleGenerator = useInputGenerator(
    () => form.name,
    (v) => (form.handle = toHandle(v))
  );

  const uriGenerator = useInputGenerator(
    () => form.name,
    (v) => {
      if (!form.sites) {
        return;
      }

      const uri = toUriFormat(v);

      form.sites = Object.fromEntries(
        Object.entries(form.sites).map(([key, site]) => [
          key,
          {
            ...site,
            singleUri: uri && !site.singleHomepage ? `${uri}` : site.singleUri,
            uriFormat: uri ? `${uri}/{slug}` : '',
            template: uri ? `${uri}/_entry.twig` : '',
          },
        ])
      );
    }
  );

  // For existing sections, mark handle as already dirty
  if (!props.brandNew) {
    handleGenerator.stop();
    uriGenerator.stop();
  }

  const {save} = useSettingsSave(form, store);
</script>

<template>
  <AppLayout :title="title" :debug="{form, $props}" :form="form" @save="save">
    <Pane appearance="raised">
      <div class="grid gap-3">
        <input
          v-if="section.id"
          type="hidden"
          name="sectionId"
          :value="section.id"
        />

        <!-- Name -->
        <CraftInput
          :label="t('Name')"
          :help-text="
            t('What this section will be called in the control panel.')
          "
          id="name"
          name="name"
          v-model="form.name"
          :disabled="readOnly"
          :error="errors?.name"
          required
          autofocus
        />

        <!-- Handle -->
        <CraftInputHandle
          :label="t('Handle')"
          :help-text="t(`How you'll refer to this section in the templates.`)"
          id="handle"
          name="handle"
          v-model="form.handle"
          :disabled="readOnly"
          :error="errors?.handle"
          required
          @change="handleGenerator.markDirty()"
        />

        <!-- Enable Versioning -->
        <CraftSwitch
          :label="t('Enable versioning for entries in this section')"
          id="enableVersioning"
          name="enableVersioning"
          :disabled="readOnly"
          v-model="form.enableVersioning"
        />

        <!-- Section Type -->
        <CraftSelect
          :label="t('Section Type')"
          :help-text="t('What type of section is this?')"
          id="type"
          name="type"
          v-model="form.type"
          :disabled="readOnly"
          :error="errors?.type"
        >
          <select slot="input">
            <option
              v-for="option in typeOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>

          <div slot="after" v-if="section.id && form.type !== 'single'">
            <craft-callout
              variant="danger"
              appearance="plain"
              class="p-0"
              icon="triangle-exclamation"
            >
              {{ t('Changing this may result in data loss.') }}
            </craft-callout>
          </div>
        </CraftSelect>
      </div>

      <hr class="my-6" />

      <!-- Entry Types -->
      <div class="grid gap-3">
        <div>
          <h3 class="font-bold text-sm">{{ t('Entry Types') }}</h3>
          <p class="text-sm text-neutral-500 mb-2">
            {{
              t(
                'Choose the types of entries that can be included in this section.'
              )
            }}
          </p>
          <EntryTypeSelect
            :entry-types="entryTypes"
            v-model="form.entryTypes"
          />
        </div>
      </div>

      <hr class="my-6" />

      <!-- Site Settings -->
      <div class="grid gap-6">
        <div>
          <h3 class="font-bold text-sm">{{ t('Site settings') }}</h3>
          <p class="text-sm text-neutral-500 mb-2">
            {{
              t(
                'Choose which sites this section should be available in, and configure the site-specific settings.'
              )
            }}
          </p>

          <SiteSettingsTable
            :is-multisite="isMultiSite"
            :is-headless="headlessMode"
            :selected-type="form.type"
            v-model="form.sites"
          />
        </div>

        <!-- Propagation Method (multi-site only) -->
        <template v-if="isMultiSite && isChannelOrStructure">
          <CraftSelect
            :label="t('Propagation Method')"
            :help-text="
              t(
                'Of the enabled sites above, which sites should entries in this section be saved to?'
              )
            "
            id="propagationMethod"
            name="propagationMethod"
            v-model="form.propagationMethod"
            :disabled="readOnly"
          >
            <select slot="input">
              <option
                v-for="option in propagationOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>

            <div
              slot="after"
              v-if="
                section.id &&
                section.propagationMethod !== 'none' &&
                siteSettings.length > 1
              "
            >
              <craft-callout
                variant="danger"
                appearance="plain"
                class="p-0"
                icon="triangle-exclamation"
              >
                {{ t('Changing this may result in data loss.') }}
              </craft-callout>
            </div>
          </CraftSelect>
        </template>
      </div>

      <!-- Structure settings -->
      <template v-if="isStructure">
        <hr class="my-6" />
        <div class="grid gap-3">
          <CraftInput
            :label="t('Max Levels')"
            :help-text="
              t('The maximum number of levels this section can have.')
            "
            id="maxLevels"
            name="maxLevels"
            v-model="form.maxLevels"
            :disabled="readOnly"
            inputmode="numeric"
            size="5"
            :error="errors?.maxLevels"
          />

          <CraftSelect
            :label="t('Default {type} Placement', {type: t('Entry')})"
            :help-text="
              t(
                'Where new {type} should be placed by default in the structure.',
                {type: t('entries')}
              )
            "
            id="defaultPlacement"
            name="defaultPlacement"
            v-model="form.defaultPlacement"
            :disabled="readOnly"
          >
            <select slot="input">
              <option
                v-for="option in placementOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </CraftSelect>
        </div>
      </template>

      <hr class="my-6" />

      <!-- Preview Targets -->
      <div class="grid gap-3">
        <div>
          <h3 class="font-bold text-sm">{{ t('Preview Targets') }}</h3>
          <p class="text-sm text-neutral-500 mb-2">
            {{
              t(
                'Locations that should be available for previewing entries in this section.'
              )
            }}
          </p>

          <PreviewTargetsTable
            v-model="form.previewTargets"
            :disabled="readOnly"
          />
        </div>
      </div>

      <hr class="my-6" />

      <!-- Max Authors -->
      <div class="grid gap-3">
        <CraftInput
          :label="t('Max Authors')"
          :help-text="
            t(
              'The maximum number of authors that entries in this section can have.'
            )
          "
          id="maxAuthors"
          name="maxAuthors"
          v-model="form.maxAuthors"
          :disabled="readOnly"
          inputmode="numeric"
          maxlength="5"
          :error="errors?.maxAuthors"
        />
      </div>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
