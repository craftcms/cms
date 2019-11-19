<template>
  <div v-if="tablePagination" class="flex pagination">
    <div
            @click="loadPage('prev')"
            class="page-link" data-icon="leftangle"
            :class="[isOnFirstPage ? 'disabled' : '']"
            title="Previous Page"></div>
    <div
            @click="loadPage('next')"
            class="page-link" data-icon="rightangle"
            :class="[isOnLastPage ? 'disabled' : '']"
            title="Next Page"></div>
    <div v-show="tablePagination" class="page-info">{{tablePagination.from}}-{{tablePagination.to}} of {{tablePagination.total}}</div>
  </div>
</template>

<script>
    import PaginationMixin from 'vuetable-2/src/components/VuetablePaginationMixin'

    export default {
        name: 'AdminTablePagination',
        mixins: [PaginationMixin],
    }
</script>

<style scoped>

</style>