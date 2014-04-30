<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 15:55
 */

namespace MO\Bundle\MovieBundle\Manager;


use MO\Bundle\MovieBundle\Model\Movie;
use MO\Bundle\MovieBundle\Model\Performance;
use MO\Bundle\MovieBundle\Model\Serie;

class MovieMatcherManager {

    /**
     * @param $movies Movie[]
     * @return Serie[]
     */
    public function getSeries($movies, $options = array()){
        /* @var $series Serie[] */
        $series = array();

        foreach($movies as $movie){
            foreach($movie->getPerformances() as $performance){
                $serie = new Serie();
                $serie->addPerformance($performance);

                foreach($movies as $otherMovie){
                    if($otherMovie != $movie){
                        if($this->addMovie($serie, $otherMovie, $options)){
                            $series[] = $serie;
                        }
                    }
                }
            }
        }

        //clean series
        $orderedSeries = array();
        foreach($series as $serie){
            $orderedSeries[$serie->getStartDate()->getTimestamp() . $serie->getEndDate()->getTimestamp()] = $serie;
        }

        ksort($orderedSeries);

        return $orderedSeries;
    }

    /**
     * @param Movie $movie
     * @return Performance[]
     */
    private function getPerformancesArray(Movie $movie){
        $performances = array();

        foreach($movie->getPerformances() as $performance){
            $performances[$performance->getStartDate()->getTimestamp()] = $performance;
        }

        ksort($performance);
        return $performances;
    }

    /**
     * Try to add a movie to the given serie
     * $constraints : time between performances, same hall, etc
     * @param Serie $serie
     * @param Movie $movie
     * @return boolean true if the movie has been added
     */
    private function addMovie(Serie $serie, Movie $movie, $options = array()){
        $resultingOptions = array_merge(array(
            'same_cinema' => true,
            'same_hall' => false,
            'min_time_between' => 5*60,
            'max_time_between' => 60*60
        ), $options);

        if(in_array($movie, $serie->getMovies())){
            return false;
        }

        //try to add before or after
        foreach($movie->getPerformances() as $performance){
            if($this->respectConstraints($serie, $performance, $resultingOptions)){
                $serie->addPerformance($performance);
                return true;
            }
        }

        return false;
    }

    /**
     * @param Serie $serie
     * @param Movie $movie
     * @param $options
     * @return boolean true if the constraints are respected
     */
    private function respectConstraints(Serie $serie, Performance $performance, $options){
        if(count($serie->getPerformances()) == 0){
            return true;
        }

        if(in_array($performance->getMovie(), $serie->getMovies())){
            return false;
        }

        if($options['same_cinema']){
            $cinemas = $serie->getCinemas();

            if(count($cinemas) > 1 || $performance->getCinema() != $cinemas[0]){
                return false;
            }
        }

        if($options['same_hall']) {
            $halls = $serie->getHalls();

            if(count($halls) > 1 || $performance->getHall() != $halls[0]){
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

        return $diff >= $options['min_time_between'] && $diff <= $options['max_time_between'];
    }
} 