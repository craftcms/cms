<template>
	<div>
		<div id="graphic" class="spinner big error"></div>
		<div id="status">{{ statusMessage }}</div>
	</div>

</template>

<script>
    export default {
		data() {
		    return {
		        statusMessage: 'Page not found.'
			}
		}
    };
</script>