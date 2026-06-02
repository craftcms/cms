<template>
  <div>
    <a
      href="#"
      class="category-selector-btn"
      @click.prevent="showCategorySelector = !showCategorySelector"
      >All categories</a
    >

    <div class="category-selector" :class="{hidden: !showCategorySelector}">
      <div
        class="category-selector-header"
        :class="{
          'tw-px-4 tw-py-2 tw-bg-gray-100 tw-border-b tw-border-solid tw-border-gray-200 tw-flex tw-items-center': true,
        }"
      >
        <button
          class="tw-px-1.5 tw-py-1 tw-flex tw-items-center tw-rounded tw-text-gray-500 hover:tw-text-blue-600"
          @click="showCategorySelector = false"
        >
          <c-icon class="tw-w-6 tw-h-6" icon="x" />
        </button>
      </div>

      <div class="category-selector-body">
        <nav-items @itemClick="showCategorySelector = false"></nav-items>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapState} from 'vuex';
  import NavItems from './NavItems';

  export default {
    components: {NavItems},
    data() {
      return {
        showCategorySelector: false,
      };
    },

    computed: {
      ...mapState({
        categories: (state) => state.pluginStore.categories,
        CraftEdition: (state) => state.craft.CraftEdition,
        CraftTeam: (state) => state.craft.CraftTeam,
        CraftPro: (state) => state.craft.CraftPro,
        CraftEnterprise: (state) => state.craft.CraftEnterprise,
        licensedEdition: (state) => state.craft.licensedEdition,
      }),
    },
  };
</script>

<style lang="scss" scoped>
  @import '@craftcms/sass/mixins';

  /* Category Selector Btn */

  .category-selector-btn {
    @apply tw-block tw-relative;
    @apply lg:tw-hidden;
    background: $grey050;
    border: 1px solid $hairlineColor;
    padding: 10px 20px;
    border-radius: 4px;
    color: $secondaryColor;

    &:before {
      @include icon;
      @apply tw-absolute tw-right-0;
      top: calc(50% - 10px);
      font-size: 16px;
      width: 43px;
      line-height: 20px;
      content: 'downangle';
    }

    &:hover {
      @apply tw-no-underline;
    }
  }

  /* Category Selector */

  .category-selector {
    @apply lg:tw-flex tw-flex-col tw-fixed tw-top-0 tw-left-0 tw-bg-white tw-z-20;
    @apply tw-hidden;
    width: 100vw;
    height: 100vh;
    box-sizing: border-box;

    .category-selector-body {
      @apply tw-overflow-auto tw-h-full tw-p-6;
      box-sizing: border-box;

      .nav-items {
        ul {
          li {
            &:first-child a {
              border-top: 0;
            }

            &:first-child:before {
              @apply tw-hidden;
            }

            &:before,
            &:after {
              left: 1rem;
              right: 1rem;
            }

            a {
              padding-left: 55px;

              img {
                left: 24px;
              }
            }
          }
        }
      }
    }
  }
</style>
