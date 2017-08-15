<template>

	<div class="field">
		<input :id="id" type="checkbox" class="checkbox">
		<label :id="id+'-label'" :for="id">{{ label }}</label>
	</div>

</template>

<script>
    export default {
        props: ['label', 'id', 'value'],
    }
</script>