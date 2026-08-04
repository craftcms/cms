<template>
  <div
    v-if="tablePagination"
    class="vue-admin-table-pagination flex pagination"
  >
    <div
      @click="loadPage('prev')"
      class="page-link prev-page"
      :class="[isOnFirstPage ? 'disabled' : '']"
      title="Previous Page"
    ></div>
    <div
      @click="loadPage('next')"
      class="page-link next-page"
      :class="[isOnLastPage ? 'disabled' : '']"
      title="Next Page"
    ></div>
    <div v-show="tablePagination" class="page-info">{{ paginationLabel }}</div>
  </div>
</template>

<script>
  /* global Craft */
  import PaginationMixin from 'vuetable-2/src/components/VuetablePaginationMixin';

  export default {
    name: 'AdminTablePagination',
    mixins: [PaginationMixin],
    props: {
      itemLabels: {
        type: Object,
        default: () => {
          return {
            singular: Craft.t('app', 'item'),
            plural: Craft.t('app', 'items'),
          };
        },
      },
    },
    computed: {
      paginationLabel() {
        return Craft.t(
          'app',
          '{first, number}-{last, number} of {total, number} {total, plural, =1{{item}} other{{items}}}',
          {
            first: this.tablePagination.from,
            last: this.tablePagination.to,
            total: this.tablePagination.total || 0,
            item: this.itemLabels.singular,
            items: this.itemLabels.plural,
          }
        );
      },
    },
  };
</script>
