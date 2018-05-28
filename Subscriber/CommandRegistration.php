<?php

namespace WbmDefaultPlugin\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class CommandRegistration implements SubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Console_Add_Command' => 'onAddConsoleCommand',
        ];
    }

    /**
     * @return ArrayCollection
     */
    public function onAddConsoleCommand()
    {
        return new ArrayCollection(
            []
        );
    }
}
