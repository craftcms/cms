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
    import Cart from './components/Cart';

    export default {
        name: 'app',

        components: {
            Cart,
        },

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
            this.$store.dispatch('getCartState')
        },
    }
</script>

<style scoped>
    .cart-button {
        position: relative;
        float: right;
        top: -10px;
        z-index: 1000;
    }
</style>