# Continuous Integration (CI)

There are [many](https://en.wikipedia.org/wiki/Comparison_of_continuous_integration_software)
[Continuous Integration](https://en.wikipedia.org/wiki/Continuous_integration)
platforms available to choose from.

Craft uses [Travis](https://travis-ci.com/craftcms/cms) for its public repo, but
you're free to use what you're comfortable with and modify things to your workflow.

There are [many options](https://docs.travis-ci.com/) for configuring Travis, but
let's examine [Craft's `.travis.yml` file](https://github.com/craftcms/cms/blob/3.2/.travis.yml).

```yaml
services:
  - mysql
  - postgresql
```

We want to run our tests against both PostgreSQL and MySQL since Craft supports both.

```yaml
matrix:
  fast_finish: true
  include:
  - php: 7.3
    env: DB=mysql
  - php: 7.3
    env: DB=pgsql
  - php: 7.2
    env: DB=mysql
  - php: 7.2
    env: DB=pgsql
  - php: 7.1
    env: TASK_TESTS_COVERAGE=1 DB=mysql
  - php: 7.1
    env: TASK_TESTS_COVERAGE=1 DB=pgsql
```

The `matrix` is where we explicitly define the different environments we want the
tests to run in.  That includes PHP 7.1 - 7.3 and we define an environment variable
called `DB` that sets both `mysql` and `pgsql` we can use later.

PHP 7.1 also sets an environment variable called `TASK_TESTS_COVERAGE` we'll use later
because that's the only environment we want code coverage reports to generate in (for
performance reasons).

```yaml
install:
- |
  if [[ $TASK_TESTS_COVERAGE != 1 ]]; then
    # disable xdebug for performance reasons when code coverage is not needed.
    phpenv config-rm xdebug.ini || echo "xdebug is not installed"
  fi
  # install composer dependencies
  export PATH="$HOME/.composer/vendor/bin:$PATH"
  travis_retry composer install $DEFAULT_COMPOSER_FLAGS
```

If `TASK_TESTS_COVERAGE` isn't set, we're going to disable xDebug to speed things up.
It's only needed for generated code coverage reports in this context.

Then we `composer install` to pull down all of Craft's dependencies.

```yaml
before_script:
- |
  # show some version and environment information
  php --version
  composer --version
  php -r "echo INTL_ICU_VERSION . \"\n\";"
  php -r "echo INTL_ICU_DATA_VERSION . \"\n\";"
  psql --version
  mysql --version
- travis_retry mysql -e 'CREATE DATABASE `craft-test`;';
- mysql -e "SET GLOBAL sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';";
- psql -U postgres -c 'CREATE DATABASE "craft-test";';
- pear config-set preferred_state beta
- pecl channel-update pecl.php.net
- yes | pecl install imagick
- cp tests/.env.example.$DB tests/.env
```

Before we run tests, we output some relevant debugging information to the console
and then create a MySQL and PostgreSQL database called `craft-test` that the tests
are going to use.

Then we install Imagick on the server as some image specific tests require it to complete.

```yaml
- cp tests/.env.example.$DB tests/.env
```

Finally, for each build environment, we take the `.env.example.mysql` and `.env.example.pgsql`
files that are in the root of the `tests` folder and copy them to `tests/.env` so the test
environments know how to connect to each type of database.

```yaml
script:
- |
  if [[ $TASK_TESTS_COVERAGE != 1 ]]; then
    vendor/bin/codecept run unit
  else
    mkdir -p build/logs
    vendor/bin/codecept run unit --coverage-xml coverage.xml;
  fi
```

If `TASK_TESTS_COVERAGE` is set, then we pass in the flags to Codeception to generate
code coverage reports.  If not, we just run the tests.

```yaml
after_script:
- |
  if [ $TASK_TESTS_COVERAGE == 1 ]; then
    bash <(curl -s https://codecov.io/bash)
  fi
```

After tests are done executing, if `TASK_TESTS_COVERAGE` is set, we upload the code
coverage reports to a 3rd party service, [https://codecov.io](https://codecov.io/gh/craftcms/cms).