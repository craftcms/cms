<script setup lang="ts">
  import {computed, ref} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import '@/modules/auth/components/login/login-form.js';
  import {useMessageOutlet} from '@/modules/messages/useMessages';
  import type {CpMessage} from '@/modules/messages';

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
    messages?: CpMessage[];
  }>();
  const {general} = useCraftData();

  // Sign-in errors the server targets at the form render beside its fields
  // rather than in the default message display.
  const loginError = ref(
    page.props.messages?.find(
      (message) => message.target === 'login' && message.type === 'error'
    )?.message ?? ''
  );

  useMessageOutlet('login', (message) => {
    loginError.value = message.message;
  });
</script>

<template>
  <AuthBase>
    <craft-login-form
      :action="action"
      show-reset-password
      show-remember-me
      :username="page.props.username"
      :initial-error="loginError"
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
