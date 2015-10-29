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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\DateTime;

class PatheProvider {

    /**
     * @var ContainerInterface
     * List of the cities
     *
     */
    private $container;

    private $moviesUrl = "http://pathe.ch/fr/[city]/films/alafiche/all/[page]";

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

    public function __construct(CinemaPool $cinemaPool, CacheProvider $cache, ContainerInterface $container, LoggerInterface $logger = null){
        $this->cinemaPool = $cinemaPool;
        $this->cache = $cache;
        $this->container = $container;

        $this->logger = $logger ? $logger : new NullLogger();
    }

    /**
     * return Movie[]
     */
    public function getCurrentMovies($options = array()) {

        $resultingOptions = array_merge(
          array(
            'city' => 'lausanne'
          ),
          $options);

        $resultingOptions['locale'] = $this->container->getParameter('app.cities.codes.' . $resultingOptions['city']);

        $cacheEntry = 'pathe_movies_' . $resultingOptions['locale'];

        //try the cache
        if($this->cache->contains($cacheEntry)) {
            $movies = $this->cache->fetch($cacheEntry);
            $this->logger->debug('Cache ok for movies ' . $cacheEntry, $resultingOptions);
            $this->cinemaPool->updateMovies($movies);
            return $movies;
        }

        $cityUrl = str_replace('[city]', $resultingOptions['city'], $this->moviesUrl);
        $pageContent = file_get_contents(str_replace('[page]', 1, $cityUrl), false, $this->getStreamContext($resultingOptions));
        $crawler = new Crawler($pageContent);

        $pages = $crawler->filter('.paging')->first()->filter('a');
        $pagesCount = 0;

        $pages->each(function(Crawler $node, $i) use (&$pagesCount) {
            if (!empty($node->text()) && is_numeric($node->text())) {
                $pagesCount ++;
            }
        });

        if ($pagesCount == 0) { //there is a least one page, but maybe no paging
            $pagesCount = 1;
        }

        $movies = array();

        for ($page = 1; $page <= $pagesCount; $page ++) {
            $pageUrl = str_replace('[page]', $page, $cityUrl);
            $this->getMoviesFromPage($pageUrl, $resultingOptions, $movies);
        }

        //save in cache
        $this->cache->save($cacheEntry, $movies, strtotime('tomorrow') - time());
        $this->logger->debug('Cache saved for movies ' . $cacheEntry, $resultingOptions);


        return $movies;
    }

    /**
     * @param $url
     * @param $options
     * @param $movies useful to avoid creating a new array every time
     * Return an array of movies in the given page (useful when there is more than one page)
     */
    private function getMoviesFromPage($url, $options, &$movies = array()) {
        $pageContent = file_get_contents($url, false, $this->getStreamContext($options));
        $crawler = new Crawler($pageContent);

        $crawler->filter('.movie-overview .row > div')->each(function(Crawler $node, $i) use(&$movies, $options){
            $image = $node->filter('img')->attr('src');
            $link = $node->filter('a')->attr('href');
            $name = $node->filter('span > strong')->text();

            $movie = new Movie();
            $movie->setName($name);
            $movie->setPageUrl('pathe.ch' . $link . '/schedule');
            $movie->setImageUrl($image);

            $movies[] = $movie;
        });

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
                'city' => 'lausanne'
            ),
            $options);

        $resultingOptions['locale'] = $this->container->getParameter('app.cities.codes.' . $resultingOptions['city']);

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
                'city' => 'lausanne'
            ),
            $options);

        $resultingOptions['locale'] = $this->container->getParameter('app.cities.codes.' . $resultingOptions['city']);

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

            $clueTip = $crawler->filter('.cluetip[cop=' . $copNum . ']');
            $is3D = $clueTip->filter('.movie3D')->count() > 0;
            $language = $clueTip->parents()->parents()->filter('abbr')->text();


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
            $performance->setVersion(strtolower($language));

            if ($is3D) {
                $performance->setKind(Performance::KIND_3D);
            }

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
     * @param array $options
     * @return \DateTime
     */
    public function getFirstPerformanceDate($options = array()){
        $resultingOptions = array_merge(
            array(
                'locale' => 20
            ),
            $options);

        $locale = $resultingOptions['locale'];

        $key = 'pathe_movie_first_perf_date_' . $locale;

        if($this->cache->contains($key)){
            return $this->cache->fetch($key);
        }

        $firstDate = new \DateTime();
        foreach($this->getCurrentMoviesWithPerformances() as $movie){
            foreach($movie->getPerformances() as $performance){
                if($performance->getStartDate() < $firstDate){
                    $firstDate = $performance->getStartDate();
                }
            }
        }

        $this->cache->save($key, $firstDate, strtotime('tomorrow') - time());

        return $firstDate;
    }

    /**
     * @param array $options
     * @return \DateTime
     */
    public function getLastPerformanceDate($options = array()){
        $resultingOptions = array_merge(
            array(
                'locale' => 20
            ),
            $options);

        $locale = $resultingOptions['locale'];

        $key = 'pathe_movie_last_perf_date_' . $locale;

        if($this->cache->contains($key)){
            return $this->cache->fetch($key);
        }

        $lastDate = new \DateTime();
        $lastDate->setTimestamp(0);
        foreach($this->getCurrentMoviesWithPerformances() as $movie){
            foreach($movie->getPerformances() as $performance){
                if($performance->getEndDate() > $lastDate){
                    $lastDate = $performance->getEndDate();
                }
            }
        }

        $this->cache->save($key, $lastDate, strtotime('tomorrow') - time());

        return $lastDate;
    }

    /**
     * Go through every movies to upload the cache
     */
    public function updateCache($cities = null) {

        if($cities == null){
            $cities = explode('|', $this->container->getParameter('app.cities.route_requirement'));
        }

        $this->cache->flushAll();

        foreach($cities as $city){
            $movies = $this->getCurrentMovies(array('locale' => $city));
            foreach($movies as $movie){
                $this->getMovie($movie->getPageUrl(), array('locale' => $city));
            }
        }

        //last cache update
        $this->cache->save('pathe_movie_cache_update_date', date('Y-m-d H:i'));

    }

    public function getLastCacheUpdate(){
        $dateString = $this->cache->fetch('pathe_movie_cache_update_date');
        return \DateTime::createFromFormat('Y-m-d H:i', $dateString);
    }

    private function getStreamContext ($options) {
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Cookie: patheregion=city=" . $options['locale'] . "\r\n" .
                            "Accept-Language: fr-FR,fr;q=0.8\r\n"
            )
        );

        return stream_context_create($opts);
    }

} 