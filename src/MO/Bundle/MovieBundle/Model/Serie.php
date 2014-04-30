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
    }

    public function addPerformance(Performance $performance){
        $this->performances[$performance->getStartDate()->getTimestamp()] = $performance;
        ksort($this->performances);
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
            if(!in_array($performance->getCinema(), $cinemas)){
                $cinemas[] = $performance->getCinema();
            }
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
                $halls[] = $performance->getHall();
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
            if(!in_array($performance->getMovie(), $movies)){
                $movies[] = $performance->getMovie();
            }
        }
        return $movies;
    }
} 