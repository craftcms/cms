<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {useFocusField} from '@/common/composables/useFocusField';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputPassword from '@craftcms/ui/vue/CraftInputPassword.vue';
  import {usePage} from '@inertiajs/vue3';

  const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'click:back'): void;
    (e: 'update:modelValue', value: any): void;
  }>();

  const props = withDefaults(
    defineProps<{
      modelValue?: {
        email?: string;
        username?: string;
        password?: string;
      };
      errors?: {
        email?: string;
        username?: string;
        password?: string;
      };
    }>(),
    {
      modelValue: () => ({
        email: '',
        username: '',
        password: '',
      }),
      errors: () => ({
        email: '',
        username: '',
        password: '',
      }),
    }
  );

  const page = usePage<{
    useEmailAsUsername: boolean;
  }>();
  const showUsername = computed(() => !page.props.useEmailAsUsername);

  const model = computed({
    get() {
      return props.modelValue;
    },
    set(value) {
      emit('update:modelValue', value);
    },
  });

  useFocusField('username-input');
</script>

<template>
  <CraftInput
    v-if="showUsername"
    :label="t('Username')"
    id="account-username"
    name="username"
    v-model="model.username"
    :error="errors?.username"
    maxlength="255"
    required
    autofocus
  />
  <CraftInput
    :label="t('Email')"
    id="account-email"
    name="email"
    v-model="model.email"
    maxlength="255"
    autocomplete="email"
    :error="errors?.email"
    required
    type="email"
  />
  <CraftInputPassword
    :label="t('Password')"
    id="account-password"
    name="password"
    v-model="model.password"
    :error="errors?.password"
    required
    autocomplete="new-password"
  />
</template>

<style scoped lang="scss"></style>
