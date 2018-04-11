import axios from 'axios'

export default {

    getDeveloper(developerId, cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/developer'), {
                params: {
                    developerId: developerId,
                },
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

}
