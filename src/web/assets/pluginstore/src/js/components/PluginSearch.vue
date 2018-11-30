<template>
    <div class="tw-mb-4">
        <form @submit.prevent="search()">
            <div class="tw-w-full texticon search icon clearable">
                <text-input
                        id="searchQuery"
                        v-model="searchQuery"
                        :placeholder="'Search plugins'|t('app')"
                        class="text"
                        ></text-input>
                <div class="clear" :class="{ hidden: searchQuery.length == 0 }" @click="searchQuery = ''" title="Clear"></div>
            </div>
        </form>

        <template v-if="sort">
            <sort-menu-btn :attributes="sortMenuBtnAttributes" :value="sort" @update:value="val => $emit('update:sort', val)"></sort-menu-btn>
        </template>
    </div>
</template>

<script>
    import SortMenuBtn from './SortMenuBtn'

    export default {

        props: ['sort'],

        components: {
            SortMenuBtn,
        },

        data() {
            return {
                searchQuery: '',
                selectedAttribute: null,
                selectedDirection: null,
                sortMenuBtnAttributes: null,
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

        mounted() {
            this.sortMenuBtnAttributes = {
                activeInstalls: this.$options.filters.t("Popularity", 'app'),
                lastUpdate: this.$options.filters.t("Last Update", 'app'),
                name: this.$options.filters.t("Name", 'app'),
                price: this.$options.filters.t("Price", 'app'),
            }
        }

    }
</script>
