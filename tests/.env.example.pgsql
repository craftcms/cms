# Set in accordance to your environment

DB_DSN="pgsql:host=localhost;port=5432;dbname=craft-test"
# if you are using the docker-compose.yaml file use these
# DB_DSN="pgsql:host=127.0.0.1;port=54320;dbname=craft-test"
DB_USER="craft"
DB_PASSWORD=""
# if you are using the docker-compose.yaml file, the password is defined in the file
#DB_PASSWORD="craft"
DB_SCHEMA="public"
DB_TABLE_PREFIX=""

SECURITY_KEY="abcde12345"

# Set this to the `entryUrl` param in the `codeception.yml` file.
PRIMARY_SITE_URL="https://test.craftcms.test/index.php"
FROM_EMAIL_NAME="Craft CMS"
FROM_EMAIL_ADDRESS="info@craftcms.com"
