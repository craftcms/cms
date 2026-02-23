<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed, defineEmits, defineProps} from 'vue';
  import Callout from '@/components/common/Callout/Callout.vue';
  import {useFocusField} from '@/composables/useFocusField';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue?: any;
      errors?: Record<string, string[]>;
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

  function handleUpdate(event: CustomEvent) {
    const target = event.target as HTMLSelectElement & {modelValue: string};
    if (target) {
      model.value[target.name] = target.modelValue;
    }
  }

  const options = [
    {value: 'mysql', label: 'MySQL'},
    {value: 'pgsql', label: 'PostgreSQL'},
  ];

  useFocusField('db-driver');
</script>

<template>
  <Callout variant="danger" v-if="errors && errors['*']">
    <ul>
      <li v-for="formError in errors['*']">
        {{ formError }}
      </li>
    </ul>
  </Callout>

  <div class="grid grid-cols-5 gap-2">
    <div class="col-span-2">
      <craft-select
        :label="t('Driver')"
        name="driver"
        id="db-driver"
        .modelValue="model.driver"
        @model-value-changed="handleUpdate"
        ref="db-driver"
      >
        <select slot="input">
          <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>

        <ul class="error-list" v-if="errors?.driver" slot="feedback">
          <li v-for="error in errors?.driver">{{ error }}</li>
        </ul>
      </craft-select>
    </div>
    <div class="col-span-2">
      <craft-input
        :label="t('Host')"
        name="host"
        id="db-host"
        v-model="model.host"
        placeholder="127.0.0.1"
      >
        <ul class="error-list" v-if="errors?.host" slot="feedback">
          <li v-for="error in errors?.host">{{ error }}</li>
        </ul>
      </craft-input>
    </div>
    <div>
      <craft-input
        :label="t('Port')"
        name="port"
        id="db-port"
        v-model="model.port"
        size="7"
      >
        <ul class="error-list" v-if="errors?.port" slot="feedback">
          <li v-for="error in errors?.port">{{ error }}</li>
        </ul>
      </craft-input>
    </div>

    <ul class="error-list col-span-5" v-if="errors?.server">
      <li v-for="formError in errors.server">
        {{ formError }}
      </li>
    </ul>
  </div>

  <div class="grid grid-cols-2 gap-2">
    <div>
      <craft-input
        :label="t('Username')"
        name="username"
        id="db-username"
        v-model="model.username"
        placeholder="root"
      >
        <ul class="error-list" v-if="errors?.username" slot="feedback">
          <li v-for="error in errors?.username">{{ error }}</li>
        </ul>
      </craft-input>
    </div>

    <div>
      <craft-input-password
        :label="t('Password')"
        name="password"
        id="db-password"
        v-model="model.password"
      >
        <ul class="error-list" v-if="errors?.password" slot="feedback">
          <li v-for="error in errors?.password">{{ error }}</li>
        </ul>
      </craft-input-password>
    </div>

    <ul class="error-list col-span-2" v-if="errors?.user">
      <li v-for="formError in errors.user">
        {{ formError }}
      </li>
    </ul>
  </div>

  <div class="grid grid-cols-4 gap-2">
    <div class="col-span-2">
      <craft-input
        :label="t('Database Name')"
        name="name"
        id="db-database"
        v-model="model.database"
      >
        <ul class="error-list" v-if="errors?.database" slot="feedback">
          <li v-for="error in errors?.database">{{ error }}</li>
        </ul>
      </craft-input>
    </div>

    <div>
      <craft-input
        :label="t('Prefix')"
        name="prefix"
        id="db-prefix"
        v-model="model.prefix"
        maxlength="5"
        size="7"
      >
        <ul class="error-list" v-if="errors?.prefix" slot="feedback">
          <li v-for="error in errors?.prefix">{{ error }}</li>
        </ul>
      </craft-input>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
