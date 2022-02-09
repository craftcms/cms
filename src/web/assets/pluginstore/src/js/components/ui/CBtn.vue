<template>
  <component
    :is="component"
    class="c-btn truncate"
    :to="to"
    :href="href"
    :target="target"
    :type="computedType"
    :class="[{
                small,
                large,
                block,
                outline,
                loading,
                [kind]: true,
                'c-btn-icon': icon && !$slots.default,
                'group': true,

                // Base
                'tw-inline-block tw-px-4 tw-py-2 tw-rounded-md': true,
                'tw-text-sm tw-leading-5 tw-no-underline': true,
                'tw-border tw-border-solid': true,
                'disabled:tw-opacity-50 disabled:tw-cursor-default': true,

                // Variants
                'tw-text-interactive-inverse': (kind === 'primary' || kind === 'danger') && !outline,
                'hover:tw-text-interactive-inverse': (kind === 'primary' || kind === 'danger') && !outline,
                'active:tw-text-interactive-inverse': (kind === 'primary' || kind === 'danger') && !outline,

                // Default
                'tw-text-interactive': kind === 'default',

                // Primary
                'tw-border-interactive-primary': kind === 'primary',
                'tw-bg-interactive-primary': kind === 'primary' && !outline,
                'hover:tw-bg-interactive-primary-hover hover:tw-border-interactive-primary-hover': kind === 'primary' && !outline,
                'active:tw-bg-interactive-primary-active active:tw-border-interactive-primary-active': kind === 'primary' && !outline,
                'disabled:tw-bg-interactive-primary disabled:tw-border-interactive-primary': kind === 'primary' && !outline,
                'tw-text-interactive-primary': kind === 'primary' && outline,
                'hover:tw-bg-interactive-primary': kind === 'primary' && outline,
                'active:tw-bg-interactive-primary-active': kind === 'primary' && outline,

                // Secondary
                'tw-border-interactive-secondary tw-text-interactive': kind === 'secondary',
                'hover:tw-cursor-pointer hover:tw-bg-interactive-secondary-hover hover:tw-border-interactive-secondary-hover hover:tw-no-underline': kind === 'secondary',
                'active:tw-cursor-pointer active:tw-bg-interactive-secondary-active active:tw-border-interactive-secondary-active': kind === 'secondary',
                'tw-bg-interactive-secondary': kind === 'secondary' && !outline,
                'tw-text-interactive': kind === 'secondary' && !outline,

                // Danger
                'tw-border-interactive-danger': kind === 'danger',
                'tw-bg-interactive-danger': kind === 'danger' && !outline,
                'hover:tw-bg-interactive-danger-hover hover:tw-border-interactive-danger-hover': kind === 'danger' && !outline,
                'active:tw-bg-interactive-danger-active active:tw-border-interactive-danger-active': kind === 'danger' && !outline,
                'disabled:tw-bg-interactive-danger disabled:tw-border-interactive-danger': kind === 'danger' && !outline,
                'tw-text-interactive-danger': kind === 'danger' && outline,
                'hover:tw-bg-interactive-danger': kind === 'danger' && outline,
                'active:tw-bg-interactive-danger-active': kind === 'danger' && outline
            }]"
    v-bind="additionalAttributes"
    @click="$emit('click')"
  >
    <template v-if="loading">
      <c-spinner :animationClass="`border-${animationColor} group-hover:border-${animationColorHover}`"/>
    </template>

    <div class="c-btn-content">
      <c-icon
        v-if="icon && icon.length > 0"
        :icon="icon"
        size="sm" />

      <slot></slot>

      <c-icon
        class="ml-1"
        v-if="trailingIcon && trailingIcon.length > 0"
        :icon="trailingIcon"
        size="sm" />
    </div>
  </component>
</template>

<script>
export default {
  name: 'Btn',

  props: {
    /**
     * 'button', 'submit', 'reset', or 'menu'
     */
    type: {
      type: String,
      default: 'button',
    },
    /**
     * 'default', 'primary', or 'danger'
     */
    kind: {
      type: String,
      default: 'secondary',
    },
    /**
     * Smaller version of button if set to `true`.
     */
    small: {
      type: Boolean,
      default: false,
    },
    /**
     * Larger version of button if set to `true`.
     */
    large: {
      type: Boolean,
      default: false,
    },
    /**
     * Block version of button if set to `true`.
     */
    block: {
      type: Boolean,
      default: false,
    },
    /**
     * Disabled version of button if set to `true`.
     */
    disabled: {
      type: Boolean,
      default: false,
    },
    /**
     * Outline version of button if set to `true`.
     */
    outline: {
      type: Boolean,
      default: false,
    },
    icon: {
      type: [String, Array],
      default: null,
    },
    trailingIcon: {
      type: String,
      default: null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    to: {
      type: String,
      default: null,
    },
    href: {
      type: String,
      default: null,
    },
    target: {
      type: String,
      default: null,
    },
  },

  computed: {
    additionalAttributes() {
      const attrs = {}

      if (this.disabled) {
        attrs.disabled = true
      }

      return attrs
    },

    component() {
      if (this.to !== null && this.to !== '') {
        return 'router-link'
      }

      if (this.href !== null && this.href !== '') {
        return 'a'
      }

      return 'button'
    },

    computedType() {
      if (this.to !== null || this.href !== null) {
        return null
      }

      return this.type
    },

    animationColor() {
      return (this.kind === 'secondary' ? 'interactive' : (!this.outline ? 'text-inverse' : 'interactive-' + this.kind))
    },

    animationColorHover() {
      return this.kind === 'secondary' ? 'interactive' : 'text-inverse'
    }
  }
}
</script>

<style lang="scss">
@import "@craftcms/sass/mixins";

.c-btn,
a.c-btn,
button.c-btn {
  &:focus {
    @apply tw-outline-none tw-ring;
  }

  &.block {
    @apply tw-w-full tw-my-2;
  }

  &.small {
    @apply tw-px-3 tw-leading-4;

    .c-icon {
      width: 12px;
      height: 12px;
    }
  }

  &.large {
    @apply tw-text-base tw-leading-6;
  }


  &.outline {
    .c-icon {
      @apply tw-fill-current;
    }
  }

  &.loading {
    @apply tw-relative;

    .c-spinner {
      @apply tw-absolute tw-inset-0 tw-flex tw-justify-center tw-items-center;
    }

    .c-btn-content {
      @apply tw-invisible;
    }
  }

  .c-icon {
    @apply tw-align-middle;
  }

  &:not(.c-btn-icon) {
    .c-icon {
      /*@include mr(1);*/
    }
  }

  .c-btn-content {
    @apply tw-inline-block;
  }
}
</style>
