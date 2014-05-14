<?php
/**
 * Created by PhpStorm.
 * User: jonas
 * Date: 10.05.14
 * Time: 18:02
 */

namespace MO\Bundle\MovieBundle\Form;


use MO\Bundle\MovieBundle\Manager\MovieManager;
use MO\Bundle\MovieBundle\Model\Movie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\Translator;

class SearchFormType extends AbstractType{

    /**
     * @var MovieManager
     */
    private $movieManager;

    /**
     * @var Router
     */
    private $router;

    private $locale;

    /**
     * @var Request
     */
    private $request;

    public function __construct(MovieManager $movieManager, Router $router, Request $request){
        $this->movieManager = $movieManager;
        $this->router = $router;
        $this->request = $request;
        $this->locale = $request->getLocale();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $movieArray = array();
        foreach($this->movieManager->getCurrentMovies($options) as $movie){
            /* @var $movie Movie */
            $movieArray[$movie->getPageUrl()] = $movie->getName();
        }

        asort($movieArray);

        $nbSeries = array();
        for($i = 2; $i < 6; $i ++){
            $nbSeries[$i] = $i;
        }

        $timeList = array();
        for($i = 5; $i <= 120; $i += 5) {
            $timeList[$i * 60] = $i . ' minutes';
        }

        $hourList = array();
        for($i = 0; $i < 48; $i ++) {
            $hourList[$i * 60 * 30] = $i %2 == 0 ? ($i/2) . 'h00' : (($i-1)/2) .'h30';
        }


        $minDatesList = array();
        $maxDatesList = array();
        $startDate = $this->movieManager->getFirstPerformanceDate($options)->setTime(0, 0);
        $endDate = $this->movieManager->getLastPerformanceDate($options)->setTime(0, 0);

        $dayQuantity = $endDate->diff($startDate)->format('%a') + 1;
        $defaultStartChoice = $startDate->getTimestamp();

        $dateFormatter = new \IntlDateFormatter($this->locale, \IntlDateFormatter::LONG, \IntlDateFormatter::LONG);
        $dateFormatter->setPattern('eeee d MMMM Y');

        for($i = 0; $i < $dayQuantity; $i ++){
            $label = $dateFormatter->format($startDate);//$startDate->format('l d/m/Y');
            $minDatesList[$startDate->getTimestamp()] = $label;
            $startDate->add(new \DateInterval('P1D'));
            $maxDatesList[$startDate->getTimestamp() - 1] = $label;
        }
        $defaultEndChoice = $startDate->getTimestamp() - 1;

        //for mobile, propose normal choice
        $mobileDetect = new \Mobile_Detect();

        $builder
            ->setAction($this->router->generate('mo_movie.movie_timeline'))
            ->setMethod('GET')
            ->add('movies', $mobileDetect->isMobile() ? 'choice' : 'genemu_jqueryselect2_choice', array('choices' => $movieArray, 'required' => true, 'multiple' => true, 'label' => 'Films requis (min. 1)'))
            ->add('same_cinema', 'checkbox', array('required' => false, 'label' => 'Même cinéma', 'attr' => array('checked'   => 'checked')))
            ->add('same_hall', 'checkbox', array('required' => false, 'label' => 'Même salle'))
            ->add('min_time_between', 'choice', array('label' => 'Temps min. entre séances', 'choices' => $timeList, 'data' => 10 * 60))
            ->add('max_time_between', 'choice', array('label' => 'Temps max. entre séances', 'choices' => $timeList, 'data' => 30*60))
            ->add('serie_min_size', 'choice', array('label' => 'Nb. min de séances', 'choices' => $nbSeries, 'data' => 2))
            ->add('serie_max_size', 'choice', array('label' => 'Nb. max de séances', 'choices' => $nbSeries, 'data' => 3))
            ->add('start_time_min', 'choice', array('label' => 'Heure de début min.', 'choices' => $hourList, 'data' => 14*60*60))
            ->add('start_time_max', 'choice', array('label' => 'Heure de début max.', 'choices' => $hourList, 'data' => 60*60*23 + 60*30))
            ->add('date_min', 'choice', array('label' => 'Date min.', 'choices' => $minDatesList, 'data' => $defaultStartChoice))
            ->add('date_max', 'choice', array('label' => 'Date max.', 'choices' => $maxDatesList, 'data' => $defaultEndChoice))
            ->add('submit', 'submit', array('label' => 'Chercher une correspondance' ));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'search_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ))->setRequired(array(
                'locale'
            ));
    }
}