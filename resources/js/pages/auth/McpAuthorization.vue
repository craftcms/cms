<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AuthBase from '@/common/layouts/AuthBase.vue';

  defineProps<{
    clientName: string;
    scopes: Array<{
      id: string;
      description: string;
    }>;
    authToken: string;
    csrfToken: string;
    approveAction: string;
    denyAction: string;
  }>();
</script>

<template>
  <AuthBase :title="t('Authorize access')">
    <craft-pane
      class="authorization"
      :label="t('{name} wants to access your account', {name: clientName})"
    >
      <div class="authorization__content">
        <p>{{ t('This application will be able to:') }}</p>

        <ul class="authorization__scopes">
          <li v-for="scope in scopes" :key="scope.id">
            {{ scope.description }}
          </li>
        </ul>

        <p class="authorization__warning">
          {{ t('Only continue if you trust this application.') }}
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

  .authorization__warning {
    color: var(--c-text-quiet);
  }
</style>
