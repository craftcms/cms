const path = require('path');
const packagePath =
  path.basename(__dirname) == 'craftcms-playwright'
    ? 'packages/craftcms-playwright'
    : 'node_modules/@craftcms/playwright';

const dockerCli = `docker compose --file=./${packagePath}/docker-compose.yaml exec --user appuser playwright`;
const craftCli = '/app/craft';


module.exports = {
  dockerCli,
  craftCli
};