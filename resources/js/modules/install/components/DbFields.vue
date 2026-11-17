<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftInputPassword from '@craftcms/ui/vue/CraftInputPassword.vue';
  import Select from '@/common/form/Select.vue';

  type DbDriver = 'mysql' | 'mariadb' | 'pgsql' | 'sqlite';
  type DbDefaults = {
    host?: string;
    port?: string;
    database: string;
    username?: string;
    prefix?: string;
  };

  const props = withDefaults(
    defineProps<{
      defaults: Record<DbDriver, DbDefaults>;
      errors?: Record<string, string>;
    }>(),
    {errors: () => ({})}
  );

  const driver = defineModel<DbDriver>('driver', {required: true});
  const host = defineModel<string>('host');
  const port = defineModel<number | string>('port');
  const database = defineModel<string>('database');
  const username = defineModel<string>('username');
  const password = defineModel<string>('password');
  const prefix = defineModel<string>('prefix');

  const currentDefaults = computed(() => props.defaults[driver.value]);
  const isSqlite = computed(() => driver.value === 'sqlite');

  const options = [
    {value: 'mysql', label: 'MySQL'},
    {value: 'mariadb', label: 'MariaDB'},
    {value: 'pgsql', label: 'PostgreSQL'},
    {value: 'sqlite', label: 'SQLite'},
  ];
</script>

<template>
  <craft-callout variant="danger" v-if="errors['*']">
    <p>{{ errors['*'] }}</p>
  </craft-callout>

  <div class="grid grid-cols-5 gap-2">
    <div :class="isSqlite ? 'col-span-3' : 'col-span-2'">
      <Select
        :label="t('Driver')"
        name="driver"
        id="db-driver"
        v-model="driver"
        :options="options"
        :error="errors?.driver"
      />
    </div>

    <div class="col-span-2" v-if="!isSqlite">
      <CraftInput
        :label="t('Host')"
        name="host"
        id="db-host"
        v-model="host"
        :placeholder="currentDefaults.host ?? undefined"
        :error="errors?.host"
      />
    </div>

    <div v-if="!isSqlite">
      <CraftInput
        :label="t('Port')"
        name="port"
        id="db-port"
        v-model="port"
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
        v-model="username"
        :placeholder="currentDefaults.username ?? undefined"
        :error="errors?.username"
      />
    </div>

    <div>
      <CraftInputPassword
        :label="t('Password')"
        name="password"
        id="db-password"
        v-model="password"
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
        v-model="database"
        :placeholder="currentDefaults.database"
        :error="errors?.database"
      />
    </div>

    <div>
      <CraftInput
        :label="t('Prefix')"
        name="prefix"
        id="db-prefix"
        v-model="prefix"
        maxlength="5"
        size="7"
        :error="errors?.prefix"
      />
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
