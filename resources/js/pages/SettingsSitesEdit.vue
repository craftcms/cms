<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import type {SelectOption, Site, SuggestionGroup} from '@/types';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import {t} from '@craftcms/cp';
  import LanguageSelectField from '@/components/LanguageSelectField.vue';
  import BooleanSelectField from '@/components/BooleanSelectField.vue';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {store} from '@actions/Settings/SitesController';
  import Combobox from '@/components/Combobox.vue';
  import {useEventListener} from '@vueuse/core';

  const props = defineProps<{
    title: string;
    crumbs: Array<any>; // @TODO
    readOnly?: boolean;
    site: Site;
    groupId?: number;
    nameSuggestions?: Array<SuggestionGroup>;
    languageOptions: Array<any>;
    baseUrlSuggestions: Array<SuggestionGroup>;
    booleanEnvOptions: Array<SelectOption>;
    groupOptions: Array<any>;
    flash?: Record<any, any>;
    errors: Record<any, any>;
    isMultisite: boolean;
  }>();

  const form = useForm({
    id: props.site.id ?? null,
    group: props.groupId,
    name: props.site.name,
    handle: props.site.handle,
    language: props.site.languageRaw,
    enabled: props.site.enabledRaw,
    hasUrls: props.site.hasUrls,
    primary: props.site.primary,
    baseUrl: props.site.baseUrlRaw,
  });

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  function save() {
    form
      .transform((values) => {
        console.log({values});
        return values;
      })
      .clearErrors()
      .submit(store({site: props.site.id}));
  }
</script>

<template>
  <form @submit.prevent="save">
    <AppLayout :title="title" :debug="{site, form}">
      <template #title-badge>
        <craft-callout
          :variant="site.enabled ? 'success' : 'danger'"
          size="small"
          class="flex items-center gap-1"
          inline
        >
          <craft-indicator
            slot="icon"
            :variant="site.enabled ? 'success' : 'danger'"
          ></craft-indicator>
          <span>{{ site.enabled ? t('Enabled') : t('Disabled') }}</span>
        </craft-callout>
        <craft-callout v-if="site.primary" size="small" inline>
          <span>{{ t('Primary') }}</span>
        </craft-callout>
      </template>

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
            <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
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
          <template v-if="form.hasErrors">
            <craft-callout variant="danger" icon="triangle-exclamation">
              <div slot="title" class="tw:font-bold">
                {{ t('Could not save settings') }}
              </div>
              <ul>
                <li v-for="(error, key) in errors">
                  {{ error }}
                </li>
              </ul>
            </craft-callout>
          </template>

          <input name="id" v-model="form.id" type="hidden" />
          <craft-select
            :label="t('Group')"
            :help-text="t('Which group should this site belong to?')"
            name="group"
            id="group"
            .modelValue="form.group"
            @model-value-changed="form.group = $event.target?.modelValue"
          >
            <select slot="input">
              <option
                v-for="option in groupOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>

            <ul class="error-list" v-if="errors?.driver" slot="feedback">
              <li v-for="error in errors?.driver">{{ error }}</li>
            </ul>

            <div slot="after" v-if="site.id && isMultisite">
              <craft-callout
                variant="danger"
                appearance="plain"
                class="p-0"
                icon="triangle-exclamation"
              >
                <span class="sr-only">{{ t('Warning:') }}</span>
                {{ t('Changing this may result in data loss.') }}
              </craft-callout>
            </div>
          </craft-select>
          <Combobox
            :label="t('Name')"
            id="name"
            name="name"
            v-model="form.name"
            :error="form.errors?.name"
            :disabled="readOnly"
            :options="nameSuggestions"
          >
            <template #after>
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                {{ t('This can begin with an environment variable.') }}
                <a
                  href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                  >{{ t('Learn more') }}</a
                >
              </craft-callout>
            </template>
          </Combobox>

          <craft-input-handle
            :label="t('Handle')"
            :help-text="t(`How you’ll refer to this site in the templates.`)"
            id="handle"
            name="handle"
            :has-feedback-for="form.errors?.handle ? 'error' : ''"
            v-model="form.handle"
          >
            <div slot="feedback">
              <ul class="error-list" v-if="form.errors?.handle">
                <li>{{ form.errors.handle }}</li>
              </ul>
            </div>
          </craft-input-handle>

          <LanguageSelectField
            name="language"
            id="site-language"
            :help-text="t('The language content in this site will use.')"
            v-model="form.language"
            :options="languageOptions"
            :error="form.errors?.language"
            :disabled="readOnly"
          />

          <template v-if="isMultisite || !site.id">
            <BooleanSelectField
              :label="t('Status')"
              id="enabled"
              name="enabled"
              v-model="form.enabled"
              :disabled="readOnly"
              :options="booleanEnvOptions"
              :error="form.errors?.enabled"
            >
              <template #after>
                <craft-callout
                  v-if="site.primary"
                  variant="warning"
                  appearance="plain"
                  class="p-0"
                  icon="lightbulb"
                >
                  {{ t('The primary site cannot be disabled.') }}
                </craft-callout>
              </template>
            </BooleanSelectField>
          </template>

          <template v-if="(isMultisite || !site.id) && !site.primary">
            <craft-switch
              :label="t('Make this the primary site')"
              :help-text="
                t(
                  'The primary site will be loaded by default on the front end.'
                )
              "
              :disabled="readOnly"
              :checked="form.primary"
              @checked-changed="form.primary = $event.target?.checked"
              v-if="!site.primary"
            >
            </craft-switch>
          </template>

          <craft-switch
            :label="t('This site has its own base URL')"
            id="has-urls"
            name="hasUrls"
            :disabled="readOnly"
            :checked="form.hasUrls"
            @checked-changed="form.hasUrls = $event.target?.checked"
          >
          </craft-switch>

          <template v-if="form.hasUrls">
            <Combobox
              :label="t('Base URL')"
              :help-text="t('The base URL for the site.')"
              id="base-url"
              name="baseUrl"
              v-model="form.baseUrl"
              :error="form.errors?.baseUrl"
              :disabled="readOnly"
              :options="baseUrlSuggestions"
            >
              <template #after>
                <craft-callout
                  variant="info"
                  appearance="plain"
                  class="p-0"
                  icon="lightbulb"
                >
                  {{ t('This can begin with an environment variable.') }}
                  <a
                    href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                    >{{ t('Learn more') }}</a
                  >
                </craft-callout>
              </template>
            </Combobox>
          </template>
        </div>
      </div>
    </AppLayout>
  </form>
</template>

<style scoped lang="scss"></style>
