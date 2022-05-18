const docker = require('./_docker');

const dbBackupPath = './backup/db.sql';

const createProject = async () => {
  console.log('Creating project');
  try {
    const ret = await docker.exec(
      `composer create-project -n craftcms/craft .`
    );
  } catch (e) {
    console.error(e);
  }
};

const install = async (username, password) => {
  console.log('Installing Craft');
  try {
    const ret = await docker.exec(
      `./craft install/craft --interactive=0 --username=${username} --password=${password}`
    );
  } catch (e) {
    console.error(e);
  }
};

const dbBackup = async () => {
  console.log('Creating DB backup');
  try {
    const ret = await docker.exec(`./craft db/backup ${dbBackupPath}`);
  } catch (e) {
    console.error(e);
  }
};

const dbRestore = async () => {
  console.log('Restoring DB');
  try {
    const ret = await docker.exec(`./craft db/restore ${dbBackupPath}`);
  } catch (e) {
    console.error(e);
  }
};

const createUser = async (username, password) => {
  console.log('Creating user');
  try {
    const ret = await docker.exec(
      `./craft users/create --admin=1 --email=playwright@craftcms.com --username=${username} --password=${password}`
    );
  } catch (e) {
    console.error(e);
  }
};

module.exports = {
  createProject,
  createUser,
  dbBackup,
  dbRestore,
  install,
};
