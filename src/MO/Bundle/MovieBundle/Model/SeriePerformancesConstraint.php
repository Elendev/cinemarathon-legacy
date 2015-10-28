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
            'language' => 'all',
            'three_dimension' => 'all',
            'min_time_between' => 5*60,
            'max_time_between' => 60*60,
            'start_time_min' => 0,
            'start_time_max' => 60*60*24,
            'end_time_min' => 0,
            'end_time_max' => 60*60*24,
            'date_min' => null,
            'date_max' => null
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

        if(!$this->performanceRespectConstraint($performance, $computedConstraints)){
            return false;
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

        if($serie->getStartDate() > $performance->getEndDate()) {
            $diff = $serie->getStartDate()->getTimestamp() - $performance->getEndDate()->getTimestamp();
        } else if($serie->getEndDate() < $performance->getStartDate()){
            $diff = $performance->getStartDate()->getTimestamp() - $serie->getEndDate()->getTimestamp();
        } else { // in between
            return false;
        }

        return $diff >= $computedConstraints['min_time_between'] && $diff <= $computedConstraints['max_time_between'];
    }

    /**
     * @param Performance $performance
     * @param array $constraints
     */
    public function performanceRespectConstraint(Performance $performance, $constraints = array()) {
        $computedConstraints = array_merge($this->constraints, $constraints);

        if($computedConstraints['language'] != 'all') {
            if (strtolower($performance->getVersion()) != $computedConstraints['language']) {
                return false;
            }
        }

        if($computedConstraints['three_dimension'] != 'all') {
            if ($computedConstraints['three_dimension'] == '3d' && $performance->getKind() != Performance::KIND_3D) {
                return false;
            } else if ($computedConstraints['three_dimension'] == 'standard' && $performance->getKind() != Performance::KIND_STANDARD) {
                return false;
            }
        }

        if($computedConstraints['date_min'] !== null){
            if($performance->getStartDate()->getTimestamp() < $computedConstraints['date_min']){
                return false;
            }
        }

        if($computedConstraints['date_max'] !== null){
            if($performance->getStartDate()->getTimestamp() > $computedConstraints['date_max']){
                return false;
            }
        }

        $startTime = $performance->getStartDate()->format('H') * 60 * 60 + $performance->getStartDate()->format('i') * 60 + $performance->getStartDate()->format('s');
        $endTime = $performance->getEndDate()->format('H') * 60 * 60 + $performance->getEndDate()->format('i') * 60 + $performance->getEndDate()->format('s');

        if($startTime < $computedConstraints['start_time_min'] ||
            $startTime > $computedConstraints['start_time_max'] ||
            $endTime < $computedConstraints['end_time_min'] ||
            $endTime > $computedConstraints['end_time_max']){
            return false;
        }

        return true;
    }
} 