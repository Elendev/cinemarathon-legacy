<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.10.2015
 * Time: 10:47
 */

namespace MO\Bundle\MovieBundle\EventListener;


use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class CityListener
{

    private $router;

    public function __construct(RouterInterface $router) {
        $this->router = $router;
    }
    //$this->router->getContext()->setParameter('city', 'geneve');

    public function setCity(GetResponseEvent $event) {
        $city = $event->getRequest()->attributes->get('city');

        if (!empty($city)) {
            $this->router->getContext()->setParameter('city', $city);
        }
    }
}