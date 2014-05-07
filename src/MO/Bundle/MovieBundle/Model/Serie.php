<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 30.04.14
 * Time: 08:37
 */

namespace MO\Bundle\MovieBundle\Model;


class Serie {

    /**
     * @var Performance[]
     */
    private $performances = array();

    private $signature;

    /**
     * @param \MO\Bundle\MovieBundle\Model\Performance[] $performances
     */
    public function setPerformances($performances)
    {
        $this->performances = array();

        foreach($performances as $performance){
            $this->performances[$performance->getStartDate()->getTimestamp()] = $performance;
        }

        ksort($this->performances);

        $this->signature = null;
    }

    public function addPerformance(Performance $performance){
        $this->performances[$performance->getStartDate()->getTimestamp()] = $performance;
        ksort($this->performances);

        $this->signature = null;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\Performance[]
     */
    public function getPerformances()
    {
        return $this->performances;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        if(count($this->performances) == 0){
            return null;
        }
        return array_values($this->performances)[0]->getStartDate();
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        if(count($this->performances) == 0){
            return null;
        }
        return array_values($this->performances)[count($this->performances)-1]->getEndDate();
    }

    /**
     * @return Cinema[]
     */
    public function getCinemas(){
        $cinemas = array();

        foreach($this->performances as $performance){
            $cinemas[$performance->getCinema()->getName()] = $performance->getCinema();
        }

        return $cinemas;
    }

    /**
     * @return Hall[]
     */
    public function getHalls(){
        $halls = array();

        foreach($this->performances as $performance){
            if(!in_array($performance->getHall(), $halls)){
                $halls[$performance->getCinema()->getName() . $performance->getHall()->getName()] = $performance->getHall();
            }
        }
        return $halls;
    }

    /**
     * @return Movie[]
     */
    public function getMovies(){
        $movies = array();

        foreach($this->performances as $performance){
            $movies[$performance->getMovie()->getName()] = $performance->getMovie();
        }
        return array_values($movies);
    }

    public function containsMovie(Movie $movie){
        foreach($this->performances as $performance){
            if($performance->getMovie()->equalsTo($movie)){
                return true;
            }
        }
        return false;
    }

    /**
     * Return a signature begining by the startDate (for order purpose)
     */
    public function getSignature(){

        if(empty($this->signature)){
            $signature = '';

            foreach($this->performances as $performance){ //performances is ordered => signature valid
                $signature .= $performance->getMovie()->getName();
            }

            $this->signature = $this->getStartDate()->getTimestamp() . $this->getEndDate()->getTimestamp() . md5($signature);
        }

        return $this->signature;
    }
} 