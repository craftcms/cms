export const cartPlugins = state => {
    return state.cart.items.map(({ id }) => {
        return state.plugins.all.find(p => p.id === id)
    })
}

export const activeTrialPlugins = state => {
    return state.cart.activeTrials.map(({ id }) => {
        return state.plugins.all.find(p => p.id === id)
    })
}
