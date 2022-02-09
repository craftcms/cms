<template>
  <div class="tw-mb-4">
    <form @submit.prevent="search()">
      <div class="ps-search tw-relative tw-flex tw-w-full">
        <div class="tw-absolute tw-inset-y-0 tw-flex tw-items-center tw-pl-3 tw-z-10 tw-text-gray-500">
          <c-icon icon="search" />
        </div>
        <input
          type="text"
          class="tw-flex-1 tw-w-full tw-pl-9 tw-pr-3 tw-py-2 tw-rounded tw-border-solid tw-border-gray-200 tw-text-sm"
          id="searchQuery"
          v-model="searchQuery"
          :placeholder="'Search plugins'|t('app')"
          autocomplete="off"
        />
      </div>
    </form>
  </div>
</template>

<script>
export default {
  data() {
    return {
      searchQuery: '',
    }
  },

  methods: {
    search() {
      if (this.searchQuery) {
        this.$store.commit('app/updateSearchQuery', this.searchQuery)
        this.$router.push({path: '/search'})
      }
    }
  },
}
</script>

