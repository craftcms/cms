<script setup lang="ts">
  import type {InertiaLinkProps} from '@inertiajs/vue3';
  import type {ButtonVariant} from '@craftcms/ui';
  import CpLink from './CpLink.vue';

  /**
   * A link that looks like a button: a `craft-button` with an `href`, so the
   * styles stay in its shadow DOM and it renders a real `<a>`, while `CpLink`
   * turns a click into an Inertia visit. Anything else `CpLink` or Inertia's
   * `Link` understands falls through `$attrs`.
   */
  defineOptions({inheritAttrs: false});

  const {
    href,
    variant,
    size,
    icon,
    target,
    inertia = true,
  } = defineProps<{
    href: InertiaLinkProps['href'];
    variant?: ButtonVariant;
    size?: 'zero' | 'small' | 'medium' | 'large';
    icon?: string;
    target?: string;
    inertia?: boolean;
  }>();
</script>

<template>
  <CpLink
    v-bind="{...$attrs, variant, size, target}"
    as="craft-button"
    :href="href"
    :icon="icon"
    :inertia="inertia"
  >
    <slot />
  </CpLink>
</template>
