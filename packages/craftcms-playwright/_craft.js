const nodeExec = util.promisify(require('child_process').exec);

const dbRestore = async () => {
  console.log('Restoring DB');
  try {
    const {stdout, stderr} = await nodeExec(`../../craft db/restore db.sql`);
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

module.exports = {
  dbRestore,
};
