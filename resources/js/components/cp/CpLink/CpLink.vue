<script setup lang="ts">
  import {type InertiaLinkProps, Link} from '@inertiajs/vue3';
  import type {Component} from 'vue';

  withDefaults(
    defineProps<
      InertiaLinkProps & {
        as?: string | Component;
        variant?: 'default' | 'primary' | 'danger';
        size?: 'zero' | 'small' | 'medium' | 'large';
        appearance?: 'button' | 'inline';
        block?: boolean;
      }
    >(),
    {
      as: 'a',
      variant: 'default',
      appearance: 'inline',
      block: false,
      size: 'medium',
    }
  );
</script>

<template>
  <Link
    :as="as"
    :href="href"
    :class="{
      block: block,
      'inline-flex': !block,
      'cp-link': true,
      'cp-link--zero': size === 'zero',
      'cp-link--small': size === 'small',
      'cp-link--medium': size === 'medium',
      'cp-link--large': size === 'large',
      'cp-link--inline': appearance === 'inline',
      'cp-link--button': appearance === 'button',
      'cp-link--default': variant === 'default',
      'cp-link--primary': variant === 'primary',
      'cp-link--danger': variant === 'danger',
    }"
    v-bind="$attrs"
  >
    <slot></slot>
  </Link>
</template>

<style scoped lang="scss">
  .cp-link {
    justify-content: center;
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
    border: 1px solid var(--c-button-border, var(--c-button-default-border));
    background-color: var(--c-button-bg, var(--c-button-default-bg));
    border-radius: var(--c-button-radius, var(--c-radius-sm));
    color: var(--c-button-fg, inherit);
    padding-inline: var(--c-button-spacing-inline, var(--c-spacing-md));
    padding-block: 0;
    width: auto;
    min-height: var(--c-button-height, var(--c-size-control-md));
    min-width: var(--c-button-width, var(--c-size-control-md));
    white-space: nowrap;

    @media (hover: hover) {
      &:hover {
        background-color: var(
          --c-button-bg-hover,
          var(--c-button-default-bg-hover)
        );
        border-color: var(
          --c-button-border-hover,
          var(--c-button-default-border-hover)
        );
        color: var(--c-button-fg-hover, var(--c-button-default-fg-hover));
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
    --c-button-bg: var(--c-button-default-bg);
    --c-button-bg-hover: var(--c-button-default-bg-hover);
    --c-button-border: var(--c-button-default-border);
    --c-button-border-hover: var(--c-button-default-border-hover);
    --c-button-fg: var(--c-button-default-fg);
    --c-button-fg-hover: var(--c-button-default-fg-hover);
  }

  .cp-link--primary {
    --c-button-bg: var(--c-button-primary-bg);
    --c-button-bg-hover: var(--c-button-primary-bg-hover);
    --c-button-border: var(--c-button-primary-border);
    --c-button-border-hover: var(--c-button-primary-border-hover);
    --c-button-fg: var(--c-button-primary-fg);
    --c-button-fg-hover: var(--c-button-primary-fg-hover);
  }

  .cp-link--danger {
    --c-button-bg: var(--c-button-danger-bg);
    --c-button-bg-hover: var(--c-button-danger-bg-hover);
    --c-button-border: var(--c-button-danger-border);
    --c-button-border-hover: var(--c-button-danger-border-hover);
    --c-button-fg: var(--c-button-danger-fg);
    --c-button-fg-hover: var(--c-button-danger-fg-hover);
  }
</style>
