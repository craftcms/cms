export const cartPlugins = state => {
    return state.cart.items.map(({ id }) => {
        return state.plugins.all.find(p => p.id === id)
    })
}

export const activeTrialPlugins = state => {
    let plugins = state.craft.installedPlugins.map( id  => {
        return state.plugins.all.find(p => p.id === id)
    })

    return plugins.filter(p => {
        if(p) {
            return p.price > 0;
        }
    });
}
