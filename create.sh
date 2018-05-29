#!/bin/sh

NAME=${PWD##*/}

read -p "Create [c]ontroller or [m]odel? " -n 1 -r

if [ "$REPLY" = "c" ]; then

  echo;
  read -p "Enter controller name: ";

  CONTROLLER="$(tr '[:lower:]' '[:upper:]' <<< ${REPLY:0:1})${REPLY:1}";

  read -p "Create [f]rontend, [b]ackend or [w]idget controller? " -n 1 -r

  if [ "$REPLY" = "f" ]; then
    cp ./.drafts/Controllers/Frontend/Default.php.dist ./Controllers/Frontend/$CONTROLLER.php;
    find ./Controllers/Frontend/$CONTROLLER.php -type f -exec sed -i '' -e "s/_Default/_$CONTROLLER/g" {} \;
  elif [ "$REPLY" = "b" ]; then
    cp ./.drafts/Controllers/Backend/Default.php.dist ./Controllers/Backend/$CONTROLLER.php;
    find ./Controllers/Backend/$CONTROLLER.php -type f -exec sed -i '' -e "s/_Default/_$CONTROLLER/g" {} \;
  elif [ "$REPLY" = "w" ]; then
    cp ./.drafts/Controllers/Widgets/Default.php.dist ./Controllers/Widgets/$CONTROLLER.php;
    find ./Controllers/Widgets/$CONTROLLER.php -type f -exec sed -i '' -e "s/_Default/_$CONTROLLER/g" {} \;
  else
    echo;
    echo "Please enter [f], [b] or [w]";
  fi

  echo;

elif [ "$REPLY" = "m" ]; then

  echo;
  read -p "Enter model name: ";

  MODEL="$(tr '[:lower:]' '[:upper:]' <<< ${REPLY:0:1})${REPLY:1}";

  read -p "Enter model folder: ";

  FOLDER="$(tr '[:lower:]' '[:upper:]' <<< ${REPLY:0:1})${REPLY:1}";

  read -p "Enter table name: ";

  TABLE=$REPLY;

  mkdir -p ./Models/$FOLDER;
  cp ./.drafts/Models/Default.php.dist ./Models/$FOLDER/$MODEL.php;
  cp ./.drafts/Models/Repository.php.dist ./Models/$FOLDER/Repository.php;
  find ./Models/$FOLDER/$MODEL.php -type f -exec sed -i '' -e "s/DefaultModel/$MODEL/g" {} \;
  find ./Models/$FOLDER/$MODEL.php -type f -exec sed -i '' -e "s/default_table/$TABLE/g" {} \;
  find ./Models/$FOLDER/ -type f -exec sed -i '' -e "s/WbmDefaultPlugin\\\Models/$NAME\\\Models\\\\$FOLDER/g" {} \;

else
  echo;
  echo "Please enter [c] or [m]";
fi
