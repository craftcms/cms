<template>
    <transition name="modal">
        <div class="modal-mask">
            <div class="modal-overlay" @click="$router.go(-1)"></div>
            <div class="modal-wrapper">
                <div class="modal-container">

                    <div class="modal-header">
                        <slot name="header"></slot>
                    </div>

                    <div class="modal-body">
                        <cart></cart>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
    import Cart from './Cart.vue'
    export default {
        name: 'cartModal',
        components: {
            Cart
        },
    }
</script>

<style scoped>
    .modal-mask {
        position: fixed;
        z-index: 9998;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: table;
        transition: opacity .2s ease;
    }

    .modal-overlay {
        background-color: rgba(255, 255, 255, .75);
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
    }

    .modal-wrapper {
        display: table-cell;
        vertical-align: middle;
    }

    .modal-container {
        position: relative;
        z-index: 1;
        width: 900px;

        margin: 0px auto;
        background-color: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
        transition: all 1.5s ease;
        font-family: Helvetica, Arial, sans-serif;
    }

    .modal-header h3 {
        margin-top: 0;
        color: #42b983;
    }

    .modal-body {
        padding: 24px;
    }

    .modal-default-button {
        float: right;
    }

    /*
     * The following styles are auto-applied to elements with
     * transition="modal" when their visibility is toggled
     * by Vue.js.
     *
     * You can easily play with the modal transition by editing
     * these styles.
     */

    .modal-enter {
        opacity: 0;
    }

    .modal-leave-active {
        opacity: 0;
    }

    /*
        .modal-enter .modal-container,
        .modal-leave-active .modal-container {
            -webkit-transform: scale(1.1);
            transform: scale(1.1);
        }
    */

</style>