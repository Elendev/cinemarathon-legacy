<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 11:00
 */

namespace MO\Bundle\MovieBundle\MovieDataProviders;


use MO\Bundle\MovieBundle\Model\Movie;
use MO\Bundle\MovieBundle\Model\Performance;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\DateTime;

class PatheProvider {

    private $moviesUrl = "http://pathe.ch/fr/lausanne/Films/alaffiche";

    /**
     * @var CinemaPool
     */
    private $cinemaPool;

    public function __construct(CinemaPool $cinemaPool){
        $this->cinemaPool = $cinemaPool;
    }

    /**
     * return Movie[]
     */
    public function getCurrentMovies() {
        $pageContent = file_get_contents($this->moviesUrl, false, $this->getStreamContext());
        $crawler = new Crawler($pageContent);

        $movies = array();

        $crawler->filter('.movie-overview .row > div')->each(function(Crawler $node, $i) use(&$movies){
            $image = $node->filter('img')->attr('src');
            $link = $node->filter('a')->attr('href');
            $name = $node->filter('span > strong')->text();

            $movie = new Movie();
            $movie->setName($name);
            $movie->setPageUrl('http://pathe.ch' . $link . '/schedule');
            $movie->setImageUrl('http://pathe.ch' . $image);

            $movies[] = $movie;
        });

        return $movies;
    }

    /**
     * @param $url
     * @return Movie
     */
    public function getMovie($url) {
        $movie = new Movie();

        $pageContent = file_get_contents($url, false, $this->getStreamContext());

        $movie->setPageUrl($url);

        $crawler = new Crawler($pageContent);

        $name = $crawler->filter('meta')->each(function(Crawler $crawler, $i) use ($movie){
            $property = $crawler->attr('property');

            if($property == 'og:title'){
                $movie->setName($crawler->attr('content'));
            } else if($property == 'og:image') {
                $movie->setImageUrl($crawler->attr('content'));
            }
        });

        $performancesCop = array();

        $copMatch = array();
        $copRule = '/associativeArray\["([0-9]+)"\]/';
        preg_match_all($copRule, $pageContent, $copMatch);

        foreach($copMatch[1] as $copNum){
            $hourMatch = array();
            $hourRule = '/associativeArray\["' . $copNum . '"\](?:\s*)=(?:\s*)"(?:[^;"0-9]*)([0-9]+:[0-9]+)(?:[^;"0-9]*)([0-9]+:[0-9]+)(?:[^;"0-9]*)";/';
            preg_match($hourRule, $pageContent, $hourMatch);

            $dateMatch = array();
            $dateRule = '/dateArray\["' . $copNum . '"\](?:\s*)=(?:\s*)"([^;"]*)";/';
            preg_match($dateRule, $pageContent, $dateMatch);

            $cinemaMatch = array();
            $cinemaRule = '/cinemaArray\["' . $copNum . '"\](?:\s*)=(?:\s*)"([^;"]*)";/';
            preg_match($cinemaRule, $pageContent, $cinemaMatch);

            $hallMatch = array();
            $hallRule = '/hallArray\["' . $copNum . '"\](?:\s*)=(?:\s*)"([^;"]*)";/';
            preg_match($hallRule, $pageContent, $hallMatch);


            $cinema = $this->cinemaPool->getCinema($cinemaMatch[1]);
            $hall = $this->cinemaPool->getHall($cinema, $hallMatch[1]);

            $startHour = $hourMatch[1];
            $endHour = $hourMatch[2];
            $date = $dateMatch[1];

            $startDateString = $date . ' ' . date('Y') . ' ' . $startHour;
            $endDateString = $date . ' ' . date('Y') . ' ' . $endHour;

            $fmt = new \IntlDateFormatter(
                "fr-FR",
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'Etc/UTC',
                \IntlDateFormatter::GREGORIAN,
                'EEEE, dd MMMM y hh:mm'
            );

            $startDate = new \DateTime();
            $startDate->setTimestamp($fmt->parse($startDateString));

            $endDate = new \DateTime();
            $endDate->setTimestamp($fmt->parse($endDateString));

            if($startDate > $endDate){
                $endDate->add(new \DateInterval('P1D'));
            }

            $performance = new Performance();
            $performance->setCinema($cinema);
            $performance->setHall($hall);
            $performance->setStartDate($startDate);
            $performance->setEndDate($endDate);

            $performance->setMovie($movie);
            $movie->addPerformance($performance);

            $performancesCop[$copNum] = $performance;
        }

        $crawler->filter('.movie-time .movie-specific .time-table')->each(function(Crawler $crawler, $i) use ($performancesCop){
            $cop = $crawler->filter('.cluetip')->attr('cop');
            $version = $crawler->filter('abbr')->text();
            $kind = trim($crawler->filter('.cluetip strong')->text());

            /* @var $performance Performance */
            $performance = $performancesCop[$cop];

            if($kind = '3D'){
                $performance->setKind(Performance::KIND_3D);
            }

        });

        return $movie;
    }

    private function getStreamContext () {
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Cookie: patheregion=city=20\r\n"
            )
        );

        return stream_context_create($opts);
    }

} 