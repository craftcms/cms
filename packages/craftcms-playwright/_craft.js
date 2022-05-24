const dbBackupPath = './backup/db.sql';

const dbBackup = async () => {
  console.log('Creating DB backup');
  try {
  } catch (e) {
    console.error(e);
  }
};

const dbRestore = async () => {
  console.log('Restoring DB');
  try {
  } catch (e) {
    console.error(e);
  }
};

module.exports = {
  dbBackup,
  dbRestore,
};
