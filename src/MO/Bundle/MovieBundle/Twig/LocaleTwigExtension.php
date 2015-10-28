<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 30.04.14
 * Time: 14:05
 */

namespace MO\Bundle\MovieBundle\Twig;


use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocaleTwigExtension extends \Twig_Extension{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'mo_movie.twig.locale_extension';
    }

    public function getGlobals(){
        return array(
            'cities' => array(
                'lausanne' => 'Lausanne',
                'geneve' => 'Genève',
                'bern' => 'Berne',
                'basel' => 'Bâle'
            ),
            'currentCity' => $this->container->get('request')->attributes->get('city'),
            'lastCacheUpdate' => $this->container->get('mo_movie.provider.pathe')->getLastCacheUpdate()
        );
    }
}