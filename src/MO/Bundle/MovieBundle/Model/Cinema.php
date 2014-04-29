<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 10:46
 */

namespace MO\Bundle\MovieBundle\Model;


class Cinema {

    private $name;

    /**
     * @var Movie[]
     */
    private $movies = array();

    /**
     * @var Hall[]
     */
    private $halls = array();

    /**
     * @param \MO\Bundle\MovieBundle\Model\Movie[] $movies
     */
    public function setMovies($movies)
    {
        $this->movies = $movies;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\Movie[]
     */
    public function getMovies()
    {
        return $this->movies;
    }

    /**
     * @param \MO\Bundle\MovieBundle\Model\Hall[] $rooms
     */
    public function setHalls($halls)
    {
        $this->halls = $halls;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\Hall[]
     */
    public function getHalls()
    {
        return $this->halls;
    }

    public function addHall(Hall $hall){
        $this->halls[$hall->getName()] = $hall;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString(){
        return $this->getName();
    }
} 