<script setup lang="ts">
  import {type InertiaLinkProps, Link} from '@inertiajs/vue3';
  import {type Component, computed, defineComponent, h} from 'vue';

  defineOptions({inheritAttrs: false});

  const CustomElementLink = defineComponent({
    inheritAttrs: false,
    props: {
      tag: {
        type: String,
        required: true,
      },
    },
    setup(props, {attrs, slots}) {
      return () => h(props.tag, attrs, slots.default?.());
    },
  });

  const props = withDefaults(
    defineProps<
      InertiaLinkProps & {
        as?: string | Component;
        variant?: 'neutral' | 'accent' | 'danger';
        size?: 'zero' | 'small' | 'medium' | 'large';
        appearance?: 'button' | 'inline';
        icon?: string;
        block?: boolean;
        inertia?: boolean;
      }
    >(),
    {
      variant: 'neutral',
      appearance: 'inline',
      block: false,
      size: 'medium',
      inertia: true,
    }
  );

  const classes = computed(() => {
    return {
      block: props.block,
      'inline-flex': !props.block,
      'cp-link': true,
      'cp-link--zero': props.size === 'zero',
      'cp-link--small': props.size === 'small',
      'cp-link--medium': props.size === 'medium',
      'cp-link--large': props.size === 'large',
      'cp-link--inline': props.appearance === 'inline',
      'cp-link--button': props.appearance === 'button',
      'cp-link--default': props.variant === 'neutral',
      'cp-link--primary': props.variant === 'accent',
      'cp-link--danger': props.variant === 'danger',
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
      v-bind="{...$attrs, ...customElementAttributes}"
      :as="linkComponent"
      :tag="customElement"
      :href="href"
      :class="customElement ? undefined : classes"
      :variant="variant"
      :size="size"
      prefetch="click"
    >
      <slot v-if="customElement"></slot>
      <div v-else class="cp:flex cp:gap-1 cp:items-center">
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
      <div v-else class="cp:flex cp:gap-1 cp:items-center">
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

  /**
  These styles are mostly copied from craft-button. Eventually we may want to
  just provide the CSS to make an element look like a button and use those styles
  for both craft-button and this link. For now, we're keeping things a bit more
  locked down.
   */
  .cp-link--button {
    cursor: pointer;
    font: inherit;
    border-radius: var(--c-button-radius, var(--c-radius-sm));
    padding-inline: var(--c-button-spacing-inline, var(--c-spacing-md));
    padding-block: 0;
    width: auto;
    min-height: var(--c-button-height, var(--c-size-control-md));
    min-width: var(--c-button-width, var(--c-size-control-md));
    white-space: nowrap;

    /* Colorable styles */
    color: var(--c-color-on-loud, var(--c-color-neutral-on-loud));
    border-width: var(--c-button-border-width, 1px);
    border-style: var(--c-button-border-style, solid);
    border-color: var(
      --c-color-border-loud,
      var(--c-color-neutral-border-loud)
    );
    background-color: var(
      --c-color-fill-loud,
      var(--c-color-neutral-fill-loud)
    );

    @media (hover: hover) {
      :host(:hover) {
        background-color: color-mix(
          in oklab,
          var(--c-color-fill-loud, var(--c-button-default-fill)),
          var(--c-color-mix-hover)
        );
        color: var(--c-color-on-loud);
        border-color: var(--c-color-border-loud);
      }
    }

    &.cp-link--zero {
      min-width: 0;
      min-height: 0;
      padding-inline: 0;
    }

    &.cp-link--small {
      padding-inline: var(--c-spacing-sm);
      min-width: var(--c-size-control-sm);
      min-height: var(--c-size-control-sm);
      font-size: 0.9em;
    }

    &.cp-link--large {
      padding-inline: var(--c-spacing-lg);
      min-height: var(--c-size-control-lg);
      min-width: var(--c-size-control-lg);
    }
  }

  .cp-link--neutral {
    --c-color-fill-loud: var(--c-color-neutral-fill-loud);
    --c-color-fill-normal: var(--c-color-neutral-fill-normal);
    --c-color-fill-quiet: var(--c-color-neutral-fill-quiet);
    --c-color-border-loud: var(--c-color-neutral-border-loud);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-quiet: var(--c-color-neutral-border-quiet);
    --c-color-on-loud: var(--c-color-neutral-on-loud);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-quiet: var(--c-color-neutral-on-quiet);
  }

  .cp-link--accent {
    --c-color-fill-loud: var(--c-color-accent-fill-loud);
    --c-color-fill-normal: var(--c-color-accent-fill-normal);
    --c-color-fill-quiet: var(--c-color-accent-fill-quiet);
    --c-color-border-loud: var(--c-color-accent-border-loud);
    --c-color-border-normal: var(--c-color-accent-border-normal);
    --c-color-border-quiet: var(--c-color-accent-border-quiet);
    --c-color-on-loud: var(--c-color-accent-on-loud);
    --c-color-on-normal: var(--c-color-accent-on-normal);
    --c-color-on-quiet: var(--c-color-accent-on-quiet);
  }

  .cp-link--danger {
    --c-color-fill-loud: var(--c-color-danger-fill-loud);
    --c-color-fill-normal: var(--c-color-danger-fill-normal);
    --c-color-fill-quiet: var(--c-color-danger-fill-quiet);
    --c-color-border-loud: var(--c-color-danger-border-loud);
    --c-color-border-normal: var(--c-color-danger-border-normal);
    --c-color-border-quiet: var(--c-color-danger-border-quiet);
    --c-color-on-loud: var(--c-color-danger-on-loud);
    --c-color-on-normal: var(--c-color-danger-on-normal);
    --c-color-on-quiet: var(--c-color-danger-on-quiet);
  }
</style>
