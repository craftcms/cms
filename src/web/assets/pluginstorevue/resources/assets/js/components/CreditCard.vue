<template>
    <div>
        <text-field label="Card Number" id="card-number" v-model="value.number"></text-field>
        <text-field label="Card Expiry" id="card-expiry" placeholder="MM/YY"  v-model="value.expiry"></text-field>
        <text-field label="Card CVC" id="card-cvc" placeholder="XXX"  v-model="value.cvc"></text-field>
    </div>
</template>

<script>
    import TextField from './fields/TextField';
    export default {
        name: 'creditCard',
        props: ['value'],
        components: {
            TextField,
        },
    }
</script>