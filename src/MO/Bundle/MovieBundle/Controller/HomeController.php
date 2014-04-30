<?php

namespace MO\Bundle\MovieBundle\Controller;

use MO\Bundle\MovieBundle\Model\Movie;
use MO\Bundle\MovieBundle\MovieDataProviders\PatheProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends Controller
{
    /**
     * @Route("/", name="mo_movie.movie_list")
     * @Template()
     */
    public function homeAction()
    {
        $movies = $this->get('mo_movie.manager.movie_manager')->getCurrentMovies();

        return array(
            'movies' => $movies,
            'form' => $this->createComboForm($movies)->createView()
        );
    }

    /**
     * @Route("/movieDetail", name="mo_movie.movie_detail")
     * @Template()
     */
    public function movieDetailAction(Request $request)
    {
        $url = $request->query->get('movie_url', null);

        if($url){
            $movie = $this->get('mo_movie.manager.movie_manager')->getMovieFromUrl($url);
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
     * @Template()
     * @param Request $request
     */
    public function moviesTimelineAction(Request $request){
        $movies = $this->get('mo_movie.manager.movie_manager')->getCurrentMovies();

        $form = $this->createComboForm($movies);

        $form->handleRequest($request);

        $movieList = array();
        $series = array();

        if($form->isValid()){
            $moviesToUse = $form->get('movies')->getData();

            foreach($moviesToUse as $movieUrl){
                $movieList[] = $this->get('mo_movie.manager.movie_manager')->getMovieFromUrl($movieUrl);
            }

            $options = array(
                'same_cinema' => $form->get('same_cinema')->getData(),
                'same_hall' => $form->get('same_hall')->getData(),
            );

            $series = $this->get('mo_movie.manager.movie_matcher')->getSeries($movieList, $options);
        }

        return array(
            'form' => $form->createView(),
            'movies' => $movieList,
            'series' => $series
        );
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
            ->add('movies', 'genemu_jqueryselect2_choice', array('choices' => $movieArray, 'multiple' => true))
            ->add('same_cinema', 'checkbox', array('required' => false, 'label' => 'Même cinéma', 'attr' => array('checked'   => 'checked')))
            ->add('same_hall', 'checkbox', array('required' => false, 'label' => 'Même salle'))
            ->add('submit', 'submit', array('label' => 'GOGOGO' ));
        return $formBuilder->getForm();
    }
}
