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
     * @var PerformanceTreeNodeVisitorConstraint
     */
    private $constraint;

    public function __construct(\DateTime $startDate = null, \DateTime $endDate = null){
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->performances = array();
    }

    public function visit(PerformancesTreeNode $performancesTreeNode){
        foreach($performancesTreeNode->getPerformances() as $performance){
            $this->performances[$performance->getStartDate()->getTimestamp() . $performance->getEndDate()->getTimestamp()] = $performance;
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