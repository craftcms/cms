import PluginStoreController from './PluginStoreController'
import InstallController from './InstallController'
import RemoveController from './RemoveController'

const PluginStore = {
    PluginStoreController: Object.assign(PluginStoreController, PluginStoreController),
    InstallController: Object.assign(InstallController, InstallController),
    RemoveController: Object.assign(RemoveController, RemoveController),
}

export default PluginStore