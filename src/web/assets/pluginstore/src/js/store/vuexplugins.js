const localStoragePlugin = store => {
    store.subscribe((mutation, state) => {
        // window.localStorage.setItem('craft.installedPlugins', JSON.stringify(state.craft.installedPlugins))
    })
}

export default localStoragePlugin;
