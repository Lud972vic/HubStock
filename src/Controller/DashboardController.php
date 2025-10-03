<?php

namespace App\Controller;

use App\Repository\StoreRepository;
use App\Repository\EquipmentRepository;
use App\Repository\AssignmentRepository;
use App\Repository\MovementRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Tableau de bord d’ensemble.
 *
 * Agrège des indicateurs clés (comptes, stocks, mouvements récents)
 * pour donner une vue rapide de l’état du système.
 */
final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        StoreRepository $storeRepository,
        EquipmentRepository $equipmentRepository,
        AssignmentRepository $assignmentRepository,
        MovementRepository $movementRepository,
        CategoryRepository $categoryRepository
    ): Response {
        // Compteurs simples: nombre d’entités principales
        $stores = $storeRepository->count([]);
        $equipment = $equipmentRepository->count([]);
        $assignments = $assignmentRepository->count([]);

        // Stock total et ruptures (0) via agrégations SQL
        $qbStock = $equipmentRepository->createQueryBuilder('e');
        $stockTotal = (int) ($qbStock->select('COALESCE(SUM(e.stockQuantity), 0)')->getQuery()->getSingleScalarResult());

        $outOfStock = (int) ($equipmentRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.stockQuantity = 0')
            ->getQuery()->getSingleScalarResult());

        // Seuil bas (<= 5)
        $lowStock = (int) ($equipmentRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.stockQuantity > 0')
            ->andWhere('e.stockQuantity <= 5')
            ->getQuery()->getSingleScalarResult());

        // Affectations actives (quantité non retournée) et en attente de retour
        $activeAssigned = (int) ($assignmentRepository->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.quantity - a.returnedQuantity), 0)')
            ->getQuery()->getSingleScalarResult());

        $pendingReturns = (int) ($assignmentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.returnedQuantity < a.quantity')
            ->getQuery()->getSingleScalarResult());

        // Mouvements récents (7 derniers jours)
        $since = new \DateTimeImmutable('-7 days');
        $recentMovements = (int) ($movementRepository->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.occurredAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()->getSingleScalarResult());

        // Catégories
        $categories = $categoryRepository->count([]);

        return $this->render('dashboard/index.html.twig', [
            'stores' => $stores,
            'equipment' => $equipment,
            'assignments' => $assignments,
            'stockTotal' => $stockTotal,
            'outOfStock' => $outOfStock,
            'lowStock' => $lowStock,
            'activeAssigned' => $activeAssigned,
            'pendingReturns' => $pendingReturns,
            'recentMovements' => $recentMovements,
            'categories' => $categories,
        ]);
    }
}