<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import {store} from '@actions/Auth/VerifyEmailController';
  import '@/modules/auth/components/verify-email/verify-email-form.js';

  const props = defineProps<{
    uid: string;
    code: string;
  }>();

  const page = usePage<{
    errors?: Record<string, string>;
  }>();

  const action = computed(
    () => store(undefined, {query: {uid: props.uid, code: props.code}}).url
  );

  const initialError = computed(
    () => page.props.errors?.code ?? page.props.errors?.uid
  );
</script>

<template>
  <AuthBase :title="t('Verify your email address')">
    <craft-verify-email-form
      :action="action"
      :uid="uid"
      :code="code"
      :initial-error="initialError"
    ></craft-verify-email-form>
  </AuthBase>
</template>
