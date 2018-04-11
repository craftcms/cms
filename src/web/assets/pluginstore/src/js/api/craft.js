import axios from 'axios'

export default {

    getCraftData(cb, cbError) {
        axios.get(Craft.getActionUrl('plugin-store/craft-data'))
            .then(response => {
                let craftData = response.data
                return cb(craftData)
            })
            .catch(response => {
                return cbError(response)
            })
    },

    tryEdition(edition) {
        return axios.post(Craft.getActionUrl('app/try-edition'), 'edition=' + edition, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    }

}
