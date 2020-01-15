<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Episode;
use App\Entity\Rating;
use App\Entity\User;
use App\Entity\Series;
use App\Form\SeriesType;
use App\Form\RatingType;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{
    /**
     * @Route("/", name="series_index", methods={"GET", "POST"})
     * 
     */
    public function index(PaginatorInterface $paginator,  Request $request): Response
    {
        $search = "";
        if($_SERVER["REQUEST_METHOD"]  === 'POST')
        {
            $search = $_POST['search'];
        }
        if($search == ""){
             $series = $this->getDoctrine()
            ->getManager()
            ->getRepository(Series::class)
            ->findBy(array(), array('id' => 'asc'));
        }
        else{
            $series = $this->getDoctrine()
            ->getRepository(Series::class);

            $query = $series->createQueryBuilder('a')
               ->where('a.title LIKE :search')
               ->setParameter('search', '%'.$search.'%')
               ->getQuery();
                $series = $query->getResult();
        }
        $pagination = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1), /*page number*/
            10 /*limite par page*/
        );

        foreach ($series as $key => $value) {
            $value->setPoster(base64_encode(stream_get_contents($value->getPoster())));
        }

        return $this->render('series/index.html.twig', [
            'series' => $pagination,
            'user' => $this->getUser()
        ]);
    }
    /**
     * @Route("/search", name="navbar_action", methods={"GET","POST"})
     */
    public function navbar_action(Request $r)
    {
        $request=$this->get('r');

 
        $adr = $_POST['search'];

    }

    /**
     * @Route("/new", name="series_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $series = new Series();
        /*$form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('series_index');
        }
        */
        $laSerieImdb = "tt0071075";
        $content = 'La série existe déjà';
        $Testseries = $this->getDoctrine()
            ->getManager()
            ->getRepository(Series::class)
            ->findOneBy(array('imdb' => $laSerieImdb));

        if (!$Testseries) {

            $client = HttpClient::create();
            $response = $client->request('GET', 'http://www.omdbapi.com/?i='. $laSerieImdb .'&apikey=3c43f1be');

            $content = $response->toArray();

            $series->setTitle($content['Title']);
            $series->setPlot($content['Plot']);
            $series->setImdb($content['imdbID']);
            $series->setDirector($content['Director']);
            $series->setYoutubeTrailer('https://www.youtube.com/watch?v=TUEhJmjfC28');
            $series->setAwards($content['Awards']);
            if (strlen($content['Year'] == 4)) {
                $series->setYearStart(intval($content['Year']));
            } else {
                $series->setYearStart(intval(substr($content['Year'], 0, 4)));
                $series->setYearEnd(intval(substr($content['Year'], 4, 8)));
            }


            $clientPoster = HttpClient::create();
            $responsePoster = $clientPoster->request('GET', 'http://img.omdbapi.com/?i='. $laSerieImdb .'&apikey=3c43f1be');
            $contentPoster = $responsePoster->getContent();
            //$series->setPoster(base64_encode(stream_get_contents($contentPoster)));
            $series->setPoster($contentPoster);

            $em = $this->getDoctrine()->getManager();
            $em->persist($series);
            $em->flush();


            $nbSeason = intval($content['totalSeasons']);

            for ($i = 1; $i <= $nbSeason; $i++) {
                $season = new Season();
                $season->setSeries($series);
                $season->setNumber($i);
                $em = $this->getDoctrine()->getManager();
                $em->persist($season);
                $em->flush();
            }

            $content = $response->getContent();
        }
        
        return $this->render('series/new.html.twig', [
            'series' => $content
        ]);
    }

    /**
     * @Route("/search/{id}", name="series_show", methods={"GET", "POST"})
     */
    public function show(Series $series, Request $request): Response
    {
        //-----------Début formulaire My list----------------
        if ($this->getUser()) {
            $user = $this->getUser();

            // On ajoute les champs de l'entité que l'on veut à notre formulaire
            $form2 = $this->get('form.factory')->createBuilder()

                ->getForm();

            if ($request->isMethod('POST')) {
                $form2->handleRequest($request);
                $user->addSeries($series);
                if ($form2->isSubmitted() && $form2->isValid()) {
                    //Sauvegarde de user
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();
                }
            }
        }
        //------------Fin formulaire My list----------------

        //-----------Début formulaire Rating----------------
        $rating = new Rating();

        // On ajoute les champs de l'entité que l'on veut à notre formulaire
        $form = $this->get('form.factory')->createBuilder(RatingType::class, $rating)
            ->add('value',      IntegerType::class, ['attr' => ['min' => 0, 'max' => 5]])
            ->add('comment',    TextType::class, array('required' => false))
            ->getForm();

        if ($request->isMethod('POST')) {


            $form->handleRequest($request);
            $rating->setSeries($series);
            $rating->setUser($this->getUser());
            if ($form->isSubmitted() && $form->isValid()) {

                //Sauvegarde de user
                $em = $this->getDoctrine()->getManager();
                $em->persist($rating);
                $em->flush();

                //return $this->redirectToRoute('series_index');
            }
        }
        //------------Fin formulaire Rating----------------

        $seasons = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findBy(array('series' => $series));

        $image = $this->getDoctrine()
            ->getManager()
            ->getRepository(Series::class)
            ->findBy(array('id' => $series));

        $rate = $this->getDoctrine()
            ->getRepository(Rating::class)
            ->findBy(array('series' => $series));

        foreach ($image as $key => $value) {
            $value->setPoster(base64_encode(stream_get_contents($value->getPoster())));
        }

        if ($this->getUser()) {
            //Si l'utilisateur est connecté
            return $this->render('series/show.html.twig', [
                'series' => $series,
                'seasons' => $seasons,
                'images' => $image,
                'rating' => $rate,
                'user' => $this->getUser(),
                'form' => $form->createView(),
                'form2' => $form2->createView()
            ]);
        }

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'images' => $image,
            'rating' => $rate
        ]);
    }

    /**
     * @Route("/{id}/edit", name="series_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Series $series): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('series_index');
        }

        return $this->render('series/edit.html.twig', [
            'series' => $series,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="series_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Series $series): Response
    {
        if ($this->isCsrfTokenValid('delete' . $series->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('series_index');
    }

    /**
     * @Route("/mySeries", name="mes_series_index", methods={"GET"})
     */
    public function mySeries(PaginatorInterface $paginator,  Request $request): Response
    {
        if ($this->getUser()) {
            return $this->render('series/mesSeries.html.twig', [
                'user' => $this->getUser()
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/{id}/image", name="series_image", methods={"GET"})
     */
    public function afficherImage(Series $series): Response
    {
        $image = $this->getDoctrine()
            ->getManager()
            ->getRepository(Series::class)
            ->findBy(array('id' => $series));

        foreach ($image as $key => $value) {
            $value->setPoster(base64_encode(stream_get_contents($value->getPoster())));
        }

        return $this->render('series/afficherImage.html.twig', [
            'images' => $image,
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/{recherche}", name="series_recherche", methods={"GET"})
     */
    public function recherche(string $recherche): Response
    {
        $series = $this->getDoctrine()
            ->getRepository(Series::class)
            ->findBy(array('title' => $recherche));

        return $this->render('series/index.html.twig', [
            'series' => $series
        ]);
    }

    /**
     * @Route("/search/{id}/Saison{numSaison}", name="index_episode_show", methods={"GET"})
     */
    public function indexEpisode(Series $serie, int $numSaison): Response
    {
        $season = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findOneBy(array('series' => $serie, 'number' => $numSaison));

        $episodes = $this->getDoctrine()
            ->getRepository(Episode::class)
            ->findBy(array('season' => $season), array('number' => 'asc'));

        return $this->render('series/afficherIndexEpisode.html.twig', [
            'series' => $serie,
            'season' => $season,
            'episodes' => $episodes,
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/search/{id}/{idSaison}/{idEp}", name="episode_show", methods={"GET"})
     */
    public function showEpisode(Series $serie, int $idSaison, int $idEp): Response
    {
        $season = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findOneBy(array('id' => $idSaison));

        $episode = $this->getDoctrine()
            ->getRepository(Episode::class)
            ->findOneBy(array('id' => $idEp));

        return $this->render('series/afficherEpisode.html.twig', [
            'series' => $serie,
            'season' => $season,
            'episode' => $episode,
            'user' => $this->getUser()
        ]);
    }
}
