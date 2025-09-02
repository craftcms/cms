/* jshint esversion: 9, strict: false */
/* globals module, require, process */
const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const path = require('path');
const testDir = './tests-playwright';
const {cleanAll} = require('../events');
require('dotenv').config({path: path.resolve(path.join(testDir, '.env'))});

class Setup {
  constructor() {
    this.packagePath =
      path.basename(path.normalize(__dirname + '/../../')) ==
      'craftcms-playwright'
        ? 'packages/craftcms-playwright'
        : 'node_modules/@craftcms/playwright';

    // this.dockerCli = `docker compose --file=./${this.packagePath}/docker-compose.yaml exec --user appuser playwright`;
    // this.craftCli = '/app/craft';
    this.dockerCli = 'ddev';
    this.craftCli = 'craft';
    this.cwd = `./${this.packagePath}`;
  }

  async cleanAll() {
    cleanAll.emit('before');

    await this.dbRestore();
    await this.projectConfigRestore();
    await this.composerRestore();

    cleanAll.emit('after');
  }

  async installPlugin(handle) {
    process.stdout.write(`Installing Plugin "${handle}"`);
    process.stdout.write('\n');
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} plugin/install ${handle}`,
        {
          cwd: this.cwd,
        }
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
        `${this.dockerCli} ${this.craftCli} db/restore --interactive=0 /var/www/backup/db.sql`,
        {
          cwd: this.cwd,
        }
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
        `${this.dockerCli} ${this.craftCli} db/backup --interactive=0 --overwrite=1 /var/www/backup/db.sql`,
        {
          cwd: this.cwd,
        }
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
        `${this.dockerCli} exec "cp -vfrp /var/www/backup/project /var/www/html/config/"`,
        {
          cwd: this.cwd,
        }
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
        `${this.dockerCli} exec "cp -vfrp /var/www/backup/composer.json /var/www/html/."`,
        {
          cwd: this.cwd,
        }
      );
      await nodeExec(
        `${this.dockerCli} exec "cp -vfrp /var/www/backup/composer.lock /var/www/html/."`,
        {
          cwd: this.cwd,
        }
      );
      await nodeExec(
        `${this.dockerCli} composer install --working-dir=/var/www/html`,
        {
          cwd: this.cwd,
        }
      );
      await nodeExec(
        `${this.dockerCli} composer dump-autoload --working-dir=/var/www/html`,
        {
          cwd: this.cwd,
        }
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
        `${this.dockerCli} ${this.craftCli} fixture/load ${name} --namespace="${ns}"`,
        {
          cwd: this.cwd,
        }
      );
      return {stdout, stderr};
    } catch (e) {
      console.error(e);
    }
  }
}

module.exports = {Setup};
