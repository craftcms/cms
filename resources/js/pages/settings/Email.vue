<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import type {SelectItem, SuggestionGroup} from '@/common/types';
  import SiteOverridesTable from '@/modules/settings/components/email/SiteOverridesTable.vue';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import {store, test} from '@routes/cp/settings/email';
  import Pane from '@/common/components/Pane.vue';
  import InlineFlash from '@/common/components/InlineFlash.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {useAppLayout} from '@/common/composables/useAppLayout';

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
    redirectUrl?: string;
  }>();

  const isMultiSite = computed(() => props.sites.length > 1);

  // Build initial site overrides with empty values for all sites
  const initialSiteOverrides: Record<
    string,
    {
      uid: string;
      fromEmail: string;
      fromName: string;
      replyToEmail: string;
      template: string;
    }
  > = {};
  for (const site of props.sites) {
    const existing = props.emailConfig.siteOverrides?.[site.uid] ?? {};
    initialSiteOverrides[site.uid] = {
      uid: site.uid,
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

  const {save} = useSettingsSave(form, store);

  useAppLayout({title: t('Email Settings'), form, onSave: save});

  function sendTest() {
    testForm.clearErrors().submit(test(), {
      onSuccess: () => {
        testForm.reset();
      },
    });
  }
</script>

<template>
  <div class="grid gap-3">
    <!-- Email Settings Form -->
    <Pane appearance="raised">
      <div class="grid gap-3">
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
          :error="form.errors?.template"
          :disabled="readOnly"
          :require-option-match="false"
          show-all-on-empty
          :options="[...(templateSuggestions ?? []), ...(envSuggestions ?? [])]"
          :callouts="['envVars']"
        />
      </div>

      <!-- Site Overrides -->
      <template v-if="isMultiSite">
        <hr class="my-6" />
        <div>
          <div class="mb-4">
            <h2 class="text-base">{{ t('Site Overrides') }}</h2>
            <p class="text-sm text-neutral-text-quiet">
              {{
                t(
                  'Override the default email settings on a per-site basis. Blank values will use the defaults above.'
                )
              }}
            </p>
          </div>

          <SiteOverridesTable v-model="form.siteOverrides" :sites="sites" />
        </div>
      </template>

      <hr class="my-6" />

      <div>
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
          :options="[
            ...mailerOptions.map((o) => ({...o, value: o.value ?? ''})),
            ...(envSuggestions ?? []),
          ]"
          :callouts="['envVars']"
        />
      </div>
    </Pane>

    <!-- Test Email -->
    <Pane appearance="raised">
      <h2 class="mb-3">{{ t('Send a test email') }}</h2>

      <div class="grid gap-3">
        <CraftInput
          :label="t('To')"
          v-model="testForm.to"
          name="to"
          :error="testForm.errors.to"
        />

        <div class="flex gap-2 items-center">
          <craft-button
            type="button"
            variant="primary"
            :loading="testForm.processing"
            @click="sendTest"
          >
            {{ t('Test') }}
          </craft-button>
          <InlineFlash
            :is-active="testForm.recentlySuccessful || testForm.hasErrors"
          />
        </div>
      </div>
    </Pane>
  </div>
</template>
