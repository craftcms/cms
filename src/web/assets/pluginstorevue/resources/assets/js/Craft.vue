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
                <p>{{ craftClient.price|currency }}</p>
                
                <div>
                    <a v-if="isInCart(craftClient)" class="btn submit disabled">Added to cart</a>
                    <a v-else  @click="buy(craftClient)" class="btn submit">Buy now</a>

                    <a v-if="isInTrial(craftClient)" class="btn disabled">Try for free</a>
                    <a v-else @click="addToActiveTrials(craftClient)" class="btn">Try for free</a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Pro</h2>
                <p class="light">For everything else.</p>
                <p>{{ craftPro.price|currency }}</p>

                <div>
                    <a v-if="isInCart(craftPro)" class="btn submit disabled">Added to cart</a>
                    <a v-else  @click="buy(craftPro)" class="btn submit">Buy now</a>

                    <a v-if="isInTrial(craftPro)" class="btn disabled">Try for free</a>
                    <a v-else @click="addToActiveTrials(craftPro)" class="btn">Try for free</a>
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
            ...mapGetters({
                isInTrial: 'isInTrial',
                isInCart: 'isInCart',
            }),
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
               'removeFromCart',
               'addToActiveTrials',
           ]),
            buy(plugin) {
                this.$store.dispatch('addToCart', plugin);
                this.$root.openGlobalModal('cart');
            },
        },

        created () {
            this.$root.showCrumbs = true;
            this.$root.pageTitle = 'Upgrade Craft CMS';
        },
    }
</script>