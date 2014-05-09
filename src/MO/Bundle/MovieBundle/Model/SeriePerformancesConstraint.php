<?php
/**
 * Created by PhpStorm.
 * User: jonas
 * Date: 06.05.14
 * Time: 21:12
 */

namespace MO\Bundle\MovieBundle\Model;


class SeriePerformancesConstraint {

    private $constraints;

    public function __construct($constraints = array()){
        $this->constraints = array_merge(array(
            'same_cinema' => true,
            'same_hall' => false,
            'min_time_between' => 5*60,
            'max_time_between' => 60*60,
            'start_time_min' => 0,
            'start_time_max' => 60*60*24,
            'end_time_min' => 0,
            'end_time_max' => 60*60*24
        ), $constraints);
    }

    /**
     * @param Serie $serie
     * @param Movie $movie
     * @param $constraints
     * @return boolean true if the constraints are respected
     */
    public function respectConstraints(Serie $serie, Performance $performance, $constraints = array()){

        $computedConstraints = array_merge($this->constraints, $constraints);

        if(count($serie->getPerformances()) == 0){
            return true;
        }

        if($serie->containsMovie($performance->getMovie())){
            return false;
        }

        if($computedConstraints['same_cinema']){
            $cinemas = $serie->getCinemas();

            if(count($cinemas) > 1 || $performance->getCinema()->getName() != array_values($cinemas)[0]->getName()){
                return false;
            }
        }

        if($computedConstraints['same_hall']) {
            $halls = $serie->getHalls();

            if(count($halls) > 1 || $performance->getHall() != array_values($halls)[0]){
                return false;
            }
        }

        /*$dayStart = strtotime('midnight');
        $startTime = $performance->getStartDate()->getTimestamp() - $dayStart;
        $endTime = $performance->getEndDate()->getTimestamp() - $dayStart;

        if($startTime < $this->constraints['start_time_min'] ||
            $startTime > $this->constraints['start_time_max'] ||
            $endTime < $this->constraints['end_time_min'] ||
            $endTime > $this->constraints['end_time_max']){
            return false;
        }*/

        if($serie->getStartDate() > $performance->getEndDate()) {
            $diff = $serie->getStartDate()->getTimestamp() - $performance->getEndDate()->getTimestamp();
        } else if($serie->getEndDate() < $performance->getStartDate()){
            $diff = $performance->getStartDate()->getTimestamp() - $serie->getEndDate()->getTimestamp();
        } else { // in between
            return false;
        }

        return $diff >= $computedConstraints['min_time_between'] && $diff <= $computedConstraints['max_time_between'];
    }
} 