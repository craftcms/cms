<template>
	<div class="input ltr" :class="{'errors': errors, 'success': success }">
		<input
				type="text"
				class="text"
				autocomplete="off"
				:class="{'fullwidth': !size }"
				:id="id"
				:placeholder="placeholder"
				:value="value"
				:size="size"
				@input="$emit('input', $event.target.value)" />
	</div>
</template>

<script>
    export default {

        props: ['id', 'placeholder', 'value', 'size', 'errors', 'success'],

    }
</script>