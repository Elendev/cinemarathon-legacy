<?php
/**
 * Created by PhpStorm.
 * User: jonas
 * Date: 05.05.14
 * Time: 20:24
 */

namespace MO\Bundle\MovieBundle\Model;


class PerformanceTreeNodeVisitor {
    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var Performance[]
     */
    private $performances;

    /**
     * @var function
     */
    private $getDateFunction;

    /**
     * @var function
     */
    private $validator;

    public function __construct($startDate = null, $endDate = null, $checkEndDate = false, $validator = null){

        if(is_numeric($startDate)){
            $this->startDate = new \DateTime();
            $this->startDate->setTimestamp($startDate);
        } else {
            $this->startDate = $startDate;
        }

        if(is_numeric($endDate)){
            $this->endDate = new \DateTime();
            $this->endDate->setTimestamp($endDate);
        } else {
            $this->endDate = $endDate;
        }

        //echo "<hr>Visit from " . $this->startDate->format('Y-m-d H:i:s'). " to " . $this->endDate->format('Y-m-d H:i:s'). "<hr>";

        $this->performances = array();

        if($checkEndDate){
            $this->getDateFunction = function(Performance $performance){
                return $performance->getEndDate();
            };
        } else {
            $this->getDateFunction = function(Performance $performance){
                return $performance->getStartDate();
            };
        }

        if(empty($validator)){
            $this->validator = function(Performance $performance){
                return true;
            };
        } else {
            $this->validator = $validator;
        }


    }

    public function visit(PerformancesTreeNode $performancesTreeNode){

        //echo "<hr><strong>Here i am - performances : " . count($performancesTreeNode->getPerformances()). "</strong><hr>";
        $validator = $this->validator;
        $dateFunction = $this->getDateFunction;

        foreach($performancesTreeNode->getPerformances() as $performance){
            if($dateFunction($performance) >= $this->startDate && $dateFunction($performance) <= $this->endDate && $validator($performance)){
                $this->performances[$performance->getStartDate()->getTimestamp() . $performance->getEndDate()->getTimestamp()] = $performance;
            }
        }
    }

    /**
     * @return Performance[]
     */
    public function getPerformances(){
        return $this->performances;
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

} 