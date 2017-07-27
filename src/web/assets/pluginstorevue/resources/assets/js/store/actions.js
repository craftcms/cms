import * as types from './mutation-types'

export const addToCart = ({ dispatch, commit}, plugin) => {
    commit(types.ADD_TO_CART, {
        id: plugin.id
    })
    dispatch('saveCartState');
}

export const removeFromCart = ({ dispatch, commit }, plugin) => {
    commit(types.REMOVE_FROM_CART, {
        id: plugin.id
    })
    dispatch('saveCartState');
}

export const addToActiveTrials = ({ dispatch, commit }, plugin) => {
    commit(types.ADD_TO_ACTIVE_TRIALS, {
        id: plugin.id
    })
    dispatch('saveCartState');
}

export const removeFromActiveTrials = ({ dispatch, commit }, plugin) => {
    commit(types.REMOVE_FROM_ACTIVE_TRIALS, {
        id: plugin.id
    })
    dispatch('saveCartState');
}

