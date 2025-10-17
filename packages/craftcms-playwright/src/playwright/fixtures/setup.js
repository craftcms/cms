/* jshint esversion: 9, strict: false */
const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const path = require('path');
const testDir = './tests-playwright';
const {cleanAll} = require('../events');
const logger = require('../logger');
require('dotenv').config({path: path.resolve(path.join(testDir, '.env'))});

class Setup {
  constructor() {
    this.packagePath =
      path.basename(path.normalize(__dirname + '/../../')) ==
      'craftcms-playwright'
        ? 'packages/craftcms-playwright'
        : 'node_modules/@craftcms/playwright';

    this.dockerCli = 'ddev';
    this.craftCli = 'craft';
    this.cwd = `./${this.packagePath}`;
  }

  async cleanAll() {
    await cleanAll.emit('before', this);

    await this.dbRestore();
    await this.projectConfigRestore();
    await this.composerRestore();

    await cleanAll.emit('after', this);
  }

  async installPlugin(handle) {
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} plugin/install ${handle}`,
        {
          cwd: this.cwd,
        }
      );

      logger.success(`Plugin "${handle}" installed.`);
      return {stdout, stderr};
    } catch (e) {
      logger.fatal(e);
    }
  }

  async dbRestore() {
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} db/restore --interactive=0 /var/www/backup/db.sql`,
        {
          cwd: this.cwd,
        }
      );
      logger.success('Craft DB restored.');
      return {stdout, stderr};
    } catch (e) {
      logger.fatal(e);
    }
  }

  async dbBackup() {
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} db/backup --interactive=0 --overwrite=1 /var/www/backup/db.sql`,
        {
          cwd: this.cwd,
        }
      );

      logger.success('Craft DB backed up.');
      return {stdout, stderr};
    } catch (e) {
      logger.fatal(e);
    }
  }

  async projectConfigRestore() {
    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} exec "cp -vfrp /var/www/backup/project /var/www/html/config/"`,
        {
          cwd: this.cwd,
        }
      );

      logger.success('Project config restored.');
      return {stdout, stderr};
    } catch (e) {
      logger.fatal(e);
    }
  }

  async composerRestore() {
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

      logger.success('Composer files restored.');
      return {stdout, stderr};
    } catch (e) {
      logger.fatal(e);
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

    try {
      const {stdout, stderr} = await nodeExec(
        `${this.dockerCli} ${this.craftCli} fixture/load ${name} --namespace="${ns}"`,
        {
          cwd: this.cwd,
        }
      );

      logger.success(`Data fixture "${name}" loaded.`);
      return {stdout, stderr};
    } catch (e) {
      logger.fatal(e);
    }
  }
}

module.exports = {Setup};
