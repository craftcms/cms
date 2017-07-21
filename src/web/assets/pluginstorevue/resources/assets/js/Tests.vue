<template>
    <div>
        <h2>Translations</h2>
        <p>{{ somePrice|currency }} per year for updates</p>
        <p>{{ "{price} per year for updates"|t('app', { price: $root.$options.filters.currency(somePrice) }) }}</p>
        <p>{{ "Go to {link}"|t('app', { link: '<a href="#">test</a>' }) }}</p>
        <p v-html="craftTranslation"></p>

        <h2>Modal</h2>
        <p><a @click="openCraftModal">Open Craft Modal</a></p>

        <div class="hidden">
            <div ref="modalcontent" class="modal">
                <div class="body">
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Asperiores, consequuntur culpa distinctio ducimus excepturi illo ipsa iste itaque laudantium mollitia odit perferendis placeat quo ullam veritatis? Architecto est ipsam itaque?
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    export default {
        name: 'tests',
        data() {
            return {
                somePrice: '99.00',
                modal: null,
            }
        },
        computed: {
            craftTranslation() {
                return Craft.t('app', 'Go to {link}', {link: '<a href="#">test</a>'});
            }
        },
        methods: {
            openCraftModal() {
                if(!this.modal) {
                    this.modal = new Garnish.Modal(this.$refs.modalcontent, {
                        autoShow: true,
                        resizable: true,
                    });
                } else {
                    this.modal.show();
                }
            }
        }
    }
</script>