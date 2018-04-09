<template>
    <div>

        <h2>Translations</h2>
        <p>{{ somePrice|currency }} per year for updates</p>
        <p>{{ "{price} per year for updates"|t('app', { price: $root.$options.filters.currency(somePrice) }) }}</p>
        <p>{{ "Go to {link}"|t('app', { link: '<a href="#">test</a>' }) }}</p>
        <p v-html="craftTranslation"></p>

        <h2>Modal</h2>

        <p><a @click="openModal()">Open Garnish Modal</a></p>

        <div class="hidden">
            <div ref="garnishmodalcontent" class="modal">
                <div class="body">
                    Hello World
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    export default {
        data() {
            return {
                somePrice: '99.00',
                modal: null,
            }
        },

        computed: {
            craftTranslation() {
                return Craft.t('app', 'Go to {link}', {link: '<a href="#">test</a>'})
            },
        },

        created() {
            this.$root.crumbs = [
                {
                    label: this.$options.filters.t("Plugin Store", 'app'),
                    path: '/',
                }
            ]
        },

        mounted() {
            this.modal = new Garnish.Modal(this.$refs.garnishmodalcontent, {
                autoShow: false,
                resizable: true
            })
        },

        methods: {
            openModal() {
                this.modal.show()
            },
        }
    }
</script>