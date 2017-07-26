<template>
    <div class="hidden">
        <div ref="garnishmodalcontent" class="modal">
            <div class="body">
                Lorem ipsum dolor sit amet, consectetur adipisicing elit. Asperiores, consequuntur culpa distinctio ducimus excepturi illo ipsa iste itaque laudantium mollitia odit perferendis placeat quo ullam veritatis? Architecto est ipsam itaque?
            </div>
        </div>
    </div>
</template>


<script>
    export default {
        name: 'garnishModal',
        props: ['showModal'],
        data() {
            return {
                modal: null,
            };
        },
        mounted() {
            let $this = this;

            this.modal = new Garnish.Modal(this.$refs.garnishmodalcontent, {
                autoShow: true,
                resizable: true,
                onHide() {
                    $this.$emit('hide');
                }
            });
        },
        watch: {
            showModal(showModal) {
                if(showModal) {
                    this.modal.show();
                } else {
                    this.modal.hide();
                }
            }
        }
    }
</script>