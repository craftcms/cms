<script setup lang="ts">
  import {type InertiaLinkProps, Link} from '@inertiajs/vue3';
  import {type Component, computed, defineComponent} from 'vue';

  const props = withDefaults(
    defineProps<
      InertiaLinkProps & {
        as?: string | Component;
        variant?: 'default' | 'primary' | 'danger';
        size?: 'zero' | 'small' | 'medium' | 'large';
        appearance?: 'button' | 'inline';
        icon?: string;
        block?: boolean;
        inertia?: boolean;
      }
    >(),
    {
      variant: 'default',
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
      'cp-link--default': props.variant === 'default',
      'cp-link--primary': props.variant === 'primary',
      'cp-link--danger': props.variant === 'danger',
    };
  });

  const hrefString = computed(() => {
    if (typeof props.href === 'string') {
      return props.href;
    }

    return props.href?.url;
  });
</script>

<template>
  <template v-if="inertia">
    <Link :as="as" :href="href" :class="classes">
      <div class="flex gap-1 items-center">
        <template v-if="icon"><craft-icon :name="icon"></craft-icon></template>
        <slot></slot>
      </div>
    </Link>
  </template>
  <template v-else>
    <a :href="hrefString" :class="classes">
      <div class="flex gap-1 items-center">
        <template v-if="icon"><craft-icon :name="icon"></craft-icon></template>
        <slot></slot>
      </div>
    </a>
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

  .cp-link--default {
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

  .cp-link--primary {
    --c-color-fill-loud: var(--c-color-brand-fill-loud);
    --c-color-fill-normal: var(--c-color-brand-fill-normal);
    --c-color-fill-quiet: var(--c-color-brand-fill-quiet);
    --c-color-border-loud: var(--c-color-brand-border-loud);
    --c-color-border-normal: var(--c-color-brand-border-normal);
    --c-color-border-quiet: var(--c-color-brand-border-quiet);
    --c-color-on-loud: var(--c-color-brand-on-loud);
    --c-color-on-normal: var(--c-color-brand-on-normal);
    --c-color-on-quiet: var(--c-color-brand-on-quiet);
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
