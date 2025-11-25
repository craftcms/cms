import UtilitiesController from './UtilitiesController'
import DeprecationErrorsController from './DeprecationErrorsController'
import ClearCachesController from './ClearCachesController'
import DbBackupController from './DbBackupController'
import FindAndReplaceController from './FindAndReplaceController'
import MigrationsController from './MigrationsController'
import ProjectConfigController from './ProjectConfigController'
import SystemMessagesController from './SystemMessagesController'

const Utilities = {
    UtilitiesController: Object.assign(UtilitiesController, UtilitiesController),
    DeprecationErrorsController: Object.assign(DeprecationErrorsController, DeprecationErrorsController),
    ClearCachesController: Object.assign(ClearCachesController, ClearCachesController),
    DbBackupController: Object.assign(DbBackupController, DbBackupController),
    FindAndReplaceController: Object.assign(FindAndReplaceController, FindAndReplaceController),
    MigrationsController: Object.assign(MigrationsController, MigrationsController),
    ProjectConfigController: Object.assign(ProjectConfigController, ProjectConfigController),
    SystemMessagesController: Object.assign(SystemMessagesController, SystemMessagesController),
}

export default Utilities