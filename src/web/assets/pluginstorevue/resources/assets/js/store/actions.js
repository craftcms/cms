import * as types from './mutation-types'

export const addToCart = ({ commit }, plugin) => {
    commit(types.ADD_TO_CART, {
        id: plugin.id
    })
}

export const removeFromCart = ({ commit }, plugin) => {
    commit(types.REMOVE_FROM_CART, {
        id: plugin.id
    })
}

export const addToActiveTrials = ({ commit }, plugin) => {
    commit(types.ADD_TO_ACTIVE_TRIALS, {
        id: plugin.id
    })
}

export const removeFromActiveTrials = ({ commit }, plugin) => {
    commit(types.REMOVE_FROM_ACTIVE_TRIALS, {
        id: plugin.id
    })
}

