<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import {t, toHandle, toUriFormat} from '@craftcms/cp';
  import TransitionFade from '@/components/TransitionFade.vue';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import {store} from '@actions/Settings/SectionsController';
  import {useEventListener} from '@vueuse/core';
  import {computed} from 'vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputHandle from '@craftcms/cp/vue/CraftInputHandle.vue';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import EntryTypeSelect from '@/components/form/EntryTypeSelect.vue';
  import {useInputGenerator} from '@/composables/useInputGenerator';
  import type {
    SectionResource,
    SectionSiteSettingsData,
    SelectOption,
  } from '@/types';
  import SiteSettingsTable from '@/components/sections/SiteSettingsTable.vue';
  import PreviewTargetsTable from '@/components/sections/PreviewTargetsTable.vue';

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
    readOnly?: boolean;
    flash?: Record<any, any>;
    errors: Record<any, any> | null;
  }>();

  const form = useForm({
    sectionId: props.section.id,
    name: props.section.name ?? '',
    handle: props.section.handle ?? '',
    type: props.section.type,
    entryTypes: props.section.entryTypes.map((type) => type.id) ?? [],
    enableVersioning: props.section.enableVersioning,
    maxAuthors: props.section.maxAuthors ?? 1,
    maxLevels: props.section.maxLevels,
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

  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  function save() {
    form.clearErrors().submit(store());
  }
</script>

<template>
  <form @submit.prevent="save">
    <AppLayout :title="title" :debug="{form, $props}">
      <template #actions>
        <TransitionFade>
          <template v-if="form.recentlySuccessful && flash?.success">
            <div class="flex gap-1 items-center text-sm">
              <craft-icon
                name="circle-check"
                style="color: var(--c-color-success-bg-emphasis)"
              ></craft-icon>
              {{ flash.success }}
            </div>
          </template>
          <template v-if="form.hasErrors">
            <div class="flex gap-1 items-center text-sm">
              <craft-icon
                name="triangle-exclamation"
                style="color: var(--c-color-danger-bg-emphasis)"
              ></craft-icon>
              {{ t('Could not save settings') }}
            </div>
          </template>
        </TransitionFade>

        <craft-button-group v-if="!readOnly">
          <craft-button
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            {{ t('Save') }}
          </craft-button>
          <craft-action-menu>
            <craft-button slot="invoker" variant="primary" type="button" icon>
              <craft-icon name="chevron-down"></craft-icon>
            </craft-button>

            <div slot="content">
              <craft-action-item @click="save">
                {{ t('Save and continue editing') }}
                <craft-shortcut slot="suffix" class="ml-2">S</craft-shortcut>
              </craft-action-item>
            </div>
          </craft-action-menu>
        </craft-button-group>
      </template>

      <div class="bg-white border border-border-subtle rounded-sm shadow-sm">
        <template v-if="readOnly">
          <CalloutReadOnly />
        </template>

        <div class="grid gap-3 p-5">
          <!-- Error summary -->
          <template v-if="form.hasErrors">
            <craft-callout variant="danger" icon="triangle-exclamation">
              <div slot="title" class="font-bold">
                {{ t('Could not save settings') }}
              </div>
              <ul>
                <li v-for="(error, key) in form.errors" :key="key">
                  {{ error }}
                </li>
              </ul>
            </craft-callout>
          </template>

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
            :has-feedback-for="form.errors?.name ? 'error' : ''"
            required
            autofocus
          >
            <div slot="feedback">
              <ul class="error-list" v-if="form.errors?.name">
                <li>{{ form.errors.name }}</li>
              </ul>
            </div>
          </CraftInput>

          <!-- Handle -->
          <CraftInputHandle
            :label="t('Handle')"
            :help-text="t(`How you'll refer to this section in the templates.`)"
            id="handle"
            name="handle"
            v-model="form.handle"
            :disabled="readOnly"
            :has-feedback-for="form.errors?.handle ? 'error' : ''"
            required
            @change="handleGenerator.markDirty()"
          >
            <div slot="feedback">
              <ul class="error-list" v-if="form.errors?.handle">
                <li>{{ form.errors.handle }}</li>
              </ul>
            </div>
          </CraftInputHandle>

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
            :has-feedback-for="form.errors?.type ? 'error' : ''"
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

            <div slot="feedback">
              <ul class="error-list" v-if="form.errors?.type">
                <li>{{ form.errors.type }}</li>
              </ul>
            </div>
          </CraftSelect>
        </div>

        <hr />

        <!-- Entry Types -->
        <div class="grid gap-3 p-5">
          <div>
            <h3 class="font-bold text-sm">{{ t('Entry Types') }}</h3>
            <p class="text-sm text-neutral-500 mb-2">
              {{
                t(
                  'Choose the types of entries that can be included in this section.'
                )
              }}
            </p>
            <EntryTypeSelect :types="entryTypes" v-model="form.entryTypes" />
          </div>
        </div>

        <hr />

        <!-- Site Settings -->
        <div class="grid gap-6 p-5">
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
              @input="
                ({columnId, value}) => console.log('input', {columnId, value})
              "
            />
          </div>

          <!--<Pane appearance="raised" :padding="0">-->
          <!--  <table class="cp-table cp-table&#45;&#45;compact">-->
          <!--    <thead>-->
          <!--      <tr>-->
          <!--        <th>-->
          <!--          {{ t('Site') }}-->
          <!--        </th>-->
          <!--        <th v-if="isMultiSite">-->
          <!--          {{ t('Enabled') }}-->
          <!--        </th>-->
          <!--        <th v-if="isSingle" :title="t('Homepage')">-->
          <!--          <craft-icon name="home"></craft-icon>-->
          <!--        </th>-->
          <!--        <th v-if="isSingle">-->
          <!--          {{ t('URI') }}-->
          <!--        </th>-->
          <!--        <th v-if="isChannelOrStructure">-->
          <!--          {{ t('Entry URI Format') }}-->
          <!--        </th>-->
          <!--        <th v-if="!headlessMode">-->
          <!--          {{ t('Template') }}-->
          <!--        </th>-->
          <!--        <th v-if="isChannelOrStructure">-->
          <!--          {{ t('Default Status') }}-->
          <!--        </th>-->
          <!--      </tr>-->
          <!--    </thead>-->
          <!--    <tbody>-->
          <!--      <tr v-for="site in siteSettings" :key="site.handle">-->
          <!--        <td class="cell">{{ site.name }}</td>-->
          <!--        <td v-if="isMultiSite" class="cell">-->
          <!--          <CraftSwitch-->
          <!--            size="small"-->
          <!--            :model-value="!!form.sites[site.handle]?.enabled"-->
          <!--            @update:model-value="-->
          <!--              form.sites[site.handle].enabled = $event-->
          <!--                ? site.siteId-->
          <!--                : ''-->
          <!--            "-->
          <!--            label-sr-only-->
          <!--            :disabled="readOnly"-->
          <!--          />-->
          <!--        </td>-->
          <!--        <td v-if="isSingle" class="cell">-->
          <!--          <CraftCheckbox-->
          <!--            v-model="form.sites[site.handle].singleHomepage"-->
          <!--            :disabled="readOnly"-->
          <!--          />-->
          <!--        </td>-->
          <!--        <td v-if="isSingle" class="cell">-->
          <!--          <CraftInput-->
          <!--            type="text"-->
          <!--            v-model="form.sites[site.handle].singleUri"-->
          <!--            :disabled="-->
          <!--              readOnly || form.sites[site.handle]?.singleHomepage-->
          <!--            "-->
          <!--            :placeholder="-->
          <!--              t(`Leave blank if the entry doesn't have a URL`)-->
          <!--            "-->
          <!--          />-->
          <!--        </td>-->
          <!--        <td v-if="isChannelOrStructure" class="cell">-->
          <!--          <CraftInput-->
          <!--            type="text"-->
          <!--            v-model="form.sites[site.handle].uriFormat"-->
          <!--            :disabled="readOnly"-->
          <!--            :placeholder="t(`Leave blank if entries don't have URLs`)"-->
          <!--          />-->
          <!--        </td>-->
          <!--        <td v-if="!headlessMode" class="cell">-->
          <!--          <CraftInput-->
          <!--            type="text"-->
          <!--            v-model="form.sites[site.handle].template"-->
          <!--            :disabled="readOnly"-->
          <!--          />-->
          <!--        </td>-->
          <!--        <td v-if="isChannelOrStructure" class="cell">-->
          <!--          <CraftSwitch-->
          <!--            label-sr-only-->
          <!--            size="small"-->
          <!--            v-model="form.sites[site.handle].enabledByDefault"-->
          <!--            :disabled="readOnly"-->
          <!--          />-->
          <!--        </td>-->
          <!--      </tr>-->
          <!--    </tbody>-->
          <!--  </table>-->
          <!--</Pane>-->

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
          <hr />
          <div class="grid gap-3 p-5">
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
              :has-feedback-for="form.errors?.maxLevels ? 'error' : ''"
            >
              <div slot="feedback">
                <ul class="error-list" v-if="form.errors?.maxLevels">
                  <li>{{ form.errors.maxLevels }}</li>
                </ul>
              </div>
            </CraftInput>

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

        <hr />

        <!-- Preview Targets -->
        <div class="grid gap-3 p-5">
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
          <!--<table-->
          <!--  v-if="form.previewTargets.length > 0"-->
          <!--  class="tw:w-full tw:text-sm tw:border tw:border-border-subtle tw:rounded"-->
          <!--&gt;-->
          <!--  <thead>-->
          <!--    <tr class="tw:border-b tw:border-border-subtle tw:bg-neutral-50">-->
          <!--      <th class="tw:text-left tw:p-2 tw:font-medium">-->
          <!--        {{ t('Label') }}-->
          <!--      </th>-->
          <!--      <th class="tw:text-left tw:p-2 tw:font-medium">-->
          <!--        {{ t('URL Format') }}-->
          <!--      </th>-->
          <!--      <th class="tw:text-center tw:p-2 tw:font-medium tw:w-24">-->
          <!--        {{ t('Auto-refresh') }}-->
          <!--      </th>-->
          <!--      <th v-if="!readOnly" class="tw:w-10"></th>-->
          <!--    </tr>-->
          <!--  </thead>-->
          <!--  <tbody>-->
          <!--    <tr-->
          <!--      v-for="(target, index) in form.previewTargets"-->
          <!--      :key="index"-->
          <!--      class="tw:border-b tw:border-border-subtle last:tw:border-b-0"-->
          <!--    >-->
          <!--      <td class="tw:p-2">-->
          <!--        <input-->
          <!--          type="text"-->
          <!--          class="tw:w-full tw:border tw:border-border-subtle tw:rounded tw:px-2 tw:py-1 tw:text-sm"-->
          <!--          v-model="form.previewTargets[index].label"-->
          <!--          :disabled="readOnly"-->
          <!--        />-->
          <!--      </td>-->
          <!--      <td class="tw:p-2">-->
          <!--        <input-->
          <!--          type="text"-->
          <!--          class="tw:w-full tw:border tw:border-border-subtle tw:rounded tw:px-2 tw:py-1 tw:text-sm tw:font-mono"-->
          <!--          v-model="form.previewTargets[index].urlFormat"-->
          <!--          :disabled="readOnly"-->
          <!--        />-->
          <!--      </td>-->
          <!--      <td class="tw:text-center tw:p-2">-->
          <!--        <CraftCheckbox-->
          <!--          v-model="form.previewTargets[index].refresh"-->
          <!--          :disabled="readOnly"-->
          <!--        />-->
          <!--      </td>-->
          <!--      <td v-if="!readOnly" class="tw:p-2">-->
          <!--        <craft-button-->
          <!--          type="button"-->
          <!--          icon-->
          <!--          size="small"-->
          <!--          appearance="plain"-->
          <!--          @click="removePreviewTarget(index)"-->
          <!--        >-->
          <!--          <craft-icon name="xmark"></craft-icon>-->
          <!--        </craft-button>-->
          <!--      </td>-->
          <!--    </tr>-->
          <!--  </tbody>-->
          <!--</table>-->

          <!--<div v-if="!readOnly">-->
          <!--  <craft-button type="button" size="small" @click="addPreviewTarget">-->
          <!--    {{ t('Add a target') }}-->
          <!--  </craft-button>-->
          <!--</div>-->
        </div>

        <hr />

        <!-- Max Authors -->
        <div class="grid gap-3 p-5">
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
            :has-feedback-for="form.errors?.maxAuthors ? 'error' : ''"
          >
            <div slot="feedback">
              <ul class="error-list" v-if="form.errors?.maxAuthors">
                <li>{{ form.errors.maxAuthors }}</li>
              </ul>
            </div>
          </CraftInput>
        </div>
      </div>
    </AppLayout>
  </form>
</template>

<style scoped lang="scss"></style>
