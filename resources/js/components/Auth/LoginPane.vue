<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import Passkeys from '@/components/Auth/Passkeys.vue';
  import SetPasswordForm from '@/components/Auth/SetPasswordForm.vue';
  import LoginForm from '@/components/Auth/LoginForm.vue';
  import {computed, ref} from 'vue';
  import {useUrlSearchParams} from '@vueuse/core';
  import useCraftData from '@/composables/useCraftData';
  import {router} from '@inertiajs/vue3';

  const props = withDefaults(
    defineProps<{
      errors?: Record<string, string[]>;
      authFormData?: Record<string, string>;
      showPasskeyBtn?: boolean;
    }>(),
    {showPasskeyBtn: true}
  );

  const params = useUrlSearchParams();
  const {general} = useCraftData();
  const view = ref<string>(params.view?.toString() ?? 'login');

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

  function setView(value: string) {
    router.reload({
      only: ['flash'],
      onSuccess: () => {
        params.view = value;
        view.value = value;
      },
    });
  }

  const showPasskeys = computed(
    () => props.showPasskeyBtn && browserSupportsWebAuthn()
  );
</script>

<template>
  <template v-if="view === 'login'">
    <div class="grid gap-3">
      <LoginForm
        :show-password-reset="true"
        :show-remember-checkbox="true"
        :username-props="usernameProps"
        @change:view="setView($event)"
      />

      <Passkeys v-if="showPasskeys" />
    </div>
  </template>
  <SetPasswordForm
    v-else-if="view === 'set-password'"
    :username-props="usernameProps"
    @change:view="setView($event)"
  />
</template>

<style scoped lang="scss"></style>
