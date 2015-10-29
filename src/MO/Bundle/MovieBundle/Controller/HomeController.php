<?php

namespace MO\Bundle\MovieBundle\Controller;

use MO\Bundle\MovieBundle\Form\SearchFormType;
use MO\Bundle\MovieBundle\Model\Movie;
use MO\Bundle\MovieBundle\MovieDataProviders\PatheProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HomeController
 * @package MO\Bundle\MovieBundle\Controller
 */
class HomeController extends Controller
{

    /**
     *
     * @Route("/", name="mo_movie.home")
     * @Cache(public=true, vary={"Cookie"}, expires="tomorrow")
     * @Template()
     */
    public function homeAction(Request $request)
    {
        $teaserSerie = $this->get('mo_movie.manager.movie_matcher')->getTeaserSeries(3, array(
            'city' => $this->getCity($request)
        ));

        return array(
            'serieTeaser' => $teaserSerie,
            'form' => $this->createComboForm($this->getCity($request))->createView()
        );
    }

    /**
     *
     * @Route("/movies", name="mo_movie.movie_list")
     * @Cache(public=true, vary={"Cookie"}, expires="tomorrow")
     * @Template()
     */
    public function movieListAction(Request $request)
    {
        $movies = $this->get('mo_movie.manager.movie_manager')->getCurrentMovies(array(
            'city' => $this->getCity($request)
        ));

        return array(
            'movies' => $movies
        );
    }

    /**
     * @Route("/movieDetail", name="mo_movie.movie_detail")
     * @Cache(public=true, vary={"Cookie"}, expires="tomorrow")
     * @Template()
     */
    public function movieDetailAction(Request $request)
    {
        $url = $request->query->get('movie_url', null);

        if($url){
            $movie = $this->get('mo_movie.manager.movie_manager')->getMovieFromUrl($url, array(
                'city' => $this->getCity($request)
            ));
        } else {
            $movie = null;
        }

        return array(
            'url' => $url,
            'movie' => $movie
        );
    }

    /**
     * @Route("/movieTimeline", name="mo_movie.movie_timeline")
     * @Cache(public=true, vary={"Cookie"}, expires="tomorrow")
     * @Template()
     * @param Request $request
     */
    public function moviesTimelineAction(Request $request){
        //$movies = $this->get('mo_movie.manager.movie_manager')->getCurrentMovies(array('locale' => $this->getCityLocale($request)));

        $form = $this->createComboForm($this->getCity($request));

        $form->handleRequest($request);

        $movieList = array();
        $series = array();

        if($form->isValid()){
            $moviesToUse = $form->get('movies')->getData();

            foreach($moviesToUse as $movieUrl){
                $movieList[] = $this->get('mo_movie.manager.movie_manager')->getMovieFromUrl($movieUrl, array('locale' => $this->getCityLocale($request)));
            }

            $options = array(
                'city' => $this->getCity($request),
                'same_cinema' => $form->get('same_cinema')->getData(),
                'same_hall' => $form->get('same_hall')->getData(),
                'language' => $form->get('language')->getData(),
                'three_dimension' => $form->get('three_dimension')->getData(),
                'min_time_between' => $form->get('min_time_between')->getData(),
                'max_time_between' => $form->get('max_time_between')->getData(),
                'start_time_min' => $form->get('start_time_min')->getData(),
                'start_time_max' => $form->get('start_time_max')->getData(),
                'serie_min_size' => $form->get('serie_min_size')->getData(),
                'serie_max_size' => $form->get('serie_max_size')->getData(),
                'date_min' => $form->get('date_min')->getData(),
                'date_max' => $form->get('date_max')->getData()
            );

            $series = $this->get('mo_movie.manager.movie_matcher')->getSeries($movieList, $options);
        }

        return array(
            'form' => $form->createView(),
            'movies' => $movieList,
            'series' => $series
        );
    }

    private function getCity(Request $request){
        return $request->attributes->get('city');
    }

    private function createComboForm($city){
        return $this->get('form.factory')->createNamed('', 'search_form', null, array('city' => $city));
    }
}
