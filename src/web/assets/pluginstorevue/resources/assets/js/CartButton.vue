<template>
    <div>
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
                products: 'cartProducts',
            }),
            totalQuantity() {
                return this.products.reduce((totalQuantity, p) => {
                    return totalQuantity + p.quantity
                }, 0)
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
            this.$store.dispatch('getAllProducts')
        }
    }
</script>