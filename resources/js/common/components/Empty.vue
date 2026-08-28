<script setup lang="ts">
  defineProps<{
    icon?: string;
    label?: string;
  }>();
</script>

<template>
  <div class="cp:py-20 cp:w-full">
    <div
      class="w-[60ch] cp:mx-auto cp:text-center cp:grid cp:gap-3 cp:justify-items-center cp:text-gray-500"
    >
      <slot name="graphic">
        <craft-icon
          v-if="icon"
          :name="icon"
          style="font-size: calc(48rem / 16)"
        ></craft-icon>
      </slot>
      <slot name="content">
        <p class="cp:text-lg">{{ label }}</p>
      </slot>

      <slot></slot>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
