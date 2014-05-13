<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 13.05.14
 * Time: 07:34
 */

namespace MO\Bundle\MovieBundle\Cache;


use Doctrine\Common\Cache\FilesystemCache;
use MO\Bundle\MovieBundle\MovieDataProviders\CinemaPool;
use MO\Bundle\MovieBundle\MovieDataProviders\PatheProvider;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class MovieWarmer implements CacheWarmerInterface {

    /**
     * @var ContainerInterface
     */
    private $container;

    private $cachePath;

    public function __construct(ContainerInterface $container, $cachePath){
        $this->container = $container;
        $this->cachePath = $cachePath;
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * Optional warmers can be ignored on certain conditions.
     *
     * A warmer should return true if the cache can be
     * generated incrementally and on-demand.
     *
     * @return bool    true if the warmer is optional, false otherwise
     */
    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        $cache = new FilesystemCache($cacheDir . $this->cachePath);
        $cinemaPool = $this->container->get('mo_movie.pool.cinema');

        $provier = new PatheProvider($cinemaPool, $cache);

        $provier->updateCache();
    }
}