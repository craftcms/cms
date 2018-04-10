<template>
	<div class="field first">
		<div v-if="label" class="heading">
			<label :id="id+'-label'" :for="id">{{ label }}</label>
		</div>
		<div class="input ltr">
			<textarea-input :id="id" :placeholder="placeholder" :value="value" :cols="cols" @input="$emit('input', $event)" />
		</div>
	</div>
</template>

<script>
    export default {

        props: ['label', 'id', 'placeholder', 'value', 'cols'],

        components: {
            TextareaInput: require('../inputs/TextareaInput'),
        },

    }
</script>