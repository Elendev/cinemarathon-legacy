<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 10:57
 */

namespace MO\Bundle\MovieBundle\Manager;


use MO\Bundle\MovieBundle\Model\Movie;
use MO\Bundle\MovieBundle\MovieDataProviders\PatheProvider;

class MovieManager {

    /**
     * @var PatheProvider
     */
    private $provider;

    public function __construct(PatheProvider $provider){
        $this->provider = $provider;
    }

    /**
     * @param $url
     * @return Movie
     */
    public function getMovieFromUrl($url, $options = array()) {
        return $this->provider->getMovie($url, $options);
    }

    public function getCurrentMovies($options = array()){
        return $this->provider->getCurrentMovies($options);
    }

} 