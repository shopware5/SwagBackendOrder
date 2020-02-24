#!/usr/bin/env bash

PLUGIN_DIR=$1
ENV=$2

wget https://docs.enterprise.shopware.com/downloads/SwagB2bPlugin/SwagB2bPlugin-dev.zip
unzip $PLUGIN_DIR/SwagB2bPlugin-dev.zip -d $PLUGIN_DIR/../
rm $PLUGIN_DIR/SwagB2bPlugin-dev.zip

php $PLUGIN_DIR/../../../bin/console sw:plugin:install --activate cron --env=$ENV
php $PLUGIN_DIR/../../../bin/console sw:plugin:install --activate SwagB2bPlugin --env=$ENV
