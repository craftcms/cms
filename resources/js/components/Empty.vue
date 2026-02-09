<script setup lang="ts">
  defineProps<{
    icon?: string;
    label?: string;
  }>();
</script>

<template>
  <div class="py-20">
    <div
      class="w-[60ch] mx-auto text-center grid gap-3 justify-items-center text-gray-500"
    >
      <craft-icon
        v-if="icon"
        :name="icon"
        style="font-size: calc(48rem / 16)"
      ></craft-icon>
      <slot name="content">
        <p class="text-lg">{{ label }}</p>
      </slot>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
