import api from '../../api/cart';

/**
 * State
 */
const state = {
    searchQuery: '',
    showingScreenshotModal: false,
    screenshotModalImages: null,
}

/**
 * Getters
 */
const getters = {

}

/**
 * Actions
 */
const actions = {

}

/**
 * Mutations
 */
const mutations = {

    updateSearchQuery(state, searchQuery) {
        state.searchQuery = searchQuery
    },

    updateShowingScreenshotModal(state, show) {
        state.showingScreenshotModal = show
    },

    updateScreenshotModalImages(state, images) {
        state.screenshotModalImages = images
    },

}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
