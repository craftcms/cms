<script setup lang="ts">
  import {type InertiaLinkProps, Link} from '@inertiajs/vue3';
  import {type Component, computed, defineComponent, h} from 'vue';

  defineOptions({inheritAttrs: false});

  type MouseHandler = (event: MouseEvent) => void;

  /**
   * Inertia's `<Link>` only skips SPA navigation for a modified click (a held
   * modifier key or a non-primary button) when its root element is a real
   * `<a>` tag. Since custom elements render as something else, its own check
   * never kicks in, so we replicate it here for any mouse handlers it hands
   * us before they reach the custom element.
   */
  function isModifiedClick(event: MouseEvent): boolean {
    return (
      event.altKey ||
      event.ctrlKey ||
      event.metaKey ||
      event.shiftKey ||
      event.button !== 0
    );
  }

  function guardMouseHandler(handler: unknown): MouseHandler | undefined {
    if (typeof handler !== 'function') {
      return undefined;
    }

    return (event: MouseEvent) => {
      if (!isModifiedClick(event)) {
        (handler as MouseHandler)(event);
      }
    };
  }

  const CustomElementLink = defineComponent({
    inheritAttrs: false,
    props: {
      tag: {
        type: String,
        required: true,
      },
    },
    setup(props, {attrs, slots}) {
      return () =>
        h(
          props.tag,
          {
            ...attrs,
            onClick: guardMouseHandler(attrs.onClick),
            onMousedown: guardMouseHandler(attrs.onMousedown),
            onMouseup: guardMouseHandler(attrs.onMouseup),
          },
          slots.default?.()
        );
    },
  });

  /**
   * Only what this adds. Everything Inertia's `Link` understands — `method`,
   * `data`, `headers`, `replace`, `preserveScroll`, `only`, the `on*`
   * callbacks, `prefetch` — falls through `$attrs` untouched, as do a custom
   * element's own attributes (`variant`, `size`, …). For a button, use
   * `CpButtonLink`, which types those.
   *
   * Declaring them here instead would break them two ways: Vue casts an
   * absent Boolean prop to `false` rather than `undefined`, so forwarding
   * them wholesale overrides Inertia's own defaults; and anything not
   * forwarded is swallowed as a prop and silently never arrives, which is how
   * an `onClick` handler ended up doing nothing at all.
   */
  const props = withDefaults(
    defineProps<{
      href: InertiaLinkProps['href'];
      as?: string | Component;
      icon?: string;
      block?: boolean;
      inertia?: boolean;
      underline?: boolean;
    }>(),
    {
      block: false,
      inertia: true,
    }
  );

  const classes = computed(() => {
    return {
      block: props.block,
      'inline-flex': !props.block,
      'cp-link': true,
      'cp-link--underline': props.underline,
    };
  });

  const hrefString = computed(() => {
    return props.href instanceof Object ? props.href.url : props.href;
  });

  const customElement = computed(() =>
    typeof props.as === 'string' && props.as.includes('-')
      ? props.as
      : undefined
  );

  const linkComponent = computed(() =>
    customElement.value ? CustomElementLink : props.as
  );

  const customElementAttributes = computed(() =>
    customElement.value
      ? {
          block: props.block || undefined,
          icon: props.icon,
        }
      : {}
  );
</script>

<template>
  <template v-if="inertia">
    <Link
      v-bind="{prefetch: 'click', ...$attrs, ...customElementAttributes}"
      :as="linkComponent"
      :tag="customElement"
      :href="href"
      :class="customElement ? undefined : classes"
    >
      <slot v-if="customElement"></slot>
      <div v-else class="flex gap-1 items-center">
        <template v-if="icon"><craft-icon :name="icon"></craft-icon></template>
        <slot></slot>
      </div>
    </Link>
  </template>
  <template v-else>
    <component
      v-bind="{...$attrs, ...customElementAttributes}"
      :is="as || 'a'"
      :href="hrefString"
      :class="customElement ? undefined : classes"
    >
      <slot v-if="customElement"></slot>
      <div v-else class="flex gap-1 items-center">
        <template v-if="icon"><craft-icon :name="icon"></craft-icon></template>
        <slot></slot>
      </div>
    </component>
  </template>
</template>

<style scoped lang="scss">
  .cp-link {
    gap: var(--c-spacing-sm);
    align-items: center;
    text-decoration: none;
  }

  .cp-link--underline {
    text-decoration: underline;

    &:hover {
      text-decoration: none;
    }
  }
</style>
