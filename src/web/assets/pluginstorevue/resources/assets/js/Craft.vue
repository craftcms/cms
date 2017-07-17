<template>
    <div>

        <div class="row">
            <div class="col-xs-12 col-sm-4">
                <h2>Personal</h2>
                <p>For sites built by and for the developer.</p>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Client</h2>
                <p>For sites built for clients with only one content manager.</p>
            </div>
            <div class="col-xs-12 col-sm-4">
                <h2>Pro</h2>
                <p>For everything else.</p>
            </div>
        </div>

    </div>

</template>

<script>

    export default {
        name: 'craft',

        created () {
            this.$root.showCrumbs = true;

            this.$root.pageTitle = 'Craft';
        },
    }
</script>