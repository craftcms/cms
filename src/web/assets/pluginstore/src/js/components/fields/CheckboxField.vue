<template>
	<div class="field">
		<input :id="id" type="checkbox" class="checkbox" value="1" :checked="checked" @change="change">
		<label :id="id+'-label'" :for="id">{{ label }}</label>
	</div>
</template>

<script>
    export default {

        props: ['label', 'id', 'value'],

        data() {
            return {
                checked: this.value,
            }
        },

        methods: {

            change($event) {
                this.checked = !this.checked
                this.$emit('input', this.checked)
            }

        },

    }
</script>