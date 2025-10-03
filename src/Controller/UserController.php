<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findBy([], ['fullName' => 'ASC']);
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['include_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }

            $em->persist($user);
            $em->flush();

            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('create')
                ->setEntityClass(User::class)
                ->setEntityId((int) $user->getId());
            $em->persist($audit);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user, EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(\App\Entity\Audit::class);
        $audits = $repo->findBy([
            'entityClass' => User::class,
            'entityId' => (int) $user->getId(),
        ], ['occurredAt' => 'DESC', 'id' => 'DESC']);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'audits' => $audits,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('update')
                ->setEntityClass(User::class)
                ->setEntityId((int) $user->getId());
            $em->persist($audit);
            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activate', name: 'app_user_activate', methods: ['POST'])]
    public function activate(User $user, EntityManagerInterface $em): Response
    {
        $user->setIsActive(true);
        $em->flush();

        $audit = (new \App\Entity\Audit())
            ->setUser($this->getUser())
            ->setAction('activate')
            ->setEntityClass(User::class)
            ->setEntityId((int) $user->getId());
        $em->persist($audit);
        $em->flush();

        $this->addFlash('success', 'Utilisateur activé.');
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/deactivate', name: 'app_user_deactivate', methods: ['POST'])]
    public function deactivate(User $user, EntityManagerInterface $em): Response
    {
        $user->setIsActive(false);
        $em->flush();

        $audit = (new \App\Entity\Audit())
            ->setUser($this->getUser())
            ->setAction('deactivate')
            ->setEntityClass(User::class)
            ->setEntityId((int) $user->getId());
        $em->persist($audit);
        $em->flush();

        $this->addFlash('success', 'Utilisateur désactivé.');
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/password', name: 'app_user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plain));
            $em->flush();

            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('password_change')
                ->setEntityClass(User::class)
                ->setEntityId((int) $user->getId());
            $em->persist($audit);
            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour.');
            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/change_password.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}