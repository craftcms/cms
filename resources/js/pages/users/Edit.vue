<script setup lang="ts">
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';

  // The shared edit payload comes from the ElementEditor pipeline; only the
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
  <!-- The account nav comes from the `subnav` page prop, which the page shell
    renders as its secondary nav. -->
  <ElementEditor :save-data="saveData" />
</template>
