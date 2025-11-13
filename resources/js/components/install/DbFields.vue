<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed, defineEmits, defineProps} from 'vue';

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
    console.log({value: target?.modelValue, name: target?.name});
    if (target) {
      model.value[target.name] = target.modelValue;
    }
  }

  const options = [
    {value: 'mysql', label: 'MySQL'},
    {value: 'pgsql', label: 'PostgreSQL'},
  ];
</script>

<template>
  <div class="tw:flex tw:gap-2">
    <div class="tw:grid tw:grid-cols-4 tw:gap-2">
      <div>
        <craft-select
          :label="t('app', 'Driver')"
          name="driver"
          id="db-driver"
          .modelValue="model.driver"
          @model-value-changed="handleUpdate"
        >
          <craft-option
            v-for="option in options"
            :key="option.value"
            .choiceValue="option.value"
          >
            {{ option.label }}
          </craft-option>
        </craft-select>
      </div>
      <div class="tw:col-span-2">
        <craft-input
          :label="t('app', 'Host')"
          name="host"
          id="db-host"
          v-model="model.host"
          placeholder="127.0.0.1"
        >
        </craft-input>
      </div>
      <div>
        <craft-input
          :label="t('app', 'Port')"
          name="port"
          id="db-port"
          v-model="model.port"
          size="7"
        >
        </craft-input>
      </div>
    </div>
  </div>

  <div class="tw:grid tw:grid-cols-2 tw:gap-2">
    <div>
      <craft-input
        :label="t('app', 'Username')"
        name="username"
        id="db-username"
        v-model="model.username"
        placeholder="root"
      >
      </craft-input>
    </div>

    <div>
      <craft-input-password
        :label="t('app', 'Password')"
        name="password"
        id="db-password"
        v-model="model.password"
      >
      </craft-input-password>
    </div>
  </div>

  <div class="tw:grid tw:grid-cols-4 tw:gap-2">
    <div class="tw:col-span-2">
      <craft-input
        :label="t('app', 'Database Name')"
        name="name"
        id="db-database"
        v-model="model.database"
      >
      </craft-input>
    </div>

    <div>
      <craft-input
        :label="t('app', 'Prefix')"
        name="prefix"
        id="db-prefix"
        v-model="model.prefix"
        maxlength="5"
        size="7"
      >
      </craft-input>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .db-fields {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: var(--c-spacing-md);
  }
</style>
