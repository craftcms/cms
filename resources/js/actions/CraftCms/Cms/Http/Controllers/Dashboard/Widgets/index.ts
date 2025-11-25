import FeedController from './FeedController'
import CraftSupportController from './CraftSupportController'

const Widgets = {
    FeedController: Object.assign(FeedController, FeedController),
    CraftSupportController: Object.assign(CraftSupportController, CraftSupportController),
}

export default Widgets