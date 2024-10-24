<script>
  /* global Craft */

  export default {
    props: {
      rating: {
        type: Number,
        default: 0,
      },
      max: {
        type: Number,
        default: 5,
      },
      size: {
        type: String,
        validator(value) {
          return ['sm', 'md', 'lg', 'xl'].includes(value);
        },
      },
    },
    computed: {
      iconSize() {
        switch (this.size) {
          case 'sm':
            return 'tw-w-4 tw-h-4';
          case 'lg':
            return 'tw-w-8 tw-h-8';
          case 'xl':
            return 'tw-w-12 tw-h-12';
          default:
            return 'tw-w-6 tw-h-6';
        }
      },

      screenReaderText() {
        return Craft.t('app', 'Rating: {rating} out of {max} stars', {
          rating: this.rating,
          max: this.max,
        });
      },

      percentage() {
        return Number((this.rating / this.max) * 100).toFixed(0);
      },

      percentageString() {
        return `${this.percentage}%`;
      },

      clipPathId() {
        return `clip-path-${Math.random().toString(36).substring(2, 9)}`;
      },
      clipPathStyle() {
        return `url(#${this.clipPathId})`;
      },
    },
  };
</script>

<template>
  <div class="rating-stars tw-relative tw-inline-flex">
    <svg class="tw-absolute tw-h-full tw-w-full tw-inset-0">
      <defs>
        <clipPath :id="clipPathId">
          <rect :width="percentageString" height="100%" />
        </clipPath>
      </defs>
    </svg>
    <div
      v-for="state in ['idle', 'active']"
      :key="state"
      class="tw-flex tw-flex-nowrap"
      :class="{
        [`stars stars--${state}`]: true,
      }"
    >
      <div
        v-for="i in max"
        :key="i"
        :class="{
          [iconSize]: true,
          'tw-text-yellow-400 tw-dark:text-yellow-600': state === 'active',
          'tw-text-gray-100 tw-dark:text-gray-700': state === 'idle',
        }"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="tw-currentColor"
        >
          <path
            fill-rule="evenodd"
            d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
    </div>
    <span class="tw-sr-only">{{ screenReaderText }}</span>
  </div>
</template>

<style scoped lang="scss">
  path {
    @apply tw-stroke-gray-300;
    stroke-width: 1px;
  }

  .rating-stars {
    position: relative;
    display: inline-flex;
  }

  .rating-stars__mask {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
  }

  .stars {
    display: flex;
    flex-wrap: nowrap;
  }

  .stars {
    color: rgb(243, 244, 246);
  }

  .star {
    width: 1rem;
    height: 1rem;
  }

  .stars--active {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    clip-path: v-bind(clipPathStyle);
    color: #facc15;

    path {
      stroke: #eab308;
    }
  }

  svg {
    fill: currentColor;
  }

  @media (prefers-color-scheme: dark) {
    path {
      stroke: theme('colors.gray.500');
    }

    .stars--active {
      path {
        stroke: theme('colors.yellow.500');
      }
    }
  }
</style>
