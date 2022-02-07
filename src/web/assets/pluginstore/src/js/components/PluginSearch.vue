<template>
    <div class="tw-mb-4">
        <form @submit.prevent="search()">
            <div class="ps-search">
                <icon icon="search" />
                <textbox
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
                if(this.searchQuery) {
                    this.$store.commit('app/updateSearchQuery', this.searchQuery)
                    this.$router.push({path: '/search'})
                }
            }
        },
    }
</script>

<style lang="scss">
    .ps-search {
        @apply tw-relative;

        .c-icon {
            @apply tw-absolute tw-z-10 tw-text-gray-600;
            top: 9px;
            left: 10px;
        }

        .c-textbox {
            input {
                padding-left: 32px;
            }
        }
    }
</style>
