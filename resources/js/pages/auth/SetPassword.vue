<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {usePage} from '@inertiajs/vue3';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import '@/modules/auth/components/set-password/set-password-form.js';

  const props = defineProps<{
    uid: string;
    code: string;
    newUser: boolean;
    action: string;
    passwordRules: string;
  }>();

  const page = usePage<{
    errors?: Record<string, string>;
  }>();

  const title = computed(() =>
    props.newUser ? t('Set Your Password') : t('Set Your New Password')
  );
</script>

<template>
  <AuthBase :title="title">
    <craft-set-password-form
      :action="action"
      :uid="uid"
      :code="code"
      :initial-error="page.props.errors?.newPassword"
      :new-user="newUser ? '' : null"
      :password-rules="passwordRules"
    ></craft-set-password-form>
  </AuthBase>
</template>
