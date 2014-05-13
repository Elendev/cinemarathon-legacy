<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 13.05.14
 * Time: 08:34
 */

namespace MO\Bundle\MovieBundle\EventListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LanguageListener {

    public function setLocale(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        $request->setLocale($request->getPreferredLanguage(array('fr')));

    }

} 