# Shopware-Plugin Bootstrap

Eine Shopware-Plugin Bootstrap mit 
* Template-Extension
* CLI Command Boilerplate
* konventionellen Namespaces
* Konfigurationsdateien
* Attribute-Generation on Install/Update via XML Konfiguration
* Unit-Test bootstrap
* php-cs-fixer .dist config

## Bedienung

Repository in ein nach dem technischen Namen des Plugins benanntes Verzeichnis unter `custom/plugins` clonen.
Der Name des Plugins soll capitalized, Camel Case sein und mit "Wbm" beginnen. Bspw `WbmMyPlugin`

```
cd $SHOPWARE_ROOT/custom/plugins
git clone https://github.com/webmatch/wbm-default-plugin.git ./WbmMyPlugin
cd WbmMyPlugin
```

`prepare.sh` ausführbar machen und ausführen. Das Script ändert den Default Namen, default service prefixes und
Snippets ini nach dem Muster des beinhaltenden Verzeichnis. Zb. von `WbmDefaultPlugin` nach `WbmMyPlugin`.
Außerdem löscht es den `.git` Folder sowie sich selbst.

```
chmod +x ./prepare.sh && ./prepare.sh
```

## php-cs-fixer

PHP Coding Standards Fixer stellt die Einhaltung von Coding Standards sicher. Es richtet sich dabei
nach den Regeln definiert in der Konfigurationsdatei `.php_cs.dist`

[Installation via homebrow](https://github.com/FriendsOfPHP/PHP-CS-Fixer#globally-homebrew)

Nach der Installation wird das Tool über Command Line aufgerufen.

```
cd $SHOPWARE_ROOT/custom/plugins/WbmMyPlugin
php-cs-fixer fix
```

## XML Config Files

Die Konfigurationsdateien `config.xml`, `menu.xml` und `cronjob.xml` werden bei der Installation geparsed. Da der
Parser bei der Installation mit invaliden Daten Fehler wirft, sind diese Dateien zunächst mit einem Underscore unwirksam gemacht.
Sie enthalten demonstrative Daten und können durch Entfernung des Underscores im Dateinamen wieder wirksam gemacht werden.

## create.sh

Das Shell-Script `create.sh` ermöglicht die beschleunigte Erstellung von Boilerplate code für neue Controller und Models.

`create.sh` ausführbar machen und ausführen:

```
chmod +x ./create.sh && ./create.sh
```

Auf Basis der darauf folgenden Prompts und der in `.drafts` befindlichen Vorlagen wird der Boileplate Code in den
konventionellen Verzeichnissen und mit den angegebenen Klassen-Namen und Namespaces angelegt.
