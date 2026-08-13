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
  import {computed, inject, type Component} from 'vue';
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
  const props = defineProps<
    ScreenProps & {
      /**
       * The full-page shell to render instead of `PageScreen` — e.g.
       * `EditorScreen`, which leaves the main region to the page.
       *
       * A context that provides its own shell still wins: inside a slideout
       * the page renders in the panel, not in a second set of CP chrome.
       */
      shell?: Component;
    }
  >();

  const slots = defineSlots<ScreenSlots>();

  const providedShell = inject(ScreenShellKey, null);
  const shell = computed(() => providedShell ?? props.shell ?? PageScreen);

  // `shell` is this component's own; passing it on would land in the shell's
  // attrs and, since the shells are multi-root, draw an attribute-fallthrough
  // warning.
  const shellProps = computed(() => {
    const {shell: _shell, ...rest} = props;

    return rest;
  });

  // Forward whatever the page actually passed. Enumerating `ScreenSlots`
  // instead would hand the shell a function for every slot, defeating the
  // `Boolean(slots.details)`-style checks the shells use to decide which
  // regions to show.
  const slotNames = computed(
    () => Object.keys(slots) as Array<keyof ScreenSlots>
  );
</script>

<template>
  <component :is="shell" v-bind="shellProps" @save="$emit('save', $event)">
    <template v-for="name in slotNames" #[name]="slotProps">
      <slot :name="name" v-bind="slotProps ?? {}"></slot>
    </template>
  </component>
</template>
