export const cartProducts = state => {
    return state.cart.added.map(({ id }) => {
        return state.products.all.find(p => p.id === id)
    })
}
