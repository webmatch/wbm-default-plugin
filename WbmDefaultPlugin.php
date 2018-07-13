<?php

namespace WbmDefaultPlugin;

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class WbmDefaultPlugin
 *
 * @package WbmDefaultPlugin
 */
class WbmDefaultPlugin extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('wbm_default_plugin.plugin_dir', $this->getPath());

        parent::build($container);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function install(InstallContext $context)
    {
        $sql = file_get_contents($this->getPath() . '/Resources/sql/install.sql');
        $this->container->get('shopware.db')->query($sql);

        $this->addSchema();

        $this->updateAttributes();
        $this->updateEmotions();

        parent::install($context);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function update(UpdateContext $context)
    {
        $this->updateAttributes();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function uninstall(UninstallContext $context)
    {
        $sql = file_get_contents($this->getPath() . '/Resources/sql/uninstall.sql');
        $this->container->get('shopware.db')->query($sql);

        parent::uninstall($context);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context)
    {
        parent::deactivate($context);
    }

    /**
     * @throws \Exception
     */
    public function updateAttributes()
    {
        $xmlPath = $this->getPath() . '/Resources/attributes.xml';

        if (!file_exists($xmlPath)) {
            return;
        }

        try {
            $dom = XmlUtils::loadFile($xmlPath, $this->getPath() . '/Resources/schema/attributes.xsd');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s". Message: %s', $xmlPath, $e->getMessage()), $e->getCode(), $e);
        }
        $attributes = XmlUtils::convertDomElementToArray($dom->getElementsByTagName('attributes')->item(0));
        $crudService = $this->container->get('shopware_attribute.crud_service');

        $tables = [];

        foreach ($attributes['attribute'] as $attribute) {
            $table = $attribute['table'];
            if (!in_array($table, $tables)) {
                $tables[] = $table;
            }

            $crudService->update(
                $table,
                $attribute['field'],
                $attribute['type'],
                [
                    'label'            => $attribute['label'],
                    'displayInBackend' => $attribute['displayInBackend'],
                    'position'         => $attribute['position'],
                    'custom'           => $attribute['custom'],
                    'translatable'     => $attribute['translatable'],
                    'entity'           => $attribute['entity'],
                    'arrayStore'       => isset($attribute['arrayStore']['option']) ? $attribute['arrayStore']['option'] : null,
                ]
            );
        }

        $this->container->get('models')->generateAttributeModels($tables);
    }

    /**
     * @throws \Exception
     */
    private function updateEmotions()
    {
        $xmlPath = $this->getPath() . '/Resources/emotions.xml';

        if (!file_exists($xmlPath)) {
            return;
        }

        try {
            $dom = XmlUtils::loadFile($xmlPath, $this->getPath() . '/Resources/schema/emotions.xsd');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s". Message: %s', $xmlPath, $e->getMessage()), $e->getCode(), $e);
        }
        $emotions = XmlUtils::convertDomElementToArray($dom->getElementsByTagName('emotions')->item(0));
        $componentInstaller = $component = $this->container->get('shopware.emotion_component_installer');

        foreach ($emotions['emotion'] as $emotion) {
            $component = $componentInstaller->createOrUpdate(
                $this->getName(),
                $emotion['name'],
                [
                    'name' => $emotion['name'],
                    'xtype' => $emotion['xtype'],
                    'template' => $emotion['template'],
                    'cls' => $emotion['cls'],
                    'description' => $emotion['description'],
                ]
            );

            foreach ($emotion['fields']['field'] as $field) {
                $component->{$field['method']}(
                    [
                        'name' => $field['name'],
                        'fieldLabel' => $field['fieldLabel'] ?: '',
                        'allowBlank' => $field['allowBlank'],
                        'defaultValue' => $field['defaultValue'] ?: '',
                        'supportText' => $field['supportText'] ?: '',
                        'store' => $field['store'] ?: '',
                        'displayField' => $field['displayField'] ?: '',
                        'valueField' => $field['valueField'] ?: '',
                        'helpTitle' => $field['helpTitle'] ?: '',
                        'helpText' => $field['helpText'] ?: '',
                    ]
                );
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function addSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $schemas = [
//            $this->container->get('models')->getClassMetadata(PluginName\Models\Classname::class),
//            $this->container->get('models')->getClassMetadata(PluginName\Models\Classname::class),
        ];

        /** @var \Doctrine\DBAL\Schema\MySqlSchemaManager $schemaManager */
        $schemaManager = $this->container->get('dbal_connection')->getSchemaManager();
        foreach ($schemas as $class) {
            if (!$schemaManager->tablesExist($class->getTableName())) {
                $tool->createSchema([$class]);
            } else {
                $tool->updateSchema([$class], true); //true - saveMode and not delete other schemas
            }
        }
    }
}
