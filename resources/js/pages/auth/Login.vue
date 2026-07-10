<script setup lang="ts">
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import '@/modules/auth/components/login/login-form.js';

  const props = defineProps<{
    errors?: Record<string, string[]>;
    authFormData?: Record<string, string>;
    oauthLoginButtons?: string[];
    action: string;
  }>();

  const oauthLoginButtonsHtml = computed(
    () => props.oauthLoginButtons?.join('') ?? ''
  );

  const page = usePage<{
    username?: string;
    flash?: {
      success: string | null;
      error: string | null;
    };
  }>();
  const {general} = useCraftData();
</script>

<template>
  <AuthBase>
    <craft-login-form
      :action="action"
      show-reset-password
      show-remember-me
      :username="page.props.username"
      :initial-error="page.props.flash?.error ?? ''"
      :use-email-as-username="general.useEmailAsUsername ? '' : null"
    >
      <div
        class="grid gap-1 pt-1"
        v-if="oauthLoginButtonsHtml"
        slot="alternative-methods"
        v-html="oauthLoginButtonsHtml"
      ></div>
    </craft-login-form>
  </AuthBase>
</template>
