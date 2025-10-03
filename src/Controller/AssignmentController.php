<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\Movement;
use App\Form\AssignmentType;
use App\Repository\AssignmentRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assignment')]
/**
 * Gestion des affectations entre matériels et magasins, avec suivi d’audit.
 *
 * - New/Edit/Delete: impactent le stock et créent des entrées d’audit
 * - Return: enregistre la restitution (mouvement + audit)
 * - Index/Show: consultation avec filtres et pagination
 */
final class AssignmentController extends AbstractController
{
    /** Liste paginée des affectations avec filtres magasin/matériel */
    #[Route(name: 'app_assignment_index', methods: ['GET'])]
    public function index(Request $request, AssignmentRepository $assignmentRepository, \Doctrine\ORM\EntityManagerInterface $em): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $storeId = $request->query->get('store');
        $storeQ = trim((string) $request->query->get('store_q', ''));
        $equipmentQ = trim((string) $request->query->get('equipment_q', ''));
        $includeArchived = ($request->query->get('archived') === '1');
        $qb = $assignmentRepository->createQueryBuilder('a')
            ->leftJoin('a.store', 's')
            ->leftJoin('a.equipment', 'e')
            ;
        if (!$includeArchived) {
            $qb->andWhere('a.deletedAt IS NULL');
        }
        if ($storeId) {
            $store = $em->getRepository(\App\Entity\Store::class)->find((int)$storeId);
            if ($store) {
                $qb->andWhere('a.store = :store')
                   ->setParameter('store', $store);
            }
        }
        if ($storeQ !== '') {
            $qb->andWhere('LOWER(s.name) LIKE :store_q')
               ->setParameter('store_q', '%'.mb_strtolower($storeQ).'%');
        }
        if ($equipmentQ !== '') {
            $qb->andWhere('(LOWER(e.reference) LIKE :eq_q OR LOWER(e.name) LIKE :eq_q)')
               ->setParameter('eq_q', '%'.mb_strtolower($equipmentQ).'%');
        }

        // Count
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        // List
        $list = $qb->select('a, s, e')
            ->orderBy('a.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $stores = $em->getRepository(\App\Entity\Store::class)->findBy([], ['name' => 'ASC']);

        return $this->render('assignment/index.html.twig', [
            'assignments' => $list,
            'stores' => $stores,
            'storeId' => $storeId ? (int)$storeId : null,
            'storeQ' => $storeQ,
            'equipmentQ' => $equipmentQ,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'archived' => $includeArchived,
        ]);
    }

    /** Crée une affectation, décrémente le stock et journalise l’audit */
    #[Route('/new', name: 'app_assignment_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $assignment = new Assignment();
        $form = $this->createForm(AssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $equipment = $assignment->getEquipment();
            $store = $assignment->getStore();
            if (($equipment && $equipment->getDeletedAt() !== null) || ($store && $store->getDeletedAt() !== null)) {
                $this->addFlash('danger', "Impossible d'affecter un matériel ou un magasin archivé.");
            } else {
                // Décrément du stock disponible
                $qty = $assignment->getQuantity();
                if ($equipment->getStockQuantity() < $qty) {
                    $this->addFlash('danger', 'Stock insuffisant pour ce matériel.');
                } else {
                    $equipment->setStockQuantity($equipment->getStockQuantity() - $qty);
                    $entityManager->persist($equipment);
                    if ($this->getUser()) {
                        $assignment->setCreatedBy($this->getUser());
                    }
                    $entityManager->persist($assignment);

                    // Mouvement: ajout (trace la sortie vers le magasin)
                    $movement = new Movement();
                    $movement->setAssignment($assignment)
                        ->setEquipment($equipment)
                        ->setStore($assignment->getStore())
                        ->setType('ajout')
                        ->setQuantity($qty);
                    if ($this->getUser()) {
                        $movement->setPerformedBy($this->getUser());
                    }
                    $entityManager->persist($movement);

                    $entityManager->flush();

                    // Audit: création affectation
                    $audit = (new \App\Entity\Audit())
                        ->setUser($this->getUser())
                        ->setAction('create')
                        ->setEntityClass(\App\Entity\Assignment::class)
                        ->setEntityId((int) $assignment->getId());
                    $entityManager->persist($audit);
                    $entityManager->flush();

                    $this->addFlash('success', 'Affectation créée et stock mis à jour.');
                    return $this->redirectToRoute('app_assignment_index', [], Response::HTTP_SEE_OTHER);
                }
            }
        }

        return $this->render('assignment/new.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    /** Affiche le détail d’une affectation */
    #[Route('/{id}', name: 'app_assignment_show', methods: ['GET'])]
    public function show(Assignment $assignment): Response
    {
        return $this->render('assignment/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }

    /** Modifie une affectation (ajuste le stock si quantité change) et journalise l’audit */
    #[Route('/{id}/edit', name: 'app_assignment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        $originalQty = $assignment->getQuantity();
        $form = $this->createForm(AssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $equipment = $assignment->getEquipment();
            $store = $assignment->getStore();
            if (($equipment && $equipment->getDeletedAt() !== null) || ($store && $store->getDeletedAt() !== null)) {
                $this->addFlash('danger', "Impossible de modifier une affectation vers un matériel ou un magasin archivé.");
                return $this->redirectToRoute('app_assignment_edit', ['id' => $assignment->getId()]);
            }
            $newQty = $assignment->getQuantity();
            $delta = $newQty - $originalQty;
            if ($delta > 0) {
                // Besoin de stock supplémentaire
                if ($equipment->getStockQuantity() < $delta) {
                    $this->addFlash('danger', 'Stock insuffisant pour augmenter la quantité affectée.');
                    return $this->redirectToRoute('app_assignment_edit', ['id' => $assignment->getId()]);
                }
                $equipment->setStockQuantity($equipment->getStockQuantity() - $delta);
            } elseif ($delta < 0) {
                // Restitution du surplus
                $equipment->setStockQuantity($equipment->getStockQuantity() + abs($delta));
            }
            $entityManager->flush();

            // Audit: modification affectation
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('update')
                ->setEntityClass(\App\Entity\Assignment::class)
                ->setEntityId((int) $assignment->getId());
            $entityManager->persist($audit);
            $entityManager->flush();

            return $this->redirectToRoute('app_assignment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('assignment/edit.html.twig', [
            'assignment' => $assignment,
            'form' => $form,
        ]);
    }

    /** Archive (soft delete) une affectation et journalise l’action */
    #[Route('/{id}', name: 'app_assignment_delete', methods: ['POST'])]
    public function delete(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$assignment->getId(), $request->getPayload()->getString('_token'))) {
            if ($assignment->getDeletedAt() !== null) {
                $this->addFlash('info', 'Cette affectation est déjà archivée.');
                return $this->redirectToRoute('app_assignment_index');
            }

            // Soft delete
            $assignment->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Audit: soft delete
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('soft_delete')
                ->setEntityClass(\App\Entity\Assignment::class)
                ->setEntityId((int) $assignment->getId());
            $entityManager->persist($audit);
            $entityManager->flush();
            $this->addFlash('success', 'Affectation archivée (soft delete).');
        }

        return $this->redirectToRoute('app_assignment_index', [], Response::HTTP_SEE_OTHER);
    }

    /** Restaure une affectation archivée et journalise l’action */
    #[Route('/{id}/restore', name: 'app_assignment_restore', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('restore'.$assignment->getId(), $request->getPayload()->getString('_token'))) {
            if ($assignment->getDeletedAt() === null) {
                $this->addFlash('info', 'Cette affectation n\'est pas archivée.');
                return $this->redirectToRoute('app_assignment_index');
            }

            $assignment->setDeletedAt(null);
            $entityManager->flush();

            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('restore')
                ->setEntityClass(\App\Entity\Assignment::class)
                ->setEntityId((int) $assignment->getId());
            $entityManager->persist($audit);
            $entityManager->flush();
            $this->addFlash('success', 'Affectation restaurée.');
        }

        return $this->redirectToRoute('app_assignment_index', [], Response::HTTP_SEE_OTHER);
    }

    /** Enregistre un retour partiel/total de l’affectation (mouvement + audit) */
    #[Route('/{id}/return', name: 'app_assignment_return', methods: ['POST'])]
    public function return(Request $request, Assignment $assignment, EntityManagerInterface $entityManager): Response
    {
        $returnedQty = max(0, (int) $request->getPayload()->getInt('returned_quantity'));
        $remaining = $assignment->getQuantity() - $assignment->getReturnedQuantity();
        if ($returnedQty <= 0 || $returnedQty > $remaining) {
            $this->addFlash('danger', 'Quantité de retour invalide.');
            return $this->redirectToRoute('app_assignment_show', ['id' => $assignment->getId()]);
        }

        $assignment->setReturnedQuantity($assignment->getReturnedQuantity() + $returnedQty);
        $assignment->setReturnedAt(new \DateTimeImmutable());
        if ($this->getUser()) {
            $assignment->setReturnedBy($this->getUser());
        }

        $equipment = $assignment->getEquipment();
        $equipment->setStockQuantity($equipment->getStockQuantity() + $returnedQty);

        // Mouvement: retour (trace la restitution du stock vers le dépôt)
        $movement = new Movement();
        $movement->setAssignment($assignment)
            ->setEquipment($equipment)
            ->setStore($assignment->getStore())
            ->setType('retour')
            ->setQuantity($returnedQty);
        if ($this->getUser()) {
            $movement->setPerformedBy($this->getUser());
        }
        $entityManager->persist($movement);

        $entityManager->flush();

        // Audit: retour affectation
        $audit = (new \App\Entity\Audit())
            ->setUser($this->getUser())
            ->setAction('return')
            ->setEntityClass(\App\Entity\Assignment::class)
            ->setEntityId((int) $assignment->getId());
        $entityManager->persist($audit);
        $entityManager->flush();

        $this->addFlash('success', 'Retour enregistré et stock remis à jour.');
        return $this->redirectToRoute('app_assignment_show', ['id' => $assignment->getId()]);
    }
}
