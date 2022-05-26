const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const craftCli = '/app/craft';

const dbRestore = async () => {
  console.log('Restoring DB');
  try {
    const {stdout, stderr} = await nodeExec(
      `${craftCli} db/restore /app/backup/db.sql`
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
      `cp -vfrp /app/backup/project /app/config/.`
    );
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

const composerRestore = async () => {
  console.log('Restoring Composer');
  try {
    const {stdout, stderr} = await nodeExec(
      `cp -vfrp /app/backup/composer.* /app/. && composer install --working-dir=/app`
    );
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

module.exports = {
  dbRestore,
  projectConfigRestore,
  composerRestore,
};
