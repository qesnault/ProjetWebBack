<?php

namespace App\Controller;

use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
/**
 * @Route("/")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/allUsers", name="user_index", methods={"GET"})
     */
    public function index(): Response
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/register", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
    
        // On ajoute les champs de l'entité que l'on veut à notre formulaire
        $form = $this->get('form.factory')->createBuilder(UserType::class, $user)
            ->add('name',      TextType::class)
            ->add('email',     EmailType::class)
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options'  => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Répeter le mot de passe'),
            ))
            //->add('password',  PasswordType::class)
            ->getForm()
        ;

        if ($request->isMethod('POST')) {
          
        
          $form->handleRequest($request);
          $user->setRegisterDate(new \DateTime());
          if ($form->isSubmitted() && $form->isValid()) {

            //encodage mdp
            $pass = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($pass);

            //Sauvegarde de user
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            //Ici on pourra ajouter par exemple un envoi de mail 
            //ou un message flash pour dire qu'on esr bien enregistre
        
            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
          }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/allUsers/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/allUsers/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/allUsers/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * @Route("/informations", name="user_log_show", methods={"GET"})
     */
    public function showUserLog(): Response
    {
        if($this->getUser()){
            return $this->render('user/show.html.twig', [
                'user' => $this->getUser()
            ]);
        }
        return $this->redirectToRoute('general');
    }
}
