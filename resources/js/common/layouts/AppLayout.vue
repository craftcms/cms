<script setup lang="ts">
  /**
   * The CP screen layout.
   *
   * This is a dispatcher, not a shell: it renders whichever shell matches the
   * current context — `PageScreen` for a full page, `SlideoutScreen` when a
   * slideout panel provides one — and forwards its props and every slot
   * through unchanged. A page component therefore renders identically in both
   * contexts without knowing which it's in.
   *
   * The slot and prop contract lives in `screens/types.ts`.
   */
  import {computed, inject} from 'vue';
  import PageScreen from './screens/PageScreen.vue';
  import {ScreenShellKey} from '@/common/composables/screen';
  import type {FormSaveOptions} from '@/common/types';
  import type {ScreenProps, ScreenSlots} from './screens/types';

  /** @deprecated Prefer `ScreenProps` from `screens/types`. */
  export type AppLayoutProps = ScreenProps;

  defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  // Defaults belong to the shells, which each declare their own. Unset props
  // forward as `undefined`, which is exactly what `withDefaults` fills in.
  defineProps<ScreenProps>();

  const slots = defineSlots<ScreenSlots>();

  const providedShell = inject(ScreenShellKey, null);
  const shell = computed(() => providedShell ?? PageScreen);

  // Forward whatever the page actually passed. Enumerating `ScreenSlots`
  // instead would hand the shell a function for every slot, defeating the
  // `Boolean(slots.details)`-style checks the shells use to decide which
  // regions to show.
  const slotNames = computed(() =>
    Object.keys(slots).filter(
      (name): name is keyof ScreenSlots => name in slots
    )
  );
</script>

<template>
  <component :is="shell" v-bind="$props" @save="$emit('save', $event)">
    <template v-for="name in slotNames" #[name]="slotProps">
      <slot :name="name" v-bind="slotProps ?? {}"></slot>
    </template>
  </component>
</template>
