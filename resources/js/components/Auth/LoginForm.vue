<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import LoginController from '@actions/Auth/LoginController';
  import {useForm, usePage} from '@inertiajs/vue3';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputPassword from '@craftcms/cp/vue/CraftInputPassword.vue';
  import CraftCheckbox from '@craftcms/cp/vue/CraftCheckbox.vue';
  import {computed} from 'vue';
  import useCraftData from '@/composables/useCraftData';
  import {cpHumanizer} from '@craftcms/cp/utilities/format.ts.mjs';
  import FlashMessages from '@/components/FlashMessages.vue';

  const emit = defineEmits<{
    (e: 'change:view', view: 'login' | 'set-password'): void;
  }>();
  const props = withDefaults(
    defineProps<{
      showPasswordReset?: boolean;
      showRememberCheckbox?: boolean;
      staticEmail?: string | null;
      username?: string;
    }>(),
    {
      showPasswordReset: true,
      showRememberCheckbox: true,
      staticEmail: null,
      username: '',
    }
  );

  const page = usePage<{
    errors?: {
      loginName?: string;
      password?: string;
      rememberMe?: string;
    } | null;
    flash: {
      error: string;
    };
  }>();
  const {general} = useCraftData();
  const fieldErrors = computed(() => page.props.errors);

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

  const initialUsername = computed(() => {
    return props.staticEmail ?? props.username ?? '';
  });

  const form = useForm({
    loginName: initialUsername.value,
    password: '',
    rememberMe: false,
  });

  function handleSubmit() {
    form.clearErrors().submit(LoginController.attemptLogin(), {
      onSuccess: () => {
        form.reset('password');
      },
    });
  }

  const humanizedDuration = computed(() =>
    cpHumanizer(general.rememberedUserSessionDuration * 1000)
  );
</script>

<template>
  <form @submit.prevent="handleSubmit()">
    <div class="grid gap-3">
      <FlashMessages />
      <template v-if="staticEmail">
        <input type="hidden" name="username" :value="staticEmail" />
      </template>
      <template v-else>
        <CraftInput
          :label="usernameProps.label"
          :type="usernameProps.type"
          name="username"
          autocomplete="username"
          :autocapitalize="false"
          :required="true"
          v-model="form.loginName"
          :error="fieldErrors?.loginName"
        />
      </template>
      <div>
        <CraftInputPassword
          name="password"
          label="Password"
          v-model="form.password"
          :required="true"
          autocomplete="current-password"
          :error="fieldErrors?.password"
        />
        <template v-if="showPasswordReset">
          <div class="mt-2">
            <craft-button
              type="button"
              appearance="none"
              @click="emit('change:view', 'set-password')"
              >{{ t('Forgot password?') }}</craft-button
            >
          </div>
        </template>
      </div>

      <template
        v-if="showRememberCheckbox && general.rememberedUserSessionDuration"
      >
        <CraftCheckbox
          :label="
            t('Stay signed in for {duration}', {
              duration: humanizedDuration,
            })
          "
          v-model="form.rememberMe"
          name="rememberMe"
        />
      </template>
    </div>

    <div class="mt-4">
      <craft-button
        type="submit"
        variant="primary"
        :loading="form.processing"
        class="w-full"
        >{{ t('Sign in') }}</craft-button
      >
    </div>
  </form>
</template>

<style scoped lang="scss"></style>
