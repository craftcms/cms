<template>
  <div class="tw-flex tw-items-center tw-self-end">
    <c-spinner v-if="loading" class="tw-mr-4" />

    <sort-menu-btn
      :attributes="sortMenuBtnAttributes"
      :value.sync="options"
    ></sort-menu-btn>
  </div>
</template>

<script>
  import SortMenuBtn from './SortMenuBtn';

  export default {
    props: ['loading', 'orderBy', 'direction'],

    components: {
      SortMenuBtn,
    },

    data() {
      return {
        selectedAttribute: null,
        selectedDirection: null,
        sortMenuBtnAttributes: null,
        options: {
          attribute: null,
          direction: null,
        },
      };
    },

    watch: {
      options() {
        this.$emit('update:orderBy', this.options.attribute);
        this.$emit('update:direction', this.options.direction);
        this.$emit('change');
      },
    },

    mounted() {
      this.options.attribute = this.orderBy;
      this.options.direction = this.direction;

      this.sortMenuBtnAttributes = {
        popularity: this.$options.filters.t('Popularity', 'app'),
        dateUpdated: this.$options.filters.t('Last Update', 'app'),
        name: this.$options.filters.t('Name', 'app'),
      };
    },
  };
</script>
