<template>
    <div class="mb-4">
        <form @submit.prevent="search()">
            <div class="w-full texticon search icon clearable">
                <text-input
                        id="searchQuery"
                        v-model="searchQuery"
                        :placeholder="'Search plugins'|t('app')"
                        class="text"
                        ></text-input>
                <div class="clear" :class="{ hidden: searchQuery.length == 0 }" @click="searchQuery = ''" title="Clear"></div>
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
