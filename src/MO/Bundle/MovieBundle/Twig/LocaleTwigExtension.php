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
                20 => 'Lausanne',
                23 => 'Genève',
                21 => 'Berne',
                22 => 'Bâle'
            ),
            'currentCity' => $this->container->get('request')->cookies->get('city_locale', 20),
            'lastCacheUpdate' => $this->container->get('mo_movie.provider.pathe')->getLastCacheUpdate()
        );
    }
}