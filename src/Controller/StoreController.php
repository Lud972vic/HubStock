<?php

namespace App\Controller;

use App\Entity\Store;
use App\Entity\Assignment;
use App\Form\StoreType;
use App\Repository\StoreRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\AssignmentRepository;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/store')]
/**
 * CRUD des magasins avec audit des actions.
 *
 * - Index: liste paginée et filtrée
 * - New/Edit/Delete: créent des entrées d’audit (create/update/delete)
 * - Show: expose les métadonnées (créé par, modifié par) et l’historique
 */
final class StoreController extends AbstractController
{
    /** Liste des magasins avec pagination et filtre par nom */
    #[Route(name: 'app_store_index', methods: ['GET'])]
    public function index(Request $request, StoreRepository $storeRepository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $q = trim((string) $request->query->get('q', ''));
        $includeArchived = ($request->query->get('archived') === '1');
        $qb = $storeRepository->createQueryBuilder('s');
        if (!$includeArchived) {
            $qb->andWhere('s.deletedAt IS NULL');
        }
        if ($q !== '') {
            $qb->andWhere('LOWER(s.name) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(s.id)')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;

        $list = $qb->orderBy('s.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('store/index.html.twig', [
            'stores' => $list,
            'q' => $q,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'archived' => $includeArchived,
        ]);
    }

    /** Crée un nouveau magasin et journalise l’audit de création */
    #[Route('/new', name: 'app_store_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $store = new Store();
        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($store);
            $entityManager->flush();

            // Audit: création magasin (qui, quoi, sur quel id)
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('Création')
                ->setEntityClass(\App\Entity\Store::class)
                ->setEntityId((int) $store->getId());
            $entityManager->persist($audit);
            $entityManager->flush();

            return $this->redirectToRoute('app_store_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('store/new.html.twig', [
            'store' => $store,
            'form' => $form,
        ]);
    }

    /** Affiche le détail et l’historique d’audit d’un magasin */
    #[Route('/{id}', name: 'app_store_show', methods: ['GET'])]
    public function show(Store $store, EntityManagerInterface $entityManager, AssignmentRepository $assignmentRepository): Response
    {
        // Mouvements associés à ce magasin
        $movements = $entityManager->getRepository(\App\Entity\Movement::class)
            ->findBy(['store' => $store], ['occurredAt' => 'DESC', 'id' => 'DESC']);

        $repo = $entityManager->getRepository(\App\Entity\Audit::class);
        $audits = $repo->findBy([
            'entityClass' => \App\Entity\Store::class,
            'entityId' => (int) $store->getId(),
        ], ['occurredAt' => 'DESC', 'id' => 'DESC']);

        $created = $repo->findOneBy([
            'entityClass' => \App\Entity\Store::class,
            'entityId' => (int) $store->getId(),
            'action' => 'Création',
        ], ['occurredAt' => 'ASC', 'id' => 'ASC']);
        $lastUpdate = $repo->findOneBy([
            'entityClass' => \App\Entity\Store::class,
            'entityId' => (int) $store->getId(),
            'action' => 'Actualisation',
        ], ['occurredAt' => 'DESC', 'id' => 'DESC']);

        $assignments = $assignmentRepository->findBy(['store' => $store], ['assignedAt' => 'DESC']);

        return $this->render('store/show.html.twig', [
            'store' => $store,
            'movements' => $movements,
            'audits' => $audits,
            'createdBy' => $created ? $created->getUser() : null,
            'createdAt' => $created ? $created->getOccurredAt() : null,
            'updatedBy' => $lastUpdate ? $lastUpdate->getUser() : null,
            'updatedAt' => $lastUpdate ? $lastUpdate->getOccurredAt() : null,
            'assignments' => $assignments,
        ]);
    }

    /** Modifie un magasin et journalise l’audit de modification */
    #[Route('/{id}/edit', name: 'app_store_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Store $store, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Audit: modification magasin
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('Actualisation')
                ->setEntityClass(\App\Entity\Store::class)
                ->setEntityId((int) $store->getId());
            $entityManager->persist($audit);
            $entityManager->flush();

            return $this->redirectToRoute('app_store_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('store/edit.html.twig', [
            'store' => $store,
            'form' => $form,
        ]);
    }

    /** Archive (soft delete) un magasin et journalise l’action */
    #[Route('/{id}', name: 'app_store_delete', methods: ['POST'])]
    public function delete(Request $request, Store $store, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$store->getId(), $request->getPayload()->getString('_token'))) {
            if ($store->getDeletedAt() !== null) {
                $this->addFlash('info', 'Ce magasin est déjà archivé.');
                return $this->redirectToRoute('app_store_index');
            }

            // Soft delete
            $store->setDeletedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Audit: soft delete
            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('Suppression')
                ->setEntityClass(\App\Entity\Store::class)
                ->setEntityId((int) $store->getId());
            $entityManager->persist($audit);
            $entityManager->flush();
            $this->addFlash('success', 'Magasin archivé (soft delete).');
        }

        return $this->redirectToRoute('app_store_index', [], Response::HTTP_SEE_OTHER);
    }

    /** Restaure un magasin archivé et journalise l’action */
    #[Route('/{id}/restore', name: 'app_store_restore', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(Request $request, Store $store, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('restore'.$store->getId(), $request->getPayload()->getString('_token'))) {
            if ($store->getDeletedAt() === null) {
                $this->addFlash('info', 'Ce magasin n\'est pas archivé.');
                return $this->redirectToRoute('app_store_index');
            }

            $store->setDeletedAt(null);
            $entityManager->flush();

            $audit = (new \App\Entity\Audit())
                ->setUser($this->getUser())
                ->setAction('Restauration')
                ->setEntityClass(\App\Entity\Store::class)
                ->setEntityId((int) $store->getId());
            $entityManager->persist($audit);
            $entityManager->flush();
            $this->addFlash('success', 'Magasin restauré.');
        }

        return $this->redirectToRoute('app_store_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Génère un PDF des matériels affectés au magasin avec zone de signature.
     *
     * Étapes:
     * 1) Récupérer les affectations du magasin (non archivées) avec leur matériel.
     * 2) Calculer la quantité restante (quantity - returnedQuantity) et filtrer les lignes actives (> 0).
     * 3) Rendre le template Twig `store/assigned_pdf.html.twig` avec `store`, `rows`, `generatedAt`.
     * 4) Configurer Dompdf (HTML5 parser, ressources locales uniquement) et produire un PDF A4 portrait.
     *
     * Personnalisation:
     * - Le logo utilisé par le PDF est défini dans le template via `/img/aldi-logo.png` (dossier `public/img`).
     */
    #[Route('/{id}/assigned.pdf', name: 'app_store_assigned_pdf', methods: ['GET'])]
    public function assignedPdf(Store $store, EntityManagerInterface $entityManager): Response
    {
        // Affectations et matériel lié
        $repo = $entityManager->getRepository(Assignment::class);
        $assignments = $repo->createQueryBuilder('a')
            ->leftJoin('a.equipment', 'e')
            ->addSelect('e')
            ->andWhere('a.store = :store')
            ->setParameter('store', $store)
            ->andWhere('a.deletedAt IS NULL')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();

        // Filtre: ne conserver que les lignes avec quantité non retournée (> 0)
        $active = [];
        foreach ($assignments as $a) {
            $qty = (int) $a->getQuantity();
            $ret = (int) ($a->getReturnedQuantity() ?? 0);
            $remaining = max(0, $qty - $ret);
            if ($remaining > 0) {
                $active[] = ['a' => $a, 'remaining' => $remaining];
            }
        }

        // Rendu HTML du template PDF
        $html = $this->renderView('store/assigned_pdf.html.twig', [
            'store' => $store,
            'rows' => $active,
            'generatedAt' => new \DateTimeImmutable(),
            // Image locale pour Dompdf: chemin relatif depuis /public
            'logoSrc' => (function(string $projectDir): string {
                $primary = $projectDir.'/public/img/aldi-logo.png';
                $fallback = $projectDir.'/public/img/entrepot.jpg';
                return is_file($primary) ? '/img/aldi-logo.png' : (is_file($fallback) ? '/img/entrepot.jpg' : '/img/aldi-logo.png');
            })($this->getParameter('kernel.project_dir')),
        ]);

        // Configuration Dompdf: parser HTML5 et ressources locales (isRemoteEnabled=false)
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        // Restreindre l’accès aux ressources au dossier public (chroot)
        $options->setChroot($this->getParameter('kernel.project_dir').'/public');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Nom de fichier informatif: Affectations_<Nom>_<Horodatage>
        $filename = sprintf('Affectations_%s_%s.pdf', preg_replace('/\s+/', '_', (string) $store->getName()), (new \DateTime())->format('Ymd_His'));
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
