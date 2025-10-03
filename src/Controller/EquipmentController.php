<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Form\EquipmentType;
use App\Repository\EquipmentRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface as ORMEntityManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/equipment')]
/**
 * Gestion des matériels avec audit des actions et mouvements de stock.
 *
 * - Index: liste paginée et filtre par catégorie/texte
 * - New/Edit/Delete: audit create/update/delete
 * - Show: détails, mouvements, et historique d’audit
 * - Adjust: enregistre un mouvement d’ajustement (performedBy si connecté)
 */
final class EquipmentController extends AbstractController
{
    /** Liste des matériels, pagination et filtres */
    #[Route(name: 'app_equipment_index', methods: ['GET'])]
    public function index(Request $request, EquipmentRepository $equipmentRepository, ORMEntityManagerInterface $em): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $categoryId = (int) $request->query->get('category', 0);
        $q = trim((string) $request->query->get('q', ''));
        $includeArchived = ($request->query->get('archived') === '1');
        $qb = $equipmentRepository->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ;
        if (!$includeArchived) {
            $qb->andWhere('e.deletedAt IS NULL');
        }
        if ($categoryId > 0) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }
        if ($q !== '') {
            $qb->andWhere('(LOWER(e.name) LIKE :q OR LOWER(e.reference) LIKE :q)')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $list = $qb->orderBy('e.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Categories for filter select
        $categories = $em->getRepository(\App\Entity\Category::class)->findBy([], ['name' => 'ASC']);

        return $this->render('equipment/index.html.twig', [
            'equipment' => $list,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'categoryId' => $categoryId > 0 ? $categoryId : null,
            'categories' => $categories,
            'q' => $q,
            'archived' => $includeArchived,
        ]);
    }

    /** Crée un nouveau matériel et journalise l’audit de création */
    #[Route('/new', name: 'app_equipment_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($equipment);
            $entityManager->flush();

            // Audit: création matériel
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('create')
                ->setEntityClass(\App\Entity\Equipment::class)
                ->setEntityId((int) $equipment->getId());
            $entityManager->persist($audit);
            $entityManager->flush();

            return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipment/new.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    /** Affiche le détail du matériel, ses mouvements et l’historique d’audit */
    #[Route('/{id}', name: 'app_equipment_show', methods: ['GET'])]
    public function show(Equipment $equipment, ORMEntityManagerInterface $em): Response
    {
        $movements = $em->getRepository(\App\Entity\Movement::class)
            ->findBy(['equipment' => $equipment], ['occurredAt' => 'DESC', 'id' => 'DESC']);

        $stores = $em->getRepository(\App\Entity\Store::class)->findBy([], ['name' => 'ASC']);

        // Audits for this equipment
        $repo = $em->getRepository(\App\Entity\Audit::class);
        $audits = $repo->findBy([
            'entityClass' => \App\Entity\Equipment::class,
            'entityId' => (int) $equipment->getId(),
        ], ['occurredAt' => 'DESC', 'id' => 'DESC']);
        $created = $repo->findOneBy([
            'entityClass' => \App\Entity\Equipment::class,
            'entityId' => (int) $equipment->getId(),
            'action' => 'create',
        ], ['occurredAt' => 'ASC', 'id' => 'ASC']);
        $lastUpdate = $repo->findOneBy([
            'entityClass' => \App\Entity\Equipment::class,
            'entityId' => (int) $equipment->getId(),
            'action' => 'update',
        ], ['occurredAt' => 'DESC', 'id' => 'DESC']);

        return $this->render('equipment/show.html.twig', [
            'equipment' => $equipment,
            'movements' => $movements,
            'stores' => $stores,
            'audits' => $audits,
            'createdBy' => $created ? $created->getUser() : null,
            'createdAt' => $created ? $created->getOccurredAt() : null,
            'updatedBy' => $lastUpdate ? $lastUpdate->getUser() : null,
            'updatedAt' => $lastUpdate ? $lastUpdate->getOccurredAt() : null,
        ]);
    }

    /** Ajuste le stock (augmentation ou diminution) et enregistre un mouvement */
    #[Route('/{id}/adjust', name: 'app_equipment_adjust', methods: ['POST'])]
    public function adjust(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('adjust'.$equipment->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_equipment_show', ['id' => $equipment->getId()]);
        }

        $direction = $request->getPayload()->getString('direction'); // 'increase' | 'decrease'
        $qty = max(0, (int) $request->getPayload()->get('quantity', 0));

        if ($qty <= 0) {
            $this->addFlash('danger', 'Quantité invalide.');
            return $this->redirectToRoute('app_equipment_show', ['id' => $equipment->getId()]);
        }


        $delta = ($direction === 'decrease') ? -$qty : $qty;

        // Validation de stock non négatif
        if ($delta < 0 && $equipment->getStockQuantity() + $delta < 0) {
            $this->addFlash('danger', 'Stock insuffisant pour diminuer de ' . $qty . '.');
            return $this->redirectToRoute('app_equipment_show', ['id' => $equipment->getId()]);
        }

        // Appliquer l'ajustement
        $equipment->setStockQuantity($equipment->getStockQuantity() + $delta);
        $entityManager->persist($equipment);

        // Enregistrer un mouvement d'ajustement (sans affectation)
        $movement = new \App\Entity\Movement();
        $movement->setEquipment($equipment);
        $movement->setStore(null);
        $movement->setAssignment(null);
        $movement->setType('ajustement');
        $movement->setQuantity($qty);
        $movement->setOccurredAt(new \DateTimeImmutable());
        if ($this->getUser()) {
            $movement->setPerformedBy($this->getUser());
        }
        $entityManager->persist($movement);

        $entityManager->flush();

        $verb = $delta >= 0 ? 'augmenté' : 'diminué';
        $this->addFlash('success', "Stock $verb de $qty unité(s). Mouvement enregistré.");

        return $this->redirectToRoute('app_equipment_show', ['id' => $equipment->getId()]);
    }

    /** Modifie un matériel et journalise l’audit de modification */
    #[Route('/{id}/edit', name: 'app_equipment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Audit: modification matériel
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('update')
                ->setEntityClass(\App\Entity\Equipment::class)
                ->setEntityId((int) $equipment->getId());
            $entityManager->persist($audit);
            $entityManager->flush();

            return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    /** Archive (soft delete) un matériel et journalise l’action */
    #[Route('/{id}', name: 'app_equipment_delete', methods: ['POST'])]
    public function delete(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipment->getId(), $request->getPayload()->getString('_token'))) {
            if ($equipment->getDeletedAt() !== null) {
                $this->addFlash('info', 'Ce matériel est déjà archivé.');
                return $this->redirectToRoute('app_equipment_index');
            }

            // Soft delete: marquer comme archivé
            $equipment->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Audit: soft delete
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('soft_delete')
                ->setEntityClass(\App\Entity\Equipment::class)
                ->setEntityId((int) $equipment->getId());
            $entityManager->persist($audit);
            $entityManager->flush();
            $this->addFlash('success', 'Matériel archivé (soft delete).');
        }

        return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
    }

    /** Restaure un matériel archivé (soft delete) et journalise l’action */
    #[Route('/{id}/restore', name: 'app_equipment_restore', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('restore'.$equipment->getId(), $request->getPayload()->getString('_token'))) {
            if ($equipment->getDeletedAt() === null) {
                $this->addFlash('info', 'Ce matériel n\'est pas archivé.');
                return $this->redirectToRoute('app_equipment_index');
            }

            // Restore: enlever l\'archive
            $equipment->setDeletedAt(null);
            $entityManager->flush();

            // Audit: restore
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('restore')
                ->setEntityClass(\App\Entity\Equipment::class)
                ->setEntityId((int) $equipment->getId());
            $entityManager->persist($audit);
            $entityManager->flush();
            $this->addFlash('success', 'Matériel restauré.');
        }

        return $this->redirectToRoute('app_equipment_index', [], Response::HTTP_SEE_OTHER);
    }
}
