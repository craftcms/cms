<script setup lang="ts">
  import {computed, useId} from 'vue';
  import type {AvatarStackPerson} from './types';

  /**
   * Overlapping round avatars, each named in a tooltip, such as an entry's
   * authors. Past `max`, the rest are summed up as “+N”, with their names in
   * its tooltip.
   */
  const props = withDefaults(
    defineProps<{people: AvatarStackPerson[]; max?: number}>(),
    {max: 3}
  );

  const idPrefix = useId();

  const shown = computed(() =>
    props.people.length > props.max
      ? props.people.slice(0, Math.max(1, props.max - 1))
      : props.people
  );
  const hidden = computed(() => props.people.slice(shown.value.length));
</script>

<template>
  <span v-if="people.length" class="avatar-stack">
    <template v-for="(person, index) in shown" :key="person.id ?? index">
      <span
        :id="`${idPrefix}-${index}`"
        class="avatar-stack__avatar"
        role="img"
        tabindex="0"
        v-html="person.thumbHtml ?? ''"
      ></span>
      <craft-tooltip :for="`${idPrefix}-${index}`" invoker-relation="label">{{
        person.name
      }}</craft-tooltip>
    </template>
    <template v-if="hidden.length">
      <span
        :id="`${idPrefix}-more`"
        class="avatar-stack__avatar avatar-stack__more"
        role="img"
        tabindex="0"
        >+{{ hidden.length }}</span
      >
      <craft-tooltip :for="`${idPrefix}-more`" invoker-relation="label">{{
        hidden.map((person) => person.name).join(', ')
      }}</craft-tooltip>
    </template>
  </span>
</template>

<style scoped>
  .avatar-stack {
    --_avatar-size: var(--avatar-stack-size, calc(24rem / 16));
    --_avatar-overlap: calc(var(--_avatar-size) / 4);
    display: flex;
    align-items: center;
  }

  .avatar-stack__avatar {
    --c-thumb-icon-size: 100%;
    --c-thumbnail-size: 100%;
    flex: none;
    display: flex;
    overflow: hidden;
    inline-size: var(--_avatar-size);
    block-size: var(--_avatar-size);
    border-radius: var(--c-radius-full);
  }

  .avatar-stack__avatar ~ .avatar-stack__avatar {
    margin-inline-start: calc(var(--_avatar-overlap) * -1);
  }

  /* Each avatar but the last is cut away where the next overlaps it, with a
     gap, so the stack reads on any background. */
  .avatar-stack__avatar:has(~ .avatar-stack__avatar) {
    --_next-center: calc(var(--_avatar-size) * 1.5 - var(--_avatar-overlap));
    mask: radial-gradient(
      circle calc(var(--_avatar-size) / 2 + 2px) at var(--_next-center) 50%,
      transparent 99%,
      #000 100%
    );
  }

  .avatar-stack__avatar:has(~ .avatar-stack__avatar):dir(rtl) {
    mask: radial-gradient(
      circle calc(var(--_avatar-size) / 2 + 2px) at
        calc(100% - var(--_next-center)) 50%,
      transparent 99%,
      #000 100%
    );
  }

  .avatar-stack__more {
    align-items: center;
    justify-content: center;
    font-size: var(--c-text-xs);
    font-weight: 600;
    color: var(--c-text-quiet);
    background-color: var(--c-color-fill-quiet);
  }
</style>
