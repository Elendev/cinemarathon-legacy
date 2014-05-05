<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 14:32
 */

namespace MO\Bundle\MovieBundle\MovieDataProviders;


use MO\Bundle\MovieBundle\Model\Cinema;
use MO\Bundle\MovieBundle\Model\Hall;
use MO\Bundle\MovieBundle\Model\Movie;

class CinemaPool {

    private $cinemas = array();

    /**
     * @param $movies Movie[]
     * Update cinema references in case of cache reload
     */
    public function updateMovies($movies){
        foreach($movies as $movie){
            foreach($movie->getPerformances() as $performance){
                $performance->setCinema($this->getCinema($performance->getCinema()->getName()));
                $performance->setHall($this->getHall($performance->getCinema(), $performance->getHall()->getName()));
            }
        }
    }

    /**
     * @param $name
     * @return Cinema
     */
    public function getCinema($name){
        if(array_key_exists($name, $this->cinemas)){
            return $this->cinemas[$name];
        }

        $cinema = new Cinema();
        $cinema->setName($name);

        $this->cinemas[$name] = $cinema;

        return $cinema;
    }

    /**
     * @param Cinema $cinema
     * @param $name
     * @return Hall|\MO\Bundle\MovieBundle\Model\Hall
     */
    public function getHall(Cinema $cinema, $name){
        if(array_key_exists($name, $cinema->getHalls())){
            $halls = $cinema->getHalls();
            return $halls[$name];
        }

        $hall = new Hall();
        $hall->setName($name);

        $cinema->addHall($hall);

        return $hall;
    }

} 