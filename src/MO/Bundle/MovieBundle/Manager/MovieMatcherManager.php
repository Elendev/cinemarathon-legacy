<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 15:55
 */

namespace MO\Bundle\MovieBundle\Manager;


use Doctrine\Common\Cache\CacheProvider;
use MO\Bundle\MovieBundle\Model\Movie;
use MO\Bundle\MovieBundle\Model\Performance;
use MO\Bundle\MovieBundle\Model\PerformancesTreeNode;
use MO\Bundle\MovieBundle\Model\PerformanceTreeNodeVisitor;
use MO\Bundle\MovieBundle\Model\Serie;
use MO\Bundle\MovieBundle\Model\SeriePerformancesConstraint;
use Symfony\Component\Stopwatch\Stopwatch;

class MovieMatcherManager {

    /**
     * @var MovieManager
     */
    private $movieManager;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cache;

    /**
     * @var array
     */
    private $defaultOptions;

    /**
     * @var PerformancesTreeNode
     */
    private $allPerformancesTreeNodes = array();

    /**
     * @var Stopwatch
     */
    private $stopWatch;

    public function __construct(MovieManager $movieManager, CacheProvider $cache, Stopwatch $stopwatch = null){
        $this->movieManager = $movieManager;
        $this->cache = $cache;

        $this->stopWatch = $stopwatch;

        $this->defaultOptions = array(
            'locale' => 20,
            'same_cinema' => true,
            'same_hall' => false,
            'min_time_between' => 5*60,
            'max_time_between' => 60*60,
            'serie_min_size' => 2,
            'serie_max_size' => 3,
            'start_time_min' => 17*60*60,
            'start_time_max' => 60*60*24,
            'end_time_min' => 0,
            'end_time_max' => 60*60*24,
            'date_min' => null,
            'date_max' => null
        );
    }

    /**
     * @param $requiredMovies Movie[] movies that need to appear
     * @return Serie[]
     */
    public function getSeries($requiredMovies, $options){

        if(null !== $this->stopWatch) {
            $this->stopWatch->start('getSeries', 'MovieMatcherManager');
        }

        $options = $this->getOptions($options);

        /* @var $series Serie[] */
        $series = array();

        if(!empty($requiredMovies)) {

            $constraint = new SeriePerformancesConstraint($options);

            foreach($requiredMovies as $movie){
                foreach($movie->getPerformances() as $performance){
                    if($constraint->performanceRespectConstraint($performance, $options)){
                        $series = array_merge($series, $this->createSeriesForPerformance($performance, $requiredMovies, $options, $constraint));
                    }
                }
            }

            if(null !== $this->stopWatch) {
                $this->stopWatch->lap('getSeries');
            }

            ksort($series);
        }

        if(null !== $this->stopWatch) {
            $this->stopWatch->stop('getSeries');
        }

        return $series;
    }

    /**
     * @return Serie[]
     */
    private function createSeriesForPerformance(Performance $performance, $requiredMovies = array(), $options = array(), SeriePerformancesConstraint $constraint){

        $series = array();

        $performancesTreeNode = $this->getAllPerformancesTreeNode($options);

        $options = $this->getOptions($options);


        $minSize = $options['serie_min_size'];
        $maxSize = $options['serie_max_size'];

        $requiredCount = count($requiredMovies);

        $seriesToComplete = array();
        $seriesToComplete[0] = array(
            'lookBack' => false,
            'serie' => new Serie()
        );
        $seriesToComplete[0]['serie']->addPerformance($performance);

        while(!empty($seriesToComplete)){
            $nextSerie = array_pop($seriesToComplete);

            $continue = true;
            $lookBack = $nextSerie['lookBack']; //nothing after : look back

            /* @var $serie Serie */
            $serie = $nextSerie['serie'];
            //$loop = 0;
            while($continue && count($serie->getPerformances()) < $maxSize){

                //echo "<hr>serie from " . $serie->getStartDate()->format('Y-m-d H:i:s'). " to " . $serie->getEndDate()->format('Y-m-d H:i:s'). "<hr>";
                $ptnv = $this->createPerformancesTreeNodeVisitor($serie, $constraint, $options, $lookBack);

                $performancesTreeNode->visit($ptnv);
                //echo "Did find " . count($ptnv->getPerformances()) . " performances<hr>";

                if(count($ptnv->getPerformances()) == 1){
                    $serie->addPerformance(array_values($ptnv->getPerformances())[0]);
                } else if(count($ptnv->getPerformances()) > 1){
                    $currentPerformances = $serie->getPerformances();
                    $perfs = $ptnv->getPerformances();

                    $serie->addPerformance(array_shift($perfs));

                    foreach($perfs as $perf){
                        $newSerie = new Serie();
                        $newSerie->setPerformances(array_merge($currentPerformances, array($perf)));
                        $seriesToComplete[] = array(
                            'lookBack' => $lookBack,
                            'serie' => $newSerie
                        );
                    }
                } else {
                    if(!$lookBack){
                        $lookBack = true;
                    } else {
                        $continue = false;
                    }
                }
                //echo "<hr>Loop : " . $loop ++ . " - serie performances size : " . count($serie->getPerformances()) . " - " . $maxSize;
            };

            if(count($serie->getPerformances()) >= $minSize && count(array_intersect($serie->getMovies(), $requiredMovies)) == $requiredCount){
                $series[$serie->getSignature()] = $serie;
            }
        }

        return $series;
    }

    /**
     * @param Serie $serie
     * @param $constraints
     * @return PerformanceTreeNodeVisitor
     */
    private function createPerformancesTreeNodeVisitor(Serie $serie, SeriePerformancesConstraint $constraint, $options, $reverseOrder = false){
        if($reverseOrder) {
            $timestamp = $serie->getStartDate()->getTimestamp();
            $fromTime = $timestamp - $options['max_time_between'];
            $toTime = $timestamp - $options['min_time_between'];
        } else {
            $timestamp = $serie->getEndDate()->getTimestamp();
            $fromTime = $timestamp + $options['min_time_between'];
            $toTime = $timestamp + $options['max_time_between'];
        }

        return new PerformanceTreeNodeVisitor($fromTime,
            $toTime,
            $reverseOrder,
            function(Performance $performance) use ($serie, $constraint){
                return $constraint->respectConstraints($serie, $performance);
            }
        );
    }

    /**
     * @return PerformancesTreeNode
     */
    private function getAllPerformancesTreeNode($options){
        if(null !== $this->stopWatch) {
            $this->stopWatch->start('getAllPerformancesTreeNode', 'MovieMatcherManager');
        }

        $locale = $options['locale'];
        $key = 'movie_matcher_manager_performances_treenode_all_' . $locale;

        if(!array_key_exists($locale, $this->allPerformancesTreeNodes)){
            if($this->cache->contains($key)){
                $this->allPerformancesTreeNodes[$locale] = $this->cache->fetch($key);
            } else {
                //get all performances for all movies
                $performances = array();

                foreach($this->movieManager->getCurrentMoviesWithPerformances($options) as $movie){
                    $performances = array_merge($performances, $movie->getPerformances());
                }
                $performancesTreeNode = new PerformancesTreeNode($performances);
                $this->allPerformancesTreeNodes[$locale] = $performancesTreeNode;

                $this->cache->save($key, $performancesTreeNode, strtotime('tomorrow') - time());
            }
        }

        if(null !== $this->stopWatch) {
            $this->stopWatch->stop('getAllPerformancesTreeNode');
        }


        return $this->allPerformancesTreeNodes[$locale];
    }

    private function getOptions($options){
        return array_merge($this->defaultOptions, $options);
    }
} 