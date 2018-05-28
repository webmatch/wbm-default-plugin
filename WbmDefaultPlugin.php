<?php

namespace WbmDefaultPlugin;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
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
     */
    public function install(InstallContext $context)
    {
        $sql = file_get_contents($this->getPath() . '/Resources/sql/install.sql');
        $this->container->get('shopware.db')->query($sql);

        $this->updateAttributes();

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
     */
    public function update(UpdateContext $context)
    {
        $this->updateAttributes();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * {@inheritdoc}
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

    public function updateAttributes()
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $attributeXml = file_get_contents($this->getPath() . '/Resources/attributes.xml');
        $attributes = simplexml_load_string($attributeXml);

        $tables = [];

        foreach ($attributes as $attribute) {
            $table = utf8_encode($attribute->table);
            if (!in_array($table, $tables)) {
                $tables[] = $table;
            }

            $arrayStore = (array) $attribute->arrayStore;

            $crudService->update(
                $table,
                utf8_encode($attribute->field),
                utf8_encode($attribute->type),
                [
                    'label'            => $attribute->label,
                    'displayInBackend' => filter_var($attribute->displayInBackend, FILTER_VALIDATE_BOOLEAN),
                    'position'         => (int) $attribute->position,
                    'custom'           => filter_var($attribute->custom, FILTER_VALIDATE_BOOLEAN),
                    'translatable'     => filter_var($attribute->translatable, FILTER_VALIDATE_BOOLEAN),
                    'entity'           => utf8_encode($attribute->entity),
                    'arrayStore'       => isset($arrayStore['option']) ? $arrayStore['option'] : null,
                ]
            );
        }

        $this->container->get('models')->generateAttributeModels($tables);
    }
}
