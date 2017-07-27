<template>
    <div>
        <template v-if="loading">
            Loading…
        </template>
        <template v-else>
            <router-view></router-view>
        </template>
    </div>
</template>


<script>
    export default {
        name: 'app',

        data() {
            return {
                loading: true,
            }
        },

        created() {
            this.$store.dispatch('getPluginStoreData')
            this.$store.dispatch('getAllPlugins').then(() => {
                this.loading = false
            })
            this.$store.dispatch('getAllCategories')
            this.$store.dispatch('getStaffPicks')
            this.$store.dispatch('getCartState')
        }
    }
</script>