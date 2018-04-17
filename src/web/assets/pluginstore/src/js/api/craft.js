import axios from 'axios'

export default {

    /**
     * Get Craft data.
     */
    getCraftData(cb, cbError) {
        axios.get(Craft.getActionUrl('plugin-store/craft-data'))
            .then(response => {
                return cb(response)
            })
            .catch(response => {
                return cbError(response)
            })
    },

    /**
     * Try edition.
     */
    tryEdition(edition) {
        return axios.post(Craft.getActionUrl('app/try-edition'), 'edition=' + edition, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    }

}
