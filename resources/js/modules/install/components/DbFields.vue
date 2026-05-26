<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed, defineEmits, defineProps} from 'vue';
  import Callout from '@/common/components/Callout.vue';
  import {useFocusField} from '@/common/composables/useFocusField';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputPassword from '@craftcms/cp/vue/CraftInputPassword.vue';
  import Select from '@/common/form/Select.vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue?: any;
      errors?: Record<string, string>;
    }>(),
    {modelValue: () => ({}), errors: () => ({})}
  );

  const model = computed({
    get() {
      return props.modelValue;
    },
    set(value) {
      emit('update:modelValue', value);
    },
  });

  const options = [
    {value: 'mysql', label: 'MySQL'},
    {value: 'pgsql', label: 'PostgreSQL'},
  ];

  useFocusField('db-driver');
</script>

<template>
  <Callout variant="danger" v-if="errors && errors['*']">
    <ul>
      <li v-for="formError in errors['*']" :key="formError">
        {{ formError }}
      </li>
    </ul>
  </Callout>

  <div class="grid grid-cols-5 gap-2">
    <div class="col-span-2">
      <Select
        :label="t('Driver')"
        name="driver"
        id="db-driver"
        v-model="model.driver"
        ref="db-driver"
        :options="options"
        :error="errors?.drive"
      />
    </div>
    <div class="col-span-2">
      <CraftInput
        :label="t('Host')"
        name="host"
        id="db-host"
        v-model="model.host"
        placeholder="127.0.0.1"
        :error="errors?.host"
      />
    </div>
    <div>
      <CraftInput
        :label="t('Port')"
        name="port"
        id="db-port"
        v-model="model.port"
        size="7"
        :error="errors?.port"
      />
    </div>

    <ul class="error-list col-span-5" v-if="errors?.server">
      <li>{{ errors?.server }}</li>
    </ul>
  </div>

  <div class="grid grid-cols-2 gap-2">
    <div>
      <CraftInput
        :label="t('Username')"
        name="username"
        id="db-username"
        v-model="model.username"
        placeholder="root"
        :error="errors?.username"
      />
    </div>

    <div>
      <CraftInputPassword
        :label="t('Password')"
        name="password"
        id="db-password"
        v-model="model.password"
        :error="errors?.password"
      />
    </div>

    <ul class="error-list col-span-2" v-if="errors?.user">
      <li>{{ errors?.user }}</li>
    </ul>
  </div>

  <div class="grid grid-cols-4 gap-2">
    <div class="col-span-2">
      <CraftInput
        :label="t('Database Name')"
        name="name"
        id="db-database"
        v-model="model.database"
        :errors="errors?.database"
      />
    </div>

    <div>
      <CraftInput
        :label="t('Prefix')"
        name="prefix"
        id="db-prefix"
        v-model="model.prefix"
        maxlength="5"
        size="7"
        :error="errors?.prefix"
      />
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
