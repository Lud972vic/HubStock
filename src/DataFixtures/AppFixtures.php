<?php

namespace App\DataFixtures;

use App\Entity\Store;
use App\Entity\Equipment;
use App\Entity\Category;
use App\Entity\Assignment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // Admin user
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setFullName('Administrateur');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin'));
        $manager->persist($user);

        // Stores
        $storeNames = ['Magasin Centre', 'Magasin Nord', 'Magasin Sud', 'Magasin Est', 'Magasin Ouest'];
        $stores = [];
        foreach ($storeNames as $i => $name) {
            $store = new Store();
            $store->setName($name);
            $store->setAddress('Adresse ' . ($i + 1) . ', Ville');
            $store->setManager('Responsable ' . chr(65 + $i));
            $manager->persist($store);
            $stores[] = $store;
        }

        // Categories
        $categoryNames = ['Informatique', 'Électrique', 'Outillage', 'Sécurité'];
        $categories = [];
        foreach ($categoryNames as $name) {
            $cat = new Category();
            $cat->setName($name);
            $manager->persist($cat);
            $categories[] = $cat;
        }

        // Equipment
        $states = ['neuf', 'utilisé', 'endommagé'];
        $equipments = [];
        for ($i = 1; $i <= 20; $i++) {
            $eq = new Equipment();
            $eq->setName('Équipement ' . $i);
            $eq->setReference(sprintf('EQ-%04d', $i));
            $eq->setCategory($categories[array_rand($categories)]);
            $eq->setState($states[array_rand($states)]);
            $eq->setStockQuantity(random_int(10, 50));
            $manager->persist($eq);
            $equipments[] = $eq;
        }

        // Assignments
        for ($i = 0; $i < 15; $i++) {
            $equipment = $equipments[array_rand($equipments)];
            // Only assign if stock available
            if ($equipment->getStockQuantity() <= 0) {
                continue;
            }
            $quantity = random_int(1, min(5, $equipment->getStockQuantity()));
            $returned = random_int(0, $quantity);

            $assignment = new Assignment();
            $assignment->setEquipment($equipment);
            $assignment->setStore($stores[array_rand($stores)]);
            // Spread assigned date in past ~30 days
            $assignment->setAssignedAt(new \DateTimeImmutable('-' . random_int(0, 30) . ' days'));
            $assignment->setQuantity($quantity);
            $assignment->setReturnedQuantity($returned);
            if ($returned > 0) {
                $assignment->setReturnedAt(new \DateTimeImmutable('-' . random_int(0, 15) . ' days'));
            }

            // Adjust stock to reflect net items out
            $netOut = $quantity - $returned; // items still out of stock
            $equipment->setStockQuantity(max(0, $equipment->getStockQuantity() - $netOut));

            $manager->persist($assignment);
        }

        $manager->flush();
    }
}
