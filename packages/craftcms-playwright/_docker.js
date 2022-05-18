const util = require('util');
const nodeExec = util.promisify(require('child_process').exec);
const path = require('path');
const {chromium, expect} = require('@playwright/test');

const getComposeYaml = () => {
  return path.resolve('./tests/docker-compose.yaml');
};

const up = async () => {
  console.log('up started');
  try {
    const {stdout, stderr} = await nodeExec(
      `docker compose --file=${getComposeYaml()} up -d`
    );
  } catch (e) {
    console.error(e);
  }
};

const down = async () => {
  console.log('Down started');
  try {
    const {stdout, stderr} = await nodeExec(
      `docker compose --file=${getComposeYaml()} down`
    );
  } catch (e) {
    console.error(e);
  }
};

const exec = async (command) => {
  try {
    const {stdout, stderr} = await nodeExec(
      `docker compose --file=${getComposeYaml()} exec web ${command}`
    );
    return {stdout, stderr};
  } catch (e) {
    console.error(e);
  }
};

module.exports = {
  up,
  down,
  exec,
  getComposeYaml,
};
