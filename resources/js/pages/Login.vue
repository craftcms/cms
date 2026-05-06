<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import LoginForm from '@/components/Auth/LoginForm.vue';
  import Pane from '@/components/Pane.vue';

  defineProps<{
    logo?: string;
    errors?: Record<string, string[]>;
  }>();
  const {general, system} = useCraftData();
</script>

<template>
  <div class="cp-login">
    <div class="grid gap-3">
      <h1 class="flex justify-center" v-if="general.cpLogoUrl">
        <img
          :src="general.cpLogoUrl"
          :alt="system.name"
          width="288px"
          height="auto"
          class="inline-block"
        />
      </h1>

      <Pane appearance="raised" class="w-sm">
        <LoginForm :show-password-reset="true" :show-remember-checkbox="true" />
      </Pane>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-login {
    display: grid;
    width: 100vw;
    height: 100vh;
    place-items: center;
    gap: var(--c-spacing-md);
  }
</style>
