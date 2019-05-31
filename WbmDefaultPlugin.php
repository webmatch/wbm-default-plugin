<?php

namespace WbmDefaultPlugin;

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Models\Mail\Mail;
use Shopware\Models\Mail\Repository;
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
        $this->addMailTemplates();

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
        if (!$context->keepUserData()) {
            $sql = file_get_contents($this->getPath() . '/Resources/sql/uninstall.sql');
            $this->container->get('shopware.db')->query($sql);
            $this->removeMailTemplates();
            $this->removeSchema();
            $this->removeAttributes();
        }

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
     * @param $schemaName
     * @param $node
     *
     * @return array|void
     */
    public function loadXMLSchema($schemaName, $node)
    {
        $xmlPath = $this->getPath() . '/Resources/' . $schemaName . '.xml';
        if (!file_exists($xmlPath)) {
            return;
        }
        try {
            $dom = XmlUtils::loadFile($xmlPath, $this->getPath() . '/Resources/schema/' . $schemaName . '.xsd');
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s". Message: %s', $xmlPath, $e->getMessage()), $e->getCode(), $e);
        }
        $elements = XmlUtils::convertDomElementToArray($dom->getElementsByTagName($schemaName)->item(0));

        if($elements === null){
            return [];
        }

        return isset($elements[$node][0]) ? $elements[$node] : [$elements[$node]];
    }

    /**
     * @throws \Exception
     */
    public function updateAttributes()
    {
        $attributes = $this->loadXMLSchema('attributes', 'attribute');

        /** @var CrudService $crudService */
        $crudService = $this->container->get('shopware_attribute.crud_service');

        $tables = [];

        foreach ($attributes as $attribute) {
            $table = $attribute['table'];
            if (!in_array($table, $tables, true)) {
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
    public function removeAttributes()
    {
        $attributes = $this->loadXMLSchema('attributes', 'attribute');

        /** @var CrudService $crudService */
        $crudService = $this->container->get('shopware_attribute.crud_service');

        foreach ($attributes as $attribute) {
            $crudService->delete($attribute['table'], $attribute['field']);
        }
    }

    /**
     * @throws \Exception
     */
    private function updateEmotions()
    {
        $emotions = $this->loadXMLSchema('emotions', 'emotion');

        $componentInstaller = $component = $this->container->get('shopware.emotion_component_installer');

        foreach ($emotions as $emotion) {
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

    /**
     * {@inheritdoc}
     */
    public function removeSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $schemas = [
//            $this->container->get('models')->getClassMetadata(PluginName\Models\Classname::class),
//            $this->container->get('models')->getClassMetadata(PluginName\Models\Classname::class),
        ];

        $tool->dropSchema($schemas);
    }

    /**
     * @throws \Exception
     */
    public function addMailTemplates()
    {
        $mails = $this->loadXMLSchema('mails', 'mail');

        /** @var ModelManager $modelManager */
        $modelManager = $this->container->get('models');

        /** @var Repository $mailRepository */
        $mailRepository = $modelManager->getRepository(Mail::class);

        foreach ($mails as $mail) {
            if ($mailRepository->findOneBy(['name' => $mail['name']]) === null) {
                $mailModel = new Mail();
                $mailModel->setName($mail['name']);
                $mailModel->setFromMail($mail['fromMail']);
                $mailModel->setFromName($mail['fromName']);
                $mailModel->setSubject($mail['subject']);
                $mailModel->setContent($mail['content']);
                $mailModel->setMailtype($mail['mailType']);

                if (!empty($mail['contentHTML'])) {
                    $mailModel->setContentHtml($mail['contentHTML']);
                }

                if ($mail['isHTML']) {
                    $mailModel->setIsHtml();
                }

                $modelManager->persist($mailModel);
                $modelManager->flush($mailModel);

                if (!empty($mail['translations'])) {
                    $this->addMailTranslation($mailRepository->findOneBy(['name' => $mail['name']])->getId(), $mail['translations']);
                }
            }
        }
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeMailTemplates()
    {
        $mails = $this->loadXMLSchema('mails', 'mail');

        /** @var ModelManager $modelManager */
        $modelManager = $this->container->get('models');

        /** @var Repository $mailRepository */
        $mailRepository = $modelManager->getRepository(Mail::class);

        foreach ($mails as $mail) {
            /** @var Mail $mailModel */
            $mailModel = $mailRepository->findOneBy(['name' => $mail['name']]);

            if (!empty($mail['translations'])) {
                $this->removeMailTranslation($mailModel->getId(), $mail['translations']);
            }

            $modelManager->remove($mailModel);
        }
        $modelManager->flush();
    }

    /**
     * @param $key
     * @param $translations
     */
    public function addMailTranslation($key, $translations)
    {
        $translationService = $this->container->get('translation');

        $translations['translation'] = isset($translations['translation'][0]) ? $translations['translation'] : [$translations['translation']];

        foreach ($translations['translation'] as $data) {
            $translationService->write($data['shopId'], 'config_mails', $key, $data);
        }
    }

    /**
     * @param $key
     * @param $translations
     */
    public function removeMailTranslation($key, $translations)
    {
        $translationService = $this->container->get('translation');

        $translations['translation'] = isset($translations['translation'][0]) ? $translations['translation'] : [$translations['translation']];

        foreach ($translations['translation'] as $data) {
            $translationService->delete($data['shopId'], 'config_mails', $key);
        }
    }
}
