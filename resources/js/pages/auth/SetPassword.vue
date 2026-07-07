<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {usePage} from '@inertiajs/vue3';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import {store} from '@actions/Auth/SetPasswordController';
  import '@/modules/auth/components/set-password/set-password-form.js';

  const props = defineProps<{
    uid: string;
    code: string;
    newUser: boolean;
  }>();

  const page = usePage<{
    errors?: Record<string, string>;
  }>();

  const title = computed(() =>
    props.newUser ? t('Set Your Password') : t('Set Your New Password')
  );

  const action = computed(
    () => store(undefined, {query: {uid: props.uid, code: props.code}}).url
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
    ></craft-set-password-form>
  </AuthBase>
</template>
