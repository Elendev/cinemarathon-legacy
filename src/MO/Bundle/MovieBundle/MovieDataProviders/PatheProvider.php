<?php
/**
 * Created by PhpStorm.
 * User: jre
 * Date: 29.04.14
 * Time: 11:00
 */

namespace MO\Bundle\MovieBundle\MovieDataProviders;


use Doctrine\Common\Cache\CacheProvider;
use MO\Bundle\MovieBundle\Model\Movie;
use MO\Bundle\MovieBundle\Model\Performance;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\DateTime;

class PatheProvider {

    private $moviesUrl = "http://pathe.ch/fr/lausanne/Films/alaffiche";

    /**
     * @var CinemaPool
     */
    private $cinemaPool;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cache;

    /**
     * @var Movie[]
     */
    private $movies = array();

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(CinemaPool $cinemaPool, CacheProvider $cache, LoggerInterface $logger = null){
        $this->cinemaPool = $cinemaPool;
        $this->cache = $cache;

        $this->logger = $logger ? $logger : new NullLogger();
    }

    /**
     * return Movie[]
     */
    public function getCurrentMovies($options = array()) {

        $resultingOptions = array_merge(
            array(
                'locale' => 20
            ),
            $options);

        $cacheEntry = 'pathe_movies_' . $resultingOptions['locale'];

        //try the cache
        if($this->cache->contains($cacheEntry)) {
            $movies = $this->cache->fetch($cacheEntry);
            $this->logger->debug('Cache ok for movies ' . $cacheEntry, $resultingOptions);
            $this->cinemaPool->updateMovies($movies);
            return $movies;
        }

        $pageContent = file_get_contents($this->moviesUrl, false, $this->getStreamContext($resultingOptions));
        $crawler = new Crawler($pageContent);

        $movies = array();

        $crawler->filter('.movie-overview .row > div')->each(function(Crawler $node, $i) use(&$movies, $resultingOptions){
            $image = $node->filter('img')->attr('src');
            $link = $node->filter('a')->attr('href');
            $name = $node->filter('span > strong')->text();

            $movie = new Movie();
            $movie->setName($name);
            $movie->setPageUrl('pathe.ch' . $link . '/schedule');
            $movie->setImageUrl('http://pathe.ch' . $image);

            $movies[] = $movie;
        });

        //save in cache
        $this->cache->save($cacheEntry, $movies, strtotime('tomorrow') - time());
        $this->logger->debug('Cache saved for movies ' . $cacheEntry, $resultingOptions);


        return $movies;
    }

    /**
     * @param array $options
     * @return array|Movie[]
     * Return all movies with performances
     */
    public function getCurrentMoviesWithPerformances($options = array()){
        $resultingOptions = array_merge(
            array(
                'locale' => 20
            ),
            $options);

        if(array_key_exists($resultingOptions['locale'], $this->movies)){
            return $this->movies[$resultingOptions['locale']];
        }

        $movies = array();

        foreach($this->getCurrentMovies($options) as $movie){
            $movies[$movie->getPageUrl()] = $this->getMovie($movie->getPageUrl(), $options);
        }

        $this->movies[$resultingOptions['locale']] = $movies;
        return $movies;
    }

    /**
     * @param $url
     * @return Movie
     */
    public function getMovie($url, $options = array()) {

        $resultingOptions = array_merge(
            array(
                'locale' => 20
            ),
            $options);

        if(array_key_exists($resultingOptions['locale'], $this->movies) && array_key_exists($url, $this->movies[$resultingOptions['locale']])){
            return $this->movies[$resultingOptions['locale']];
        }

        $cacheEntry = 'pathe_movie_' . $resultingOptions['locale'] . '_' . $url;

        if($this->cache->contains($cacheEntry)){
            $movie = $this->cache->fetch($cacheEntry);
            $this->logger->debug('Cache ok for movie ' . $cacheEntry, $resultingOptions);
            $this->cinemaPool->updateMovies(array($movie));
            return $movie;
        }

        $movie = new Movie();

        $pageContent = file_get_contents('http://' . $url, false, $this->getStreamContext($resultingOptions));

        $movie->setPageUrl('http://' . $url);

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

            $english = array('Jan','Febr','Mar','Apr','May','Jun','Jul','Aug','Sept','Oct','Nov','Dec', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            $french = array('janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche');

            $startDate = \DateTime::createFromFormat('D d M Y H:i', str_replace($french, $english, strtolower($startDateString)));

            $endDate = \DateTime::createFromFormat('D d M Y H:i', str_replace($french, $english, strtolower($endDateString)));

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
            if($crawler->filter('abbr')->count() > 0){
                $version = $crawler->filter('abbr')->text();
            }

            if($crawler->filter('.cluetip strong')->count() > 0){
                $kind = trim($crawler->filter('.cluetip strong')->text());
            }

            /* @var $performance Performance */
            $performance = $performancesCop[$cop];

            if($kind = '3D'){
                $performance->setKind(Performance::KIND_3D);
            }

            $performance->setVersion($version);
        });

        $this->cache->save($cacheEntry, $movie, strtotime('tomorrow') - time());
        $this->logger->debug('Cache saved for movie ' . $cacheEntry, $resultingOptions);

        return $movie;
    }

    /**
     * Go through every movies to upload the cache
     */
    public function updateCache($locales = null) {

        if($locales == null){
            $locales = array(20, 21, 22, 23);
        }

        $this->cache->flushAll();

        foreach($locales as $locale){
            $movies = $this->getCurrentMovies(array('locale' => $locale));
            foreach($movies as $movie){
                $this->getMovie($movie->getPageUrl(), array('locale' => $locale));
            }
        }

    }

    private function getStreamContext ($options) {
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Cookie: patheregion=city=" . $options['locale'] . "\r\n"
            )
        );

        return stream_context_create($opts);
    }

} 