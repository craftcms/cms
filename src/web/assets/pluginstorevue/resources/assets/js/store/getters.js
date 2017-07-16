export const cartPlugins = state => {
    return state.cart.added.map(({ id }) => {
        return state.plugins.all.find(p => p.id === id)
    })
}

export const activeTrialPlugins = state => {
    return state.activeTrials.activeTrials.map(({ id }) => {
        return state.plugins.all.find(p => p.id === id)
    })
}
