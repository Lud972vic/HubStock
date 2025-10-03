<?php

namespace App\Form;

use App\Entity\Assignment;
use App\Entity\Equipment;
use App\Entity\Store;
use App\Repository\EquipmentRepository;
use App\Repository\StoreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('equipment', EntityType::class, [
                'class' => Equipment::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un matériel',
                'query_builder' => function (EquipmentRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->andWhere('e.deletedAt IS NULL')
                        ->orderBy('e.name', 'ASC');
                },
                'choice_attr' => function (Equipment $eq) {
                    return [
                        'data-stock' => $eq->getStockQuantity(),
                        'data-reference' => $eq->getReference(),
                    ];
                },
            ])
            ->add('quantity', ChoiceType::class, [
                'choices' => [],
                'placeholder' => 'Choisir une quantité',
                'label' => 'Quantité',
                'attr' => [
                    'data-qty' => 'quantity'
                ],
                'required' => true,
            ])
            ->add('store', EntityType::class, [
                'class' => Store::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un magasin',
                'query_builder' => function (StoreRepository $sr) {
                    return $sr->createQueryBuilder('s')
                        ->andWhere('s.deletedAt IS NULL')
                        ->orderBy('s.name', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assignment::class,
        ]);
    }
}
