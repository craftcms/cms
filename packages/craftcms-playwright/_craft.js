const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const dockerCli =
  'docker compose --file=./node_modules/@craftcms/playwright/docker-compose.yaml exec --user appuser playwright';
const craftCli = '/app/craft';

const dbRestore = async () => {
  console.log('Restoring DB');
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
  console.log('Backing up DB');
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
  console.log('Restoring Project Config');
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
  console.log('Restoring Composer');
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
