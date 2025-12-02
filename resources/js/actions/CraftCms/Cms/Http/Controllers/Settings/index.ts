import EntryTypesController from './EntryTypesController'
import RoutesController from './RoutesController'
import SectionsController from './SectionsController'
import SiteGroupsController from './SiteGroupsController'
import SitesController from './SitesController'
import UserGroupsController from './UserGroupsController'
import UserSettingsController from './UserSettingsController'
import GeneralSettingsController from './GeneralSettingsController'

const Settings = {
    EntryTypesController: Object.assign(EntryTypesController, EntryTypesController),
    RoutesController: Object.assign(RoutesController, RoutesController),
    SectionsController: Object.assign(SectionsController, SectionsController),
    SiteGroupsController: Object.assign(SiteGroupsController, SiteGroupsController),
    SitesController: Object.assign(SitesController, SitesController),
    UserGroupsController: Object.assign(UserGroupsController, UserGroupsController),
    UserSettingsController: Object.assign(UserSettingsController, UserSettingsController),
    GeneralSettingsController: Object.assign(GeneralSettingsController, GeneralSettingsController),
}

export default Settings