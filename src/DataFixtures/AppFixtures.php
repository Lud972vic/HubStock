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
        $user->setEmail('admin@local.fr');
        $user->setFullName('Administrateur');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin@local.fr'));
        $manager->persist($user);

        // Les user
        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $faker = \Faker\Factory::create('fr_FR');
            $user->setEmail($faker->email);
            $user->setFullName($faker->name);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $faker->email));
            $manager->persist($user);
        }

        // Stores
        $srValues = ['LYO', 'ABL', 'HON', 'TOU', 'CES'];
        $statutValues = ['fermé', 'en activité'];
        $typeDeProjetValues = ['Création', 'Modernisation', 'Transfert', 'Rénovation'];
        for ($i = 0; $i < 15; $i++) {
            $store = new Store();
            $faker = \Faker\Factory::create('fr_FR');
            $store->setName($faker->unique()->company);
            $store->setAddress($faker->streetAddress . ', ' . $faker->countryCode() . ', ' . $faker->region());
            $store->setSr($srValues[array_rand($srValues)]);
            $store->setCodeFR('FR' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT));
            $store->setStatut($statutValues[array_rand($statutValues)]);
            $store->setTypeDeProjet($typeDeProjetValues[array_rand($typeDeProjetValues)]);
            $store->setDateOuverture($faker->dateTimeThisCentury);
            $manager->persist($store);
            $stores[] = $store;
        }

        // Categories
        $categoryNames = ['Meuble', 'Caisse', 'Informatique', 'Entretien'];
        $categories = [];
        foreach ($categoryNames as $name) {
            $cat = new Category();
            $cat->setName($name);
            $manager->persist($cat);
            $categories[] = $cat;
        }

        // Equipment
        $states = ['Neuf', 'Utilisé', 'Endommagé'];
        $equipments = [];
        for ($i = 1; $i <= 25; $i++) {
            $eq = new Equipment();
            $faker = \Faker\Factory::create('fr_FR');
            $eq->setName(ucfirst($faker->words(2, true)));
            $eq->setReference("Réf ." . $faker->siren());
            $eq->setCategory($categories[array_rand($categories)]);
            $eq->setState($states[array_rand($states)]);
            $eq->setStockQuantity(random_int(10, 50));
            $manager->persist($eq);
            $equipments[] = $eq;
        }

        // Assignments
        for ($i = 0; $i < 25; $i++) {
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
