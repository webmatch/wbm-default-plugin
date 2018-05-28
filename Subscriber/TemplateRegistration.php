<?php

namespace WbmDefaultPlugin\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Shopware\Components\Theme\LessDefinition;

class TemplateRegistration implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * TemplateRegistration constructor.
     *
     * @param $pluginDirectory
     * @param $templateManager \Enlight_Template_Manager
     */
    public function __construct(
        $pluginDirectory,
        \Enlight_Template_Manager $templateManager
    ) {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Javascript' => 'addJsFiles',
            'Theme_Compiler_Collect_Plugin_Less' => 'addLessFiles',
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
        ];
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addJsFiles()
    {
        $jsFiles = [];

        return new ArrayCollection($jsFiles);
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addLessFiles()
    {
        $less = new LessDefinition(
            [],
            [],
            $this->pluginDirectory
        );

        return new ArrayCollection([$less]);
    }

    public function onPreDispatch()
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }
}
