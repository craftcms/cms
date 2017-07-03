<template>
    <div>
        <a @click="openModal()">Cart</a>

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