<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 10:41
 */

namespace MO\Bundle\MovieBundle\Model;


class Performance {

    const KIND_STANDARD = 'standard';
    const KIND_3D = '3D';
    const KIND_IMAX = 'IMAX';

    /**
     * @var Movie
     */
    private $movie;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    private $kind = self::KIND_STANDARD;

    private $version;

    /**
     * @var Hall
     */
    private $hall;

    /**
     * @var Cinema
     */
    private $cinema;

    public function __construct(){
        $this->startDate = new \DateTime();
        $this->startDate->setTimestamp(0);

        $this->endDate = new \DateTime();
        $this->endDate->setTimestamp(0);
    }

    /**
     * @param mixed $kind
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    /**
     * @return mixed
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param \MO\Bundle\MovieBundle\Model\Movie $movie
     */
    public function setMovie($movie)
    {
        $this->movie = $movie;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\Movie
     */
    public function getMovie()
    {
        return $this->movie;
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        if(!$endDate){
            $this->endDate = new \DateTime();
            $this->endDate->setTimestamp(0);
        }
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \MO\Bundle\MovieBundle\Model\Hall $hall
     */
    public function setHall($hall)
    {
        $this->hall = $hall;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\Hall
     */
    public function getHall()
    {
        return $this->hall;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        if(!$startDate){
            $this->startDate = new \DateTime();
            $this->startDate->setTimestamp(0);
        }
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \MO\Bundle\MovieBundle\Model\Cinema $cinema
     */
    public function setCinema($cinema)
    {
        $this->cinema = $cinema;
    }

    /**
     * @return \MO\Bundle\MovieBundle\Model\Cinema
     */
    public function getCinema()
    {
        return $this->cinema;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function getSignature(){
        return $this->getCinema()->getName() . $this->getHall()->getName() . $this->getStartDate()->getTimestamp() . $this->getEndDate()->getTimestamp() . $this->getVersion() . $this->getKind();
    }

} 