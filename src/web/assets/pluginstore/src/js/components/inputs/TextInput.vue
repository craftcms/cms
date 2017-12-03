<template>
	<input class="text fullwidth" type="text" :id="id" :placeholder="placeholder" autocomplete="off" :value="value" @input="$emit('input', $event.target.value)">
</template>

<script>
    export default {

        props: ['id', 'placeholder', 'value'],

    }
</script>