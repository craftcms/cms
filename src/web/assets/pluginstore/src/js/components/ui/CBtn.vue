<template>
  <component
    :is="component"
    class="c-btn truncate"
    :to="to"
    :href="href"
    :target="target"
    :type="computedType"
    :class="[
      {
        small,
        large,
        block,
        outline,
        loading,
        [kind]: true,
        'c-btn-icon': icon && !$slots.default,
        group: true,

        // Base
        'tw-inline-block tw-px-4 tw-py-2 tw-rounded-md': true,
        'tw-text-sm tw-leading-5 tw-no-underline hover:tw-no-underline': true,
        'tw-border tw-border-solid': true,
        'disabled:tw-opacity-50 disabled:tw-cursor-default': true,

        // Variants
        'tw-text-white': kind === 'primary' && !outline,
        'hover:tw-text-white': kind === 'primary' && !outline,
        'active:tw-text-white': kind === 'primary' && !outline,

        // Default
        'tw-text-black': kind === 'default',

        // Primary
        'tw-border-blue-600': kind === 'primary',
        'tw-bg-blue-600': kind === 'primary' && !outline,
        'hover:tw-bg-blue-700 hover:tw-border-blue-700 active:hover:tw-bg-blue-600 active:hover:tw-border-blue-600':
          kind === 'primary' && !outline,
        'active:tw-bg-blue-800 active:tw-border-blue-800':
          kind === 'primary' && !outline,
        'disabled:tw-bg-blue-600 disabled:tw-border-blue-600':
          kind === 'primary' && !outline,
        'tw-text-blue-600 hover:tw-bg-blue-600 active:tw-bg-blue-800':
          kind === 'primary' && outline,

        // Secondary
        'tw-border-gray-200 tw-text-blue-600': kind === 'secondary',
        'hover:tw-cursor-pointer hover:tw-bg-gray-50 hover:tw-border-gray-200':
          kind === 'secondary',
        'active:tw-cursor-pointer active:tw-bg-gray-100 active:tw-border-gray-300':
          kind === 'secondary',
        'tw-text-blue-600 tw-bg-white tw-shadow-gray-600/7':
          kind === 'secondary' && !outline,

        // Danger
        'tw-text-red-600 tw-bg-white tw-border-gray-200': kind === 'danger',
        'focus:tw-border-red-400 focus:tw-ring-red-500/30': kind === 'danger',
        'hover:tw-bg-red-500 hover:tw-text-white hover:tw-border-red-600':
          kind === 'danger',
        'active:tw-bg-red-600': kind === 'danger',
      },
    ]"
    v-bind="additionalAttributes"
    @click="$emit('click')"
  >
    <template v-if="loading">
      <c-spinner
        :animationClass="`border-${animationColor} group-hover:border-${animationColorHover}`"
      />
    </template>

    <div class="c-btn-content">
      <c-icon
        v-if="icon && icon.length > 0"
        class="tw-mr-1"
        :icon="icon"
        size="3"
      />
      <slot></slot>

      <c-icon
        class="tw-ml-1"
        v-if="trailingIcon && trailingIcon.length > 0"
        :icon="trailingIcon"
        size="3"
      />
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
        const attrs = {};

        if (this.disabled) {
          attrs.disabled = true;
        }

        return attrs;
      },

      component() {
        if (this.to !== null && this.to !== '') {
          return 'router-link';
        }

        if (this.href !== null && this.href !== '') {
          return 'a';
        }

        return 'button';
      },

      computedType() {
        if (this.to !== null || this.href !== null) {
          return null;
        }

        return this.type;
      },

      animationColor() {
        return this.kind === 'secondary'
          ? 'interactive'
          : !this.outline
          ? 'text-inverse'
          : 'interactive-' + this.kind;
      },

      animationColorHover() {
        return this.kind === 'secondary' ? 'interactive' : 'text-inverse';
      },
    },
  };
</script>

<style lang="scss">
  @import '@craftcms/sass/mixins';

  .c-btn,
  a.c-btn,
  button.c-btn {
    &:focus {
      @apply tw-outline-none tw-ring;
    }

    &.block {
      @apply tw-w-full;
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
        @include margin-right(1rem);
      }
    }

    .c-btn-content {
      @apply tw-flex tw-items-center tw-justify-center;
    }
  }
</style>
