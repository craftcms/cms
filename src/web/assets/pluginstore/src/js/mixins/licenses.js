export default {
    computed: {
        licenseMismatched() {
            return this.pluginLicenseInfo.licenseIssues.find(issue => issue === 'mismatched')
        },

        licenseValidOrAstray() {
            return (this.pluginLicenseInfo.licenseKeyStatus === 'valid' || this.pluginLicenseInfo.licenseKeyStatus === 'astray')
        }
    }
}