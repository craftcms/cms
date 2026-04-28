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
  import SiteOverridesTable from '@/components/Settings/Email/SiteOverridesTable.vue';
  import CraftCombobox from '@/components/form/CraftCombobox.vue';
  import {store, test} from '@routes/cp/settings/email';

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

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  function save() {
    form.clearErrors().submit(store());
  }

  function sendTest() {
    testForm.clearErrors().submit(test(), {
      onSuccess: () => {
        testForm.reset();
      },
    });
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

          <CraftCombobox
            :label="t('System Email Address')"
            :help-text="
              t('The email address Craft CMS will use when sending email.')
            "
            id="fromEmail"
            name="fromEmail"
            v-model="form.fromEmail"
            :error="form.errors?.fromEmail"
            :options="envSuggestions"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
            :callouts="['envVars']"
          />

          <CraftCombobox
            :label="t('Sender Name')"
            :help-text="
              t('The “From” name Craft CMS will use when sending email.')
            "
            id="fromName"
            name="fromName"
            v-model="form.fromName"
            :error="form.errors?.fromName"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
            :options="envSuggestions"
            :callouts="['envVars']"
          />

          <CraftCombobox
            :label="t('Reply-To Address')"
            :help-text="
              t(
                'The Reply-To email address Craft CMS should use when sending email.'
              )
            "
            id="replyToEmail"
            name="replyToEmail"
            v-model="form.replyToEmail"
            :error="form.errors?.replyToEmail"
            :disabled="readOnly"
            :require-option-match="false"
            :options="envSuggestions"
            show-all-on-empty
            :callouts="['envVars']"
          />

          <CraftCombobox
            :label="t('HTML Email Template')"
            :help-text="
              t(
                'The template Craft CMS will use for HTML emails. Leave blank to use the default template.'
              )
            "
            id="template"
            name="template"
            v-model="form.template"
            :error="errors?.template"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
            :options="[...templateSuggestions, ...envSuggestions]"
            :callouts="['envVars']"
          />
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

            <SiteOverridesTable v-model="form.siteOverrides" :sites="sites" />
          </div>
        </template>

        <hr />

        <div class="p-5">
          <CraftCombobox
            :label="t('Mailer')"
            :help-text="t('How should Craft CMS send the emails?')"
            id="mailer"
            name="mailer"
            v-model="form.mailer"
            :error="form.errors?.mailer"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
            :options="[...mailerOptions, ...envSuggestions]"
            :callouts="['envVars']"
          />
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
