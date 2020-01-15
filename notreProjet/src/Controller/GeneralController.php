<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class GeneralController extends AbstractController
{
    /**
     * @Route("/", name="general")
     */
    public function index()
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'GeneralController',
        ]);
    }
     /**
     * @Route("/a-propos", name="infos")
     */
    public function infos()
    {
        return $this->render('infos/informations.html.twig', [
            'controller_name' => 'GeneralController',
        ]);
    }
    /**
     * @Route("/", name="infos")
     */

}
