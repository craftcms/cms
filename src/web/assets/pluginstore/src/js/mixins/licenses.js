export default {
    computed: {
        licenseMismatched() {
            return this.pluginLicenseInfo.licenseKeyStatus === 'mismatched'
        },

        licenseValidOrAstray() {
            return (this.pluginLicenseInfo.licenseKeyStatus === 'valid' || this.pluginLicenseInfo.licenseKeyStatus === 'astray')
        }
    }
}