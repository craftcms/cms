<script setup lang="ts">
  import ElementEditPage from '@/modules/elements/components/ElementEditPage.vue';

  // The shared edit payload comes from the ElementEditPage pipeline; only the
  // User-specific keys (UserEditViewModel) remain props.
  const props = defineProps<{
    userId: number | null;
    redirectUrl: string | null;
  }>();

  // What `users/save-user` resolves the account from. Everything else — the
  // native fields (username, email, full name, photo) and any custom fields —
  // comes from the compiled field layout.
  const saveData = () => ({
    userId: props.userId,
  });
</script>

<template>
  <ElementEditPage :save-data="saveData" />
</template>
