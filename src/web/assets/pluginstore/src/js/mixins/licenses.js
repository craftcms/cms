export default {
    computed: {
        licenseMismatched() {
            return this.getLicenseMismatched(this.pluginLicenseInfo)
        },

        licenseValidOrAstray() {
            return this.getLicenseValidOrAstray(this.pluginLicenseInfo)
        }
    },

    methods: {
        getLicenseMismatched(pluginLicenseInfo) {
            return pluginLicenseInfo && pluginLicenseInfo.licenseKeyStatus === 'mismatched'
        },

        getLicenseValidOrAstray(pluginLicenseInfo) {
            return (pluginLicenseInfo.licenseKeyStatus === 'valid' || pluginLicenseInfo.licenseKeyStatus === 'astray')
        },
    }
}
