const config = require('./_config');
const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const path = require('path');
const packagePath =
  path.basename(__dirname) == 'craftcms-playwright'
    ? 'packages/craftcms-playwright'
    : 'node_modules/@craftcms/playwright';

const dockerCli = `docker compose --file=./${packagePath}/docker-compose.yaml exec --user appuser playwright`;
const craftCli = '/app/craft';
const testDir = './tests-playwright';
require('dotenv').config({path: path.resolve(path.join(testDir, '.env'))});

const getNamespace = () => {
  let ns = process.env.PLAYWRIGHT_FIXTURES_NAMESPACE;
  // Escape the backslashes for use in cli commands

  ns = ns ? ns.replace(/\\/g, '\\\\') : '';

  return ns;
};

const loadFixture = async (name, namespace = null) => {
  let ns = namespace ? namespace.replace(/\\/g, '\\\\') : getNamespace();

  process.stdout.write('Loading Fixture ' + name + ' (' + ns + ')');
  process.stdout.write('\n');
  try {
    const {stdout, stderr} = await nodeExec(
      `${dockerCli} ${craftCli} fixture/load ${name} --namespace="${ns}"`
    );
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

const cleanAll = async () => {
  await dbRestore();
  await projectConfigRestore();
  await composerRestore();
};

const installPlugin = async (handle) => {
  process.stdout.write(`Installing Plugin "${handle}"`);
    process.stdout.write('\n');
    try {
      const {stdout, stderr} = await nodeExec(
        `${dockerCli} ${craftCli} plugin/install ${handle}`
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
};

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
      `${dockerCli} cp -vfrp /app/backup/project /app/config/`
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
  loadFixture,
  cleanAll,
  installPlugin,
};
