const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const path = require('path');
const packagePath =
  path.basename(__dirname) == 'craftcms-playwright'
    ? 'packages/craftcms-playwright'
    : 'node_modules/@craftcms/playwright';
const dockerCli = `docker compose --file=./${packagePath}/docker-compose.yaml exec --user appuser playwright`;
const craftCli = '/app/craft';

const dbRestore = async () => {
  process.stdout.write('Restoring DB');
  process.stdout.write('\n');
  try {
    const {stdout, stderr} = await nodeExec(
      `${dockerCli} ${craftCli} db/restore --interactive=0 /app/backup/db.sql`
    );
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

const dbBackup = async () => {
  process.stdout.write('Backing up DB');
  process.stdout.write('\n');
  try {
    const {stdout, stderr} = await nodeExec(
      `${dockerCli} ${craftCli} db/backup --interactive=0 --overwrite=1 /app/backup/db.sql`
    );
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

const projectConfigRestore = async () => {
  process.stdout.write('Restoring Project Config');
  process.stdout.write('\n');
  try {
    const {stdout, stderr} = await nodeExec(
      `${dockerCli} cp -vfrp /app/backup/project /app/config/.`
    );
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

const composerRestore = async () => {
  process.stdout.write('Restoring Composer');
  process.stdout.write('\n');
  try {
    let {stdout, stderr} = await nodeExec(
      `${dockerCli} cp -vfrp /app/backup/composer.json /app/.`
    );
    await nodeExec(`${dockerCli} cp -vfrp /app/backup/composer.lock /app/.`);
    await nodeExec(`${dockerCli} composer install --working-dir=/app`);
    await nodeExec(`${dockerCli} composer dump-autoload --working-dir=/app`);
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

module.exports = {
  dbBackup,
  dbRestore,
  projectConfigRestore,
  composerRestore,
};
