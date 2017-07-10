import * as types from './mutation-types'

export const addToCart = ({ commit }, plugin) => {

    console.log('action – addToCart()', plugin);

    commit(types.ADD_TO_CART, {
        id: plugin.id
    })
}

export const removeFromCart = ({ commit }, plugin) => {
    commit(types.REMOVE_FROM_CART, {
        id: plugin.id
    })
}

