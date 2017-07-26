<template>
    <div>

        <router-link to="/">Back to index</router-link>
        <h2>Translations</h2>
        <p>{{ somePrice|currency }} per year for updates</p>
        <p>{{ "{price} per year for updates"|t('app', { price: $root.$options.filters.currency(somePrice) }) }}</p>
        <p>{{ "Go to {link}"|t('app', { link: '<a href="#">test</a>' }) }}</p>
        <p v-html="craftTranslation"></p>

        <h2>Modal</h2>
        <p><a @click="showModal = true">Open Garnish Modal</a></p>

        <garnish-modal :showModal="showModal" @hide="showModal = false"></garnish-modal>
    </div>

</template>

<script>
    import GarnishModal from './components/GarnishModal.vue'

    export default {
        name: 'tests',
        components: {
            GarnishModal,
        },
        data() {
            return {
                somePrice: '99.00',
                modal: null,
                showModal: false,
            }
        },
        computed: {
            craftTranslation() {
                return Craft.t('app', 'Go to {link}', {link: '<a href="#">test</a>'});
            }
        },
    }
</script>