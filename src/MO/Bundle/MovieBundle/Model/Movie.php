<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 10:40
 */

namespace MO\Bundle\MovieBundle\Model;


class Movie {

    private $name;

    private $pageUrl;

    private $imageUrl;

    /**
     * @var Performance[]
     */
    private $performances;

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

    /**
     * @param \MO\Bundle\MovieBundle\Model\Performance[] $performances
     */
    public function setPerformances($performances)
    {
        $this->performances = $performances;
    }

    /**
     * @param Performance $performance
     */
    public function addPerformance(Performance $performance){
        $this->performances[] = $performance;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\Performance[]
     */
    public function getPerformances()
    {
        return $this->performances;
    }

    /**
     * @param mixed $imageUrl
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @param mixed $pageUrl
     */
    public function setPageUrl($pageUrl)
    {
        $this->pageUrl = $pageUrl;
    }

    /**
     * @return mixed
     */
    public function getPageUrl()
    {
        return $this->pageUrl;
    }
} 