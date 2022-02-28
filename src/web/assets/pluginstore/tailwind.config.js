module.exports = {
  prefix: 'tw-',
  corePlugins: {
    preflight: false,
  },
  content: ["./src/**/*.{vue,js}"],
  theme: {
    extend: {

      screens: {
        'xl': '1200px',
      },
    },
  },
  plugins: [],
}