<template>
  <div
    v-if="release"
    class="changelog-release">
    <div class="version">
      <a
        :href="'#' + release.version"
        class="anchor">
        <icon icon="link" />
      </a>
      <h2 :id="release.version">
        {{ "Version {version}"|t('app', {version: release.version}) }}</h2>
      <div class="date">{{ date }}</div>
      <div
        v-if="release.critical"
        class="critical">{{ 'Critical'|t('app') }}
      </div>
    </div>

    <div
      class="details readable"
      v-html="release.notes"></div>
  </div>
</template>

<script>
/* global Craft */

export default {
  props: ['release'],

  computed: {
    date() {
      return Craft.formatDate(this.release.date)
    }
  }
}
</script>

<style lang="scss">
@import "../../../../../../../packages/craftcms-sass/mixins";

.changelog-release {
  @apply tw-pt-2 tw-pb-4 tw-border-b tw-border-gray-200 tw-border-solid;

  .version {
    @apply tw-relative;

    .anchor {
      @apply tw-absolute tw-text-white tw-p-1 tw-rounded-full;
      @include left(-24px);
      top: 0px;
      font-size: 14px;
      transform: rotate(45deg);

      &:hover {
        @apply tw-text-black;
      }
    }

    &:hover {
      .anchor {
        @apply tw-text-black;
      }
    }

    h2 {
      @apply tw-mt-6 tw-mb-2;
    }

    .date {
      @apply tw-text-gray-600;
    }

    .critical {
      @apply tw-uppercase tw-text-red-600 tw-border tw-border-red-600 tw-border-solid tw-inline-block tw-px-1 tw-py-0 tw-rounded tw-text-sm tw-mt-2;
    }
  }

  .details {
    @apply tw-pt-6;

    h3 {
      @apply tw-mt-6 tw-mb-4;
    }

    ul {
      @apply tw-mb-4 tw-ml-6 tw-leading-normal;
      list-style-type: disc;

      li:not(:first-child) {
        @apply tw-mt-1;
      }
    }
  }
}

@media (min-width: 992px) {
  .changelog-release {
    @apply tw-flex;

    .version {
      @apply tw-w-full tw-max-w-xs;

      .anchor {
        top: 20px;
      }
    }

    .details {
      @apply tw-flex-1;
    }
  }
}

</style>
