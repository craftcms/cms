<template>
  <div
    class="c-spinner"
    :class="{
      'tw-inline-block': true,
      [size]: true,
    }"
  >
    <div
      class="animation"
      :class="[
        {
          'tw-border-gray-400': !animationClass,
          [animationClass]: animationClass,
        },
      ]"
    ></div>
  </div>
</template>

<script>
  export default {
    props: {
      animationClass: {
        type: String,
      },

      /**
       * 'base' or 'lg'
       */
      size: {
        type: String,
        default: 'base',
      },
    },
  };
</script>

<style lang="scss">
  .c-spinner {
    & > .animation {
      animation: rotator 0.7s linear infinite;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border-width: 2px;
      border-style: solid;
      border-top-color: transparent !important;
      border-left-color: transparent !important;
    }

    &.sm {
      & > .animation {
        width: 16px;
        height: 16px;
      }
    }

    &.lg {
      & > .animation {
        width: 32px;
        height: 32px;
        border-width: 3px;
      }
    }
  }

  @keyframes rotator {
    0% {
      transform: rotate(0);
    }

    to {
      transform: rotate(1turn);
    }
  }
</style>
