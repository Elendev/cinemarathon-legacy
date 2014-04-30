<?php

namespace MO\Bundle\MovieBundle\Controller;

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

class HomeController extends Controller
{
    /**
     * @Route("/", name="mo_movie.movie_list")
     * @Cache(public=true, vary={"Cookie"}, expires="tomorrow")
     * @Template()
     */
    public function homeAction(Request $request)
    {
        $movies = $this->get('mo_movie.manager.movie_manager')->getCurrentMovies(array(
            'locale' => $this->getCityLocale($request)
        ));

        return array(
            'movies' => $movies,
            'form' => $this->createComboForm($movies)->createView()
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
                'locale' => $this->getCityLocale($request)
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
        $movies = $this->get('mo_movie.manager.movie_manager')->getCurrentMovies(array('locale' => $this->getCityLocale($request)));

        $form = $this->createComboForm($movies);

        $form->handleRequest($request);

        $movieList = array();
        $series = array();

        if($form->isValid()){
            $moviesToUse = $form->get('movies')->getData();

            foreach($moviesToUse as $movieUrl){
                $movieList[] = $this->get('mo_movie.manager.movie_manager')->getMovieFromUrl($movieUrl, array('locale' => $this->getCityLocale($request)));
            }

            $options = array(
                'same_cinema' => $form->get('same_cinema')->getData(),
                'same_hall' => $form->get('same_hall')->getData()
            );

            $series = $this->get('mo_movie.manager.movie_matcher')->getSeries($movieList, $options);
        }

        return array(
            'form' => $form->createView(),
            'movies' => $movieList,
            'series' => $series
        );
    }

    /**
     * @Route("/changeCity/{cityLocale}", name="mo_movie.change_city_locale")
     * @param Request $request
     */
    public function changeCityAction(Request $request, $cityLocale){
        if($cityLocale > 23 || $cityLocale < 20){
            throw $this->createNotFoundException();
        }

        $cookie = new Cookie('city_locale', $cityLocale);
        $response = new RedirectResponse($this->generateUrl('mo_movie.movie_list'));
        $response->headers->setCookie($cookie);

        return $response;
    }

    private function getCityLocale(Request $request){
        return $request->cookies->get('city_locale', 20);
    }

    private function createComboForm($movieList){

        $movieArray = array();

        foreach($movieList as $movie){
            /* @var $movie Movie */
            $movieArray[$movie->getPageUrl()] = $movie->getName();
        }

        $formBuilder = $this->createFormBuilder(null, array('csrf_protection' => false))
            ->setAction($this->generateUrl('mo_movie.movie_timeline'))
            ->setMethod('GET')
            ->add('movies', 'genemu_jqueryselect2_choice', array('choices' => $movieArray, 'multiple' => true, 'label' => 'Films'))
            ->add('same_cinema', 'checkbox', array('required' => false, 'label' => 'Même cinéma', 'attr' => array('checked'   => 'checked')))
            ->add('same_hall', 'checkbox', array('required' => false, 'label' => 'Même salle'))
            ->add('submit', 'submit', array('label' => 'Chercher une correspondance entre les séances' ));
        return $formBuilder->getForm();
    }
}
