<template>
    <div>
        <a @click="openModal()">Active Trials ({{ activeTrialsTotal }})</a>
        <a @click="openModal()">Cart ({{ totalQuantity }})</a>

        <modal :show.sync="showModal" :on-close="closeModal">
            <div slot="body">
                <cart></cart>
            </div>
        </modal>
    </div>

</template>

<script>
    import Cart from './Cart.vue'
    import Modal from './Modal.vue'
    import { mapGetters } from 'vuex'

    export default {
        name: 'cartButton',

        components: {
            Cart,
            Modal,
        },

        data () {
            return {
                showModal: false,
            }
        },

        computed: {
            ...mapGetters({
                cartProducts: 'cartProducts',
                activeTrialProducts: 'activeTrialProducts',
            }),
            activeTrialsTotal() {
                return this.activeTrialProducts.length;
            },
            totalQuantity() {
                return this.cartProducts.length;
            }
        },

        methods: {
            openModal: function() {
                this.showModal = true;
            },
            closeModal: function() {
                this.showModal = false;
            }
        },

        created () {
            // this.$store.dispatch('getAllProducts')
        }
    }
</script>