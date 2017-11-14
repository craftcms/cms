<template>
	<textarea class="text fullwidth" type="text" :id="id" :placeholder="placeholder" autocomplete="off" :value="value" :cols="cols" @input="$emit('input', $event.target.value)"></textarea>
</template>

<script>
    export default {

        props: ['id', 'placeholder', 'value', 'cols'],

    }
</script>