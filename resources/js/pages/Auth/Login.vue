<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AuthBase from '@/layout/AuthBase.vue';
  import LoginForm from '@/components/Auth/LoginForm.vue';
  import {computed, ref} from 'vue';
  import SetPasswordForm from '@/components/Auth/SetPasswordForm.vue';
  import useCraftData from '@/composables/useCraftData';
  import {useUrlSearchParams} from '@vueuse/core';
  import {router} from '@inertiajs/vue3';

  defineProps<{
    logo?: string;
    errors?: Record<string, string[]>;
    authFormData?: Record<string, string>;
  }>();

  const params = useUrlSearchParams();
  const {general} = useCraftData();
  const view = ref<string>(params.view?.toString() ?? 'login');

  function setView(value: string) {
    router.reload({
      only: ['flash'],
      onSuccess: () => {
        params.view = value;
        view.value = value;
      },
    });
  }

  const usernameProps = computed(() => {
    if (general.useEmailAsUsername) {
      return {
        label: t('Email'),
        type: 'email',
      };
    }

    return {
      label: t('Username or Email'),
      type: 'text',
    };
  });
</script>

<template>
  <AuthBase>
    <LoginForm
      :show-password-reset="true"
      :show-remember-checkbox="true"
      :username-props="usernameProps"
      v-if="view === 'login'"
      @change:view="setView($event)"
    />
    <SetPasswordForm
      v-else-if="view === 'set-password'"
      :username-props="usernameProps"
      @change:view="setView($event)"
    />
  </AuthBase>
</template>

<style scoped lang="scss"></style>
