import MigrateController from './MigrateController'
import Entries from './Entries'
import PreviewController from './PreviewController'
import Users from './Users'
import InstallController from './InstallController'
import ApiController from './ApiController'
import Utilities from './Utilities'
import Updates from './Updates'
import AddressesController from './AddressesController'
import Settings from './Settings'
import FieldsController from './FieldsController'
import Dashboard from './Dashboard'
import FilesystemsController from './FilesystemsController'
import PluginsController from './PluginsController'
import ConfigSyncController from './ConfigSyncController'
import StructuresController from './StructuresController'
import PluginStore from './PluginStore'

const Controllers = {
    MigrateController: Object.assign(MigrateController, MigrateController),
    Entries: Object.assign(Entries, Entries),
    PreviewController: Object.assign(PreviewController, PreviewController),
    Users: Object.assign(Users, Users),
    InstallController: Object.assign(InstallController, InstallController),
    ApiController: Object.assign(ApiController, ApiController),
    Utilities: Object.assign(Utilities, Utilities),
    Updates: Object.assign(Updates, Updates),
    AddressesController: Object.assign(AddressesController, AddressesController),
    Settings: Object.assign(Settings, Settings),
    FieldsController: Object.assign(FieldsController, FieldsController),
    Dashboard: Object.assign(Dashboard, Dashboard),
    FilesystemsController: Object.assign(FilesystemsController, FilesystemsController),
    PluginsController: Object.assign(PluginsController, PluginsController),
    ConfigSyncController: Object.assign(ConfigSyncController, ConfigSyncController),
    StructuresController: Object.assign(StructuresController, StructuresController),
    PluginStore: Object.assign(PluginStore, PluginStore),
}

export default Controllers