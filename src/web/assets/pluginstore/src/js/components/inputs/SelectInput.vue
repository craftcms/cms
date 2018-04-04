<template>
	<div class="input" :class="{'errors': errors }">
		<div class="select">
			<select :value="value" @input="$emit('input', $event.target.value)">
				<option v-for="option in this.options" :value="option.value">{{ option.label }}</option>
			</select>
		</div>
	</div>
</template>


<script>
    export default {

        props: ['options', 'value', 'errors'],

    }
</script>
