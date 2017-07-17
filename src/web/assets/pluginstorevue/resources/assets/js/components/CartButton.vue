<template>
    <div>
        <a @click="openModal()">Cart ({{ totalQuantity }})</a>

        <modal :show.sync="showModal" :on-close="closeModal">
            <div slot="body">
                <cart @continueShopping="closeModal"></cart>
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
                cartPlugins: 'cartPlugins',
            }),
            totalQuantity() {
                return this.cartPlugins.length;
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
    }
</script>