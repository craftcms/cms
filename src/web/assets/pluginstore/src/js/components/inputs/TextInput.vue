<template>
	<input class="text" :class="{'error': error, 'fullwidth': !size }" type="text" :id="id" :placeholder="placeholder" autocomplete="off" :value="value" @input="$emit('input', $event.target.value)" :size="size">
</template>

<script>
    export default {

        props: ['id', 'placeholder', 'value', 'error', 'size'],

    }
</script>