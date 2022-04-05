/* global Craft */

export function escapeHtml(str) {
    return Craft.escapeHtml(str)
}

export function t(message, category, params) {
    return Craft.t(category, message, params)
}

export function formatDate(date) {
    return Craft.formatDate(date)
}

export function formatNumber(number, format = ',.0f') {
    return Craft.formatNumber(number, format)
}
