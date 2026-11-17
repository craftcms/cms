<script setup lang="ts">
  import {ref} from 'vue';
  import {t} from '@craftcms/ui';
  import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
  import AuthBase from '@/common/layouts/AuthBase.vue';

  const props = defineProps<{
    clientName: string;
    redirectUri: string | null;
    redirectHost: string | null;
    scopes: Array<{
      id: string;
      description: string;
    }>;
    accessOptions: Array<{
      value: string;
      label: string;
      description: string;
    }>;
    authToken: string;
    csrfToken: string;
    approveAction: string;
    denyAction: string;
  }>();

  const access = ref(props.accessOptions[0]?.value ?? '');
</script>

<template>
  <AuthBase :title="t('Authorize “{client}”', {client: clientName})">
    <craft-pane
      class="authorization"
      :label="
        t('“{client}” wants to access your account', {client: clientName})
      "
    >
      <div class="authorization__content">
        <craft-radio-group
          v-if="accessOptions.length"
          name="access-choice"
          :label="t('Access')"
          .value="access"
          @change="access = $event.currentTarget.value"
        >
          <craft-radio
            v-for="option in accessOptions"
            :key="option.value"
            :label="option.label"
            :help-text="option.description"
            .value="option.value"
            .checked="option.value === access"
          ></craft-radio>
        </craft-radio-group>

        <template v-if="scopes.length">
          <p>{{ t('This application will be able to:') }}</p>

          <ul class="authorization__scopes">
            <li v-for="scope in scopes" :key="scope.id">
              {{ scope.description }}
            </li>
          </ul>
        </template>

        <p class="authorization__redirect">
          {{ t('Only authorize apps you trust.') }}
          <span
            v-if="redirectHost"
            v-html="
              t('You will be redirected to {host}.', {
                host: `<strong>${escapeHtml(redirectHost)}</strong>`,
              })
            "
          ></span>
        </p>
      </div>

      <form slot="secondary-action" :action="denyAction" method="post">
        <input type="hidden" name="_token" :value="csrfToken" />
        <input type="hidden" name="_method" value="DELETE" />
        <input type="hidden" name="auth_token" :value="authToken" />
        <craft-button type="submit">{{ t('Cancel') }}</craft-button>
      </form>

      <form slot="primary-action" :action="approveAction" method="post">
        <input type="hidden" name="_token" :value="csrfToken" />
        <input type="hidden" name="auth_token" :value="authToken" />
        <input
          v-if="accessOptions.length"
          type="hidden"
          name="access"
          :value="access"
        />
        <craft-button type="submit" variant="primary">
          {{ t('Authorize') }}
        </craft-button>
      </form>
    </craft-pane>
  </AuthBase>
</template>

<style scoped>
  .authorization {
    width: 100%;
  }

  .authorization::part(footer-actions) {
    flex-wrap: wrap;
  }

  .authorization__content {
    display: grid;
    min-width: 0;
    gap: var(--c-spacing-md);
  }

  .authorization__content p {
    margin: 0;
  }

  .authorization__scopes {
    display: grid;
    gap: var(--c-spacing-xs);
    padding-inline-start: var(--c-spacing-xl);
    overflow-wrap: anywhere;
  }

  .authorization__redirect {
    overflow-wrap: anywhere;
  }
</style>
