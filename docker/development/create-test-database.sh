#!/bin/bash

set -e
set -u

if [ -n "$POSTGRES_TEST_DB" ]; then
  sleep 2
	echo "Creating database '$POSTGRES_TEST_DB'"
	psql -v ON_ERROR_STOP=1 --dbname="$POSTGRES_DB" --username="$POSTGRES_USER" <<-EOSQL
	    CREATE DATABASE $POSTGRES_TEST_DB;
	    GRANT ALL PRIVILEGES ON DATABASE $POSTGRES_TEST_DB TO $POSTGRES_USER;
EOSQL
	echo "Created database $POSTGRES_TEST_DB"
fi