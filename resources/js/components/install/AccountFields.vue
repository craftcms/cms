<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed} from 'vue';
  import {useFocusField} from '@/composables/useFocusField';

  const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'click:back'): void;
    (e: 'update:modelValue', value: any): void;
  }>();

  const props = withDefaults(
    defineProps<{
      modelValue?: any;
      errors?: Record<string, string[]>;
      showUsername?: boolean;
    }>(),
    {showUsername: true, modelValue: () => ({}), errors: () => ({})}
  );

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
  <craft-input
    v-if="showUsername"
    :label="t('app', 'Username')"
    id="account-username"
    name="username"
    v-model="model.username"
    :has-feedback-for="errors?.username ? 'error' : ''"
    maxlength="255"
    ref="username-input"
  >
    <ul class="error-list" v-if="errors?.username" slot="feedback">
      <li v-for="error in errors?.username">{{ error }}</li>
    </ul>
  </craft-input>
  <craft-input
    :label="t('app', 'Email')"
    id="account-email"
    name="email"
    v-model="model.email"
    maxlength="255"
    autocomplete="email"
    :has-feedback-for="errors?.email ? 'error' : ''"
    type="email"
  >
    <ul class="error-list" v-if="errors?.email" slot="feedback">
      <li v-for="error in errors?.email">{{ error }}</li>
    </ul>
  </craft-input>
  <craft-input-password
    :label="t('app', 'Password')"
    id="account-password"
    name="password"
    v-model="model.password"
    :has-feedback-for="errors?.password ? 'error' : ''"
    autocomplete="new-password"
  >
    <ul class="error-list" v-if="errors?.password" slot="feedback">
      <li v-for="error in errors?.password">{{ error }}</li>
    </ul>
  </craft-input-password>
</template>

<style scoped lang="scss"></style>
