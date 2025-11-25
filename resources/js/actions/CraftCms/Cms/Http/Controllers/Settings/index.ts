import EntryTypesController from './EntryTypesController'
import RoutesController from './RoutesController'
import SectionsController from './SectionsController'
import SiteGroupsController from './SiteGroupsController'
import SitesController from './SitesController'
import GeneralSettingsController from './GeneralSettingsController'

const Settings = {
    EntryTypesController: Object.assign(EntryTypesController, EntryTypesController),
    RoutesController: Object.assign(RoutesController, RoutesController),
    SectionsController: Object.assign(SectionsController, SectionsController),
    SiteGroupsController: Object.assign(SiteGroupsController, SiteGroupsController),
    SitesController: Object.assign(SitesController, SitesController),
    GeneralSettingsController: Object.assign(GeneralSettingsController, GeneralSettingsController),
}

export default Settings