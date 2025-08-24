const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const path = require('path');
const testDir = './tests-playwright';
require('dotenv').config({path: path.resolve(path.join(testDir, '.env'))});

class Setup {
  constructor() {
    this.packagePath =
      path.basename(path.normalize(__dirname + '/../../')) ==
      'craftcms-playwright'
        ? 'packages/craftcms-playwright'
        : 'node_modules/@craftcms/playwright';

    this.dockerCli = `docker compose --file=./${this.packagePath}/docker-compose.yaml exec --user appuser playwright`;
    this.craftCli = '/app/craft';
  }

  async cleanAll() {
    await this.dbRestore();
    await this.projectConfigRestore();
    await this.composerRestore();
  }

  async installPlugin(handle) {
    process.stdout.write(`Installing Plugin "${handle}"`);
    process.stdout.write('\n');
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} plugin/install ${handle}`
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
  }

  async dbRestore() {
    process.stdout.write('Restoring DB');
    process.stdout.write('\n');
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} db/restore --interactive=0 /app/backup/db.sql`
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
  }

  async dbBackup() {
    process.stdout.write('Backing up DB');
    process.stdout.write('\n');
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} db/backup --interactive=0 --overwrite=1 /app/backup/db.sql`
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
  }

  async projectConfigRestore() {
    process.stdout.write('Restoring Project Config');
    process.stdout.write('\n');
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} cp -vfrp /app/backup/project /app/config/`
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
  }

  async composerRestore() {
    process.stdout.write('Restoring Composer');
    process.stdout.write('\n');
    try {
      let {stdout, stderr} = await nodeExec(
        `${this.dockerCli} cp -vfrp /app/backup/composer.json /app/.`
      );
      await nodeExec(
        `${this.dockerCli} cp -vfrp /app/backup/composer.lock /app/.`
      );
      await nodeExec(`${this.dockerCli} composer install --working-dir=/app`);
      await nodeExec(
        `${this.dockerCli} composer dump-autoload --working-dir=/app`
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
  }

  getNamespace() {
    let ns = process.env.CODECEPTION_FIXTURES_NAMESPACE;
    // Escape the backslashes for use in cli commands

    ns = ns ? ns.replace(/\\/g, '\\\\') : '';

    return ns;
  }

  async loadFixture(name, namespace = null) {
    let ns = namespace ? namespace.replace(/\\/g, '\\\\') : this.getNamespace();

    process.stdout.write('Loading Fixture ' + name + ' (' + ns + ')');
    process.stdout.write('\n');
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} fixture/load ${name} --namespace="${ns}"`
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
  }
}

module.exports = {Setup};
