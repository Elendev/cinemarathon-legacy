<?php
/**
 * Created by PhpStorm.
 * User: jonas
 * Date: 05.05.14
 * Time: 21:46
 */

namespace MO\Bundle\MovieBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateCacheCommand extends Command implements ContainerAwareInterface {

    /**
     * @var ContainerInterface
     */
    private $container;

    protected function configure(){
        $this->setName('mo:movie:update-cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $this->container->get('mo_movie.provider.pathe')->updateCache();
    }

    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }
} 