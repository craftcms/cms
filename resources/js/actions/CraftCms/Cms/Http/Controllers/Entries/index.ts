import StoreEntryController from './StoreEntryController'
import CreateEntryController from './CreateEntryController'
import MoveEntryToSectionController from './MoveEntryToSectionController'
import EntriesIndexController from './EntriesIndexController'

const Entries = {
    StoreEntryController: Object.assign(StoreEntryController, StoreEntryController),
    CreateEntryController: Object.assign(CreateEntryController, CreateEntryController),
    MoveEntryToSectionController: Object.assign(MoveEntryToSectionController, MoveEntryToSectionController),
    EntriesIndexController: Object.assign(EntriesIndexController, EntriesIndexController),
}

export default Entries