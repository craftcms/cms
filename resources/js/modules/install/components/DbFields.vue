<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import Callout from '@/common/components/Callout.vue';
  import {useFocusField} from '@/common/composables/useFocusField';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputPassword from '@craftcms/ui/vue/CraftInputPassword.vue';
  import Select from '@/common/form/Select.vue';

  type DbDriver = 'mysql' | 'mariadb' | 'pgsql' | 'sqlite';
  type DbFormData = {
    driver: DbDriver;
    host?: string;
    port?: number;
    database?: string;
    username?: string;
    password?: string;
    prefix?: string;
  };
  type DbDefaults = {
    host?: string;
    port?: string;
    database: string;
    username?: string;
    prefix?: string;
  };

  const emit = defineEmits<{
    (e: 'update:modelValue', value: DbFormData): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue: DbFormData;
      defaults: Record<DbDriver, DbDefaults>;
      errors?: Record<string, string>;
    }>(),
    {errors: () => ({})}
  );

  const model = computed({
    get() {
      return props.modelValue;
    },
    set(value) {
      emit('update:modelValue', value);
    },
  });

  const selectedDriver = computed(() => model.value.driver);
  const currentDefaults = computed(() => props.defaults[selectedDriver.value]);
  const isSqlite = computed(() => selectedDriver.value === 'sqlite');

  const options = [
    {value: 'mysql', label: 'MySQL'},
    {value: 'mariadb', label: 'MariaDB'},
    {value: 'pgsql', label: 'PostgreSQL'},
    {value: 'sqlite', label: 'SQLite'},
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
    <div :class="isSqlite ? 'col-span-3' : 'col-span-2'">
      <Select
        :label="t('Driver')"
        name="driver"
        id="db-driver"
        v-model="model.driver"
        ref="db-driver"
        :options="options"
        :error="errors?.driver"
      />
    </div>

    <div class="col-span-2" v-if="!isSqlite">
      <CraftInput
        :label="t('Host')"
        name="host"
        id="db-host"
        v-model="model.host"
        :placeholder="currentDefaults.host ?? undefined"
        :error="errors?.host"
      />
    </div>

    <div v-if="!isSqlite">
      <CraftInput
        :label="t('Port')"
        name="port"
        id="db-port"
        v-model="model.port"
        size="7"
        :placeholder="currentDefaults.port ?? undefined"
        :error="errors?.port"
      />
    </div>

    <ul class="error-list col-span-5" v-if="errors?.server">
      <li>{{ errors?.server }}</li>
    </ul>
  </div>

  <div class="grid grid-cols-2 gap-2" v-if="!isSqlite">
    <div>
      <CraftInput
        :label="t('Username')"
        name="username"
        id="db-username"
        v-model="model.username"
        :placeholder="currentDefaults.username ?? undefined"
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
    <div :class="isSqlite ? 'col-span-3' : 'col-span-2'">
      <CraftInput
        :label="isSqlite ? t('Database File Path') : t('Database Name')"
        name="database"
        id="db-database"
        v-model="model.database"
        :placeholder="currentDefaults.database"
        :error="errors?.database"
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
