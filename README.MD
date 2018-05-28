# Shopware-Plugin Bootstrap

Eine Shopware-Plugin Bootstrap mit 
* Template-Extension
* konventionellen Namespaces
* Konfigurationsdateien
* Attribute-Generation on Install/Update via XML Konfiguration
* Unit-Test bootstrap

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
Außerdem löscht es den `.git` sowie sich selbst.

```
chmod +x ./prepare.sh && ./prepare.sh
```