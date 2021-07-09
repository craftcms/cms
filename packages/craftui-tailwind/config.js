module.exports = {
    darkMode: 'media',
    plugins: [
        require('tailwindcss-rtl'),
        require('@tailwindcss/forms'),
    ],
    variants: {
        extend: {
            borderColor: ['disabled'],
            backgroundColor: ['disabled'],
            opacity: ['disabled'],
            cursor: ['disabled', 'hover', 'active']
        }
    }
}