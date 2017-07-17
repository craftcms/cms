<template>
    <div v-if="craftClient && craftPro">

        <div class="row">
            <div class="col-xs-12 col-sm-4">
                <h2>Personal</h2>
                <p class="light">For sites built by and for the developer.</p>
                <p>Free</p>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Client</h2>
                <p class="light">For sites built for clients with only one content manager.</p>
                <p>${{ craftClient.price }}</p>
                
                <div>
                    <a @click="buyPlugin(craftClient)" class="btn submit">Buy now</a>
                    <a @click="tryPlugin(craftClient)" class="btn">Try for free</a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Pro</h2>
                <p class="light">For everything else.</p>
                <p>${{ craftPro.price }}</p>
                <div>
                    <a @click="buyPlugin(craftPro)" class="btn submit">Buy now</a>
                    <a @click="tryPlugin(craftPro)" class="btn">Try for free</a>
                </div>
            </div>
        </div>

    </div>

</template>

<script>
    import { mapGetters, mapActions } from 'vuex'

    export default {

        name: 'craft',

        computed: {
            craftClient() {
                return this.$store.getters.getPluginById(152);
            },
            craftPro() {
                return this.$store.getters.getPluginById(153);
            },
        },

        methods: {
            ...mapActions([
               'addToCart',
               'removeFromCart'
           ]),
            buyPlugin(plugin) {
                this.$store.dispatch('addToCart', plugin);

                this.$root.$refs.cartButton.openModal();
            },
            tryPlugin(plugin) {
                this.$store.dispatch('addToActiveTrials', plugin);
                /*
                this.$emit('tryPlugin');*/
            },
        },

        created () {
            this.$root.showCrumbs = true;

            this.$root.pageTitle = 'Craft';
        },
    }
</script>