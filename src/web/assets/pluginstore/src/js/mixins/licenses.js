export default {
    computed: {
        licenseMismatched() {
            return this.pluginLicenseInfo.licenseIssues.find(issue => issue === 'mismatched')
        },
    }
}