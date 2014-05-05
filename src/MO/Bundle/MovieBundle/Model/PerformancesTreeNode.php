<?php
/**
 * Created by PhpStorm.
 * User: jonas
 * Date: 05.05.14
 * Time: 20:09
 */

namespace MO\Bundle\MovieBundle\Model;


class PerformancesTreeNode {

    private static $formatters = array('Y', 'm', 'd', 'H', 'i', 's');

    private static $performanceQuantityLimit = 10;

    private $performances = array();

    private $value; //year, month

    private $formatterIndex;

    /**
     * @var PerformancesTreeNode[]
     */
    private $childs; //MoviesTreeNode childs


    /**
     * @param $movies Performance[]
     * @param int $formatterIndex
     */
    public function __construct($performances, $value = null, $formatterIndex = -1){
        $this->performances = $performances;
        $this->formatterIndex = $formatterIndex;
        $this->value = $value;

        $this->childs = array();

        if($this->formatterIndex < count(self::$formatters) + 1 && count($performances) > self::$performanceQuantityLimit){
            $this->createNewLevel($performances, $formatterIndex);
        } else {
            $this->performances = $performances;
        }
    }

    public function visit(PerformanceTreeNodeVisitor $visitor){

        $visitor->visit($this);

        if(count($this->childs) > 0){
            $formatter = self::$formatters[$this->formatterIndex + 1];
            $startId = $visitor->getStartDate()->format($formatter);
            $endId = $visitor->getEndDate()->format($formatter);

            foreach($this->childs as $key => $child){
                if($key >= $startId && $key <= $endId){
                    $child->visit($visitor);
                }
            }
        }
    }

    /**
     * @return null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\PerformancesTreeNode[]
     */
    public function getChilds()
    {
        return $this->childs;
    }

    /**
     * @return Performance[]
     */
    public function getPerformances()
    {
        return $this->performances;
    }

    /**
     * @return int
     */
    public function getFormatterIndex()
    {
        return $this->formatterIndex;
    }

    /**
     * @param $performances Performance[]
     */
    private function createNewLevel($performances, $formatterIndex){
        $fIndex = $formatterIndex + 1;

        $childPerfs = array();

        foreach($performances as $performance){
            $childId = $performance->getStartDate()->format(self::$formatters[$fIndex]);
            $childPerfs[$childId] = $performance;
        }

        foreach($childPerfs as $childId => $childPerformances){
            $this->childs[$childId] = new PerformancesTreeNode($childPerformances, $childId, $fIndex);
        }
    }
} 