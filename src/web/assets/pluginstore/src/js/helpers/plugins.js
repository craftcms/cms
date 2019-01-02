export default {

    sortPlugins(plugins, sortingOptions) {
        if (!plugins) {
            return []
        }

        // let plugins = JSON.parse(JSON.stringify(plugins))

        let attribute = sortingOptions.attribute
        let direction = sortingOptions.direction

        function compareASC(a, b) {
            if (a[attribute] < b[attribute]) {
                return -1
            }
            if (a[attribute] > b[attribute]) {
                return 1
            }
            return 0
        }

        function compareDESC(a, b) {
            if (a[attribute] > b[attribute]) {
                return -1
            }
            if (a[attribute] < b[attribute]) {
                return 1
            }
            return 0
        }

        if (direction === 'desc') {
            plugins.sort(compareDESC)
        } else {
            plugins.sort(compareASC)
        }

        return plugins
    }

}