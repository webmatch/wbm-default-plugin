#!/bin/sh

NAME=${PWD##*/}
SCNAME=$(echo $NAME | perl -pe 's/([a-z0-9])([A-Z])/$1_\L$2/g' | tr '[:upper:]' '[:lower:]')
LCNAME=$(echo $NAME | sed -e "s/Wbm//g" | tr '[:upper:]' '[:lower:]')

echo "Changing plugin name from WbmDefaultPlugin to $NAME"

find ./ -not -path '*/\.*' -type f -exec sed -i '' -e "s/WbmDefaultPlugin/$NAME/g" {} \;

mv ./WbmDefaultPlugin.php ./$NAME.php

echo "Changing service prefix from wbm_default_plugin to $SCNAME"

find ./ -not -path '*/\.*' -type f -exec sed -i '' -e "s/wbm_default_plugin/$SCNAME/g" {} \;

echo "Changing snippets ini from defaultplugin.ini to $LCNAME.ini"

mv ./Resources/snippets/frontend/plugins/wbm/defaultplugin.ini ./Resources/snippets/frontend/plugins/wbm/$LCNAME.ini

echo "Removing .git directory, README.MD and prepare.sh"

rm -rf ./.git
rm ./README.MD
rm ./prepare.sh
