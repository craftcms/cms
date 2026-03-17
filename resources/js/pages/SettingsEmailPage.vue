<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import {useEventListener} from '@vueuse/core';
  import {computed} from 'vue';
  import TransitionFade from '@/components/TransitionFade.vue';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import Input from '@/components/form/Input.vue';
  import type {SelectItem, SuggestionGroup} from '@/types';

  const props = defineProps<{
    readOnly?: boolean;
    emailConfig: {
      fromEmail?: string;
      fromName?: string;
      replyToEmail?: string;
      mailer?: string | null;
      template?: string | null;
      siteOverrides?: Record<
        string,
        {
          fromEmail?: string;
          fromName?: string;
          replyToEmail?: string;
          template?: string;
        }
      >;
    };
    mailerOptions: Array<{
      value: string | null;
      label: string;
    }>;
    envSuggestions?: Array<SuggestionGroup>;
    templateSuggestions?: Array<SelectItem>;
    sites: Array<{
      uid: string;
      name: string;
    }>;
    saveUrl: string;
    testUrl: string;
    defaultToEmail: string;
    flash?: Record<any, any>;
    errors: Record<any, any>;
  }>();

  const flash = computed(() => props.flash);
  const errors = computed(() => props.errors);
  const isMultiSite = computed(() => props.sites.length > 1);

  // Build initial site overrides with empty values for all sites
  const initialSiteOverrides: Record<string, Record<string, string>> = {};
  for (const site of props.sites) {
    const existing = props.emailConfig.siteOverrides?.[site.uid] ?? {};
    initialSiteOverrides[site.uid] = {
      fromEmail: existing.fromEmail ?? '',
      fromName: existing.fromName ?? '',
      replyToEmail: existing.replyToEmail ?? '',
      template: existing.template ?? '',
    };
  }

  const form = useForm({
    fromEmail: props.emailConfig.fromEmail ?? '',
    fromName: props.emailConfig.fromName ?? '',
    replyToEmail: props.emailConfig.replyToEmail ?? '',
    mailer: props.emailConfig.mailer ?? '',
    template: props.emailConfig.template ?? '',
    siteOverrides: initialSiteOverrides,
  });

  const testForm = useForm({
    to: props.defaultToEmail,
  });

  function handleMailerUpdate(event: CustomEvent) {
    const target = event.target as HTMLSelectElement & {modelValue: string};
    if (target) {
      form.mailer = target.modelValue;
    }
  }

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  function save() {
    form.clearErrors().post(props.saveUrl);
  }

  function sendTest() {
    testForm.clearErrors().post(props.testUrl, {
      onSuccess: () => {
        testForm.reset();
      },
    });
  }

  function getSiteName(siteUid: string): string {
    return props.sites.find((s) => s.uid === siteUid)?.name ?? siteUid;
  }
</script>

<template>
  <form @submit.prevent="save">
    <AppLayout :title="t('Email Settings')">
      <template #actions>
        <TransitionFade>
          <template v-if="form.recentlySuccessful && flash?.success">
            <div class="flex gap-1 items-center text-sm">
              <craft-icon
                name="circle-check"
                style="color: var(--c-color-success-fill-loud)"
              ></craft-icon>
              {{ flash.success }}
            </div>
          </template>
          <template v-if="form.hasErrors">
            <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
              <craft-icon
                name="triangle-exclamation"
                style="color: var(--c-color-danger-fill-loud)"
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
              <craft-icon
                name="chevron-down"
                :label="t('More actions')"
              ></craft-icon>
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

      <!-- Email Settings Form -->
      <div
        class="bg-white border border-neutral-border-quiet rounded-sm shadow-sm"
      >
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
                <li v-for="(error, key) in errors" :key="key">
                  {{ error }}
                </li>
              </ul>
            </craft-callout>
          </template>

          <craft-combobox
            :label="t('System Email Address')"
            :help-text="
              t('The email address Craft CMS will use when sending email.')
            "
            id="fromEmail"
            name="fromEmail"
            v-model="form.fromEmail"
            :has-feedback-for="errors?.fromEmail ? 'error' : ''"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
          >
            <template v-for="(group, idx) in envSuggestions" :key="idx">
              <craft-option
                v-for="suggestion in group.data"
                :key="suggestion.name"
                .choiceValue="suggestion.name"
                .hint="suggestion.hint"
                >{{ suggestion.name }}</craft-option
              >
            </template>
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                {{ t('This can be set to an environment variable.') }}
              </craft-callout>
            </div>
            <div slot="feedback">
              <ul class="error-list" v-if="errors?.fromEmail">
                <li>{{ errors.fromEmail }}</li>
              </ul>
            </div>
          </craft-combobox>

          <craft-combobox
            :label="t('Sender Name')"
            :help-text="
              t('The “From” name Craft CMS will use when sending email.')
            "
            id="fromName"
            name="fromName"
            v-model="form.fromName"
            :has-feedback-for="errors?.fromName ? 'error' : ''"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
          >
            <template v-for="(group, idx) in envSuggestions" :key="idx">
              <craft-option
                v-for="suggestion in group.data"
                :key="suggestion.name"
                .choiceValue="suggestion.name"
                .hint="suggestion.hint"
                >{{ suggestion.name }}</craft-option
              >
            </template>
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                {{ t('This can be set to an environment variable.') }}
              </craft-callout>
            </div>
            <div slot="feedback">
              <ul class="error-list" v-if="errors?.fromName">
                <li>{{ errors.fromName }}</li>
              </ul>
            </div>
          </craft-combobox>

          <craft-combobox
            :label="t('Reply-To Address')"
            :help-text="
              t(
                'The Reply-To email address Craft CMS should use when sending email.'
              )
            "
            id="replyToEmail"
            name="replyToEmail"
            v-model="form.replyToEmail"
            :has-feedback-for="errors?.replyToEmail ? 'error' : ''"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
          >
            <template v-for="(group, idx) in envSuggestions" :key="idx">
              <craft-option
                v-for="suggestion in group.data"
                :key="suggestion.name"
                .choiceValue="suggestion.name"
                .hint="suggestion.hint"
                >{{ suggestion.name }}</craft-option
              >
            </template>
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                {{ t('This can be set to an environment variable.') }}
              </craft-callout>
            </div>
            <div slot="feedback">
              <ul class="error-list" v-if="errors?.replyToEmail">
                <li>{{ errors.replyToEmail }}</li>
              </ul>
            </div>
          </craft-combobox>

          <craft-combobox
            :label="t('HTML Email Template')"
            :help-text="
              t(
                'The template Craft CMS will use for HTML emails. Leave blank to use the default template.'
              )
            "
            id="template"
            name="template"
            v-model="form.template"
            :has-feedback-for="errors?.template ? 'error' : ''"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
          >
            <template
              v-for="(group, idx) in templateSuggestions"
              :key="'tpl-' + idx"
            >
              <template v-if="group.type === 'optgroup'">
                <span class="group-label">{{ group.label }}</span>
                <craft-option
                  v-for="option in group.options"
                  :key="option.value"
                  .choiceValue="option.value"
                  .hint="option.data?.hint"
                  >{{ option.label }}</craft-option
                >
              </template>
            </template>
            <template
              v-for="(group, idx) in envSuggestions"
              :key="'env-' + idx"
            >
              <craft-option
                v-for="suggestion in group.data"
                :key="suggestion.name"
                .choiceValue="suggestion.name"
                .hint="suggestion.hint"
                >{{ suggestion.name }}</craft-option
              >
            </template>
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                {{ t('This can be set to an environment variable.') }}
              </craft-callout>
            </div>
            <div slot="feedback">
              <ul class="error-list" v-if="errors?.template">
                <li>{{ errors.template }}</li>
              </ul>
            </div>
          </craft-combobox>
        </div>

        <!-- Site Overrides -->
        <template v-if="isMultiSite">
          <hr />
          <div class="p-5">
            <h2 class="mb-2">{{ t('Site Overrides') }}</h2>
            <p class="text-sm text-neutral-text-quiet mb-4">
              {{
                t(
                  'Override the default email settings on a per-site basis. Blank values will use the defaults above.'
                )
              }}
            </p>

            <div class="overflow-x-auto">
              <table class="cp-table min-w-full">
                <thead>
                  <tr>
                    <th>{{ t('Site') }}</th>
                    <th>{{ t('System Email Address') }}</th>
                    <th>{{ t('Sender Name') }}</th>
                    <th>{{ t('Reply-To Address') }}</th>
                    <th>{{ t('Template') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="site in sites" :key="site.uid">
                    <th class="light whitespace-nowrap">
                      {{ getSiteName(site.uid) }}
                    </th>
                    <td>
                      <craft-input
                        :name="`siteOverrides[${site.uid}][fromEmail]`"
                        v-model="form.siteOverrides[site.uid].fromEmail"
                        :disabled="readOnly"
                        size="small"
                      ></craft-input>
                    </td>
                    <td>
                      <craft-input
                        :name="`siteOverrides[${site.uid}][fromName]`"
                        v-model="form.siteOverrides[site.uid].fromName"
                        :disabled="readOnly"
                        size="small"
                      ></craft-input>
                    </td>
                    <td>
                      <craft-input
                        :name="`siteOverrides[${site.uid}][replyToEmail]`"
                        v-model="form.siteOverrides[site.uid].replyToEmail"
                        :disabled="readOnly"
                        size="small"
                      ></craft-input>
                    </td>
                    <td>
                      <craft-input
                        :name="`siteOverrides[${site.uid}][template]`"
                        v-model="form.siteOverrides[site.uid].template"
                        :disabled="readOnly"
                        size="small"
                      ></craft-input>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <hr />

        <div class="p-5">
          <craft-combobox
            :label="t('Mailer')"
            :help-text="t('How should Craft CMS send the emails?')"
            id="mailer"
            name="mailer"
            .modelValue="form.mailer"
            @model-value-changed="handleMailerUpdate"
            :has-feedback-for="errors?.mailer ? 'error' : ''"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
          >
            <craft-option
              v-for="option in mailerOptions"
              :key="option.value ?? '__default__'"
              .choiceValue="option.value ?? ''"
            >
              {{ option.label }}
            </craft-option>
            <template
              v-for="(group, idx) in envSuggestions"
              :key="'mailer-env-' + idx"
            >
              <craft-option
                v-for="suggestion in group.data"
                :key="suggestion.name"
                .choiceValue="suggestion.name"
                .hint="suggestion.hint"
                >{{ suggestion.name }}</craft-option
              >
            </template>
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                {{ t('This can be set to an environment variable.') }}
              </craft-callout>
            </div>
            <div slot="feedback">
              <ul class="error-list" v-if="errors?.mailer">
                <li>{{ errors.mailer }}</li>
              </ul>
            </div>
          </craft-combobox>
        </div>
      </div>

      <!-- Test Email -->
      <div
        class="bg-white border border-neutral-border-quiet rounded-sm shadow-sm mt-6"
      >
        <div class="p-5">
          <h2 class="mb-3">{{ t('Send a test email') }}</h2>

          <div class="grid gap-3">
            <Input
              :label="t('To')"
              v-model="testForm.to"
              name="to"
              :error="testForm.errors.to"
            />

            <TransitionFade>
              <template v-if="testForm.recentlySuccessful && flash?.success">
                <craft-callout variant="success" icon="circle-check">
                  {{ flash.success }}
                </craft-callout>
              </template>
            </TransitionFade>

            <div class="buttons">
              <craft-button
                type="button"
                variant="primary"
                :loading="testForm.processing"
                @click="sendTest"
              >
                {{ t('Test') }}
              </craft-button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  </form>
</template>
