/**
 * State
 */
const state = {
    searchQuery: '',
    showingScreenshotModal: false,
    screenshotModalImages: null,
    screenshotModalImageKey: 0,
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {}

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

    updateScreenshotModalImageKey(state, key) {
        state.screenshotModalImageKey = key
    },
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
