import axios from 'axios';
import {getElementIndexParams} from '../utils/elementIndex'

let CancelToken = axios.CancelToken
let cancelTokenSource = CancelToken.source()

export default  {
  cancelRequests() {
    // cancel requests
    cancelTokenSource.cancel()

    // create new cancel token
    cancelTokenSource = CancelToken.source()
  },

  searchDevelopers({searchQuery, developerIndexParams}) {
    return new Promise((resolve) => {
      const params = getElementIndexParams(developerIndexParams)
      params.searchQuery = searchQuery

      // TODO: replace mocked response by real one

      const getDevelopersFixture = () => {
        const nbDevelopers = 24
        const developers = []

        for (let i = 0; i < nbDevelopers; i++) {
          developers[i] = {
            name: 'Developer ' + (i + 1),
          }
        }

        return developers
      }

      const developers = getDevelopersFixture()

      resolve({
        data: {
          developers
        }
      })
    })
  },
}