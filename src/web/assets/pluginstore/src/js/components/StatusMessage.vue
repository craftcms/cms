<template>
    <div>
        <div id="graphic" class="spinner big" :class="{ error: error }"></div>
        <div id="status">{{ message }}</div>
    </div>
</template>

<script>
    export default {

        props: ['message', 'error']

    }
</script>