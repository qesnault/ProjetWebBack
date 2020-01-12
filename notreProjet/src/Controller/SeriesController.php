<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Episode;
use App\Entity\Series;
use App\Form\SeriesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{
    /**
     * @Route("/", name="series_index", methods={"GET"})
     */
    public function index(PaginatorInterface $paginator,  Request $request): Response
    {
        $series = $this->getDoctrine()
            ->getManager()
            ->getRepository(Series::class)
            ->findBy(array(), array('id' => 'asc'));

        $pagination = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1), /*page number*/
            10 /*limite par page*/
        );

        foreach ($series as $key => $value) {
            $value->setPoster(base64_encode(stream_get_contents($value->getPoster())));
        }
    
        return $this->render('series/index.html.twig', [
            'series' => $pagination
        ]);
    }

    /**
     * @Route("/new", name="series_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $series = new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('series_index');
        }

        return $this->render('series/new.html.twig', [
            'series' => $series,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/recherche/{id}", name="series_show", methods={"GET"})
     */
    public function show(Series $series): Response
    {
        $seasons = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findBy(array('series' => $series));

        $image = $this->getDoctrine()
            ->getManager()
            ->getRepository(Series::class)
            ->findBy(array('id' => $series));

        foreach ($image as $key => $value) {
            $value->setPoster(base64_encode(stream_get_contents($value->getPoster())));
        }

        if($this->getUser()){
            //Si l'utilisateur est connecté
        }

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'images' => $image
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
            'images' => $image
        ]);
    }

    /**
     * @Route("/{recherche}", name="series_recherche", methods={"GET"},
     * requirements={
     *  "pageNumber" = "\d+"
     * }, defaults={"pageNumber" = "1"})
     */
    public function recherche(int $pageNumber, string $recherche): Response
    {
        $series = $this->getDoctrine()
            ->getRepository(Series::class)
            ->findBy(array('title' => $recherche), null, 10, ($pageNumber * 10) - 10);

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'nbPage' => $pageNumber
        ]);
    }

    /**
     * @Route("/recherche/{id}/Saison{numSaison}", name="index_episode_show", methods={"GET"})
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
            'episodes' => $episodes
        ]);
    }

    /**
     * @Route("/recherche/{id}/{idSaison}/{idEp}", name="episode_show", methods={"GET"})
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
            'episode' => $episode
        ]);
    }
}
