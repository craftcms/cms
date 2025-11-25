import WidgetsController from './WidgetsController'
import Widgets from './Widgets'
import DashboardController from './DashboardController'

const Dashboard = {
    WidgetsController: Object.assign(WidgetsController, WidgetsController),
    Widgets: Object.assign(Widgets, Widgets),
    DashboardController: Object.assign(DashboardController, DashboardController),
}

export default Dashboard