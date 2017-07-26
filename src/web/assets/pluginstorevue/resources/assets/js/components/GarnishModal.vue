<template>
    <div class="hidden">
        <div ref="garnishmodalcontent" class="modal">
            <div class="body">
                <slot name="body"></slot>
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
                autoShow: false,
                resizable: true,
                onHide() {
                    $this.$emit('update:showModal', false);
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

<style scoped>
    .modal {
        width: 900px;
        height: 600px;
    }
</style>