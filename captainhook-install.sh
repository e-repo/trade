#!/usr/bin/env sh

if [ -n "$(composer show | grep 'captainhook/captainhook')" ];
then
  vendor/bin/captainhook install -f --only-enabled --run-mode=docker --run-exec="docker compose run --rm php-cli" --run-path="vendor/bin/captainhook" > /dev/null 2>&1;
fi
