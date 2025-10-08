<?php

namespace App\Form;

use App\Entity\Assignment;
use App\Entity\Equipment;
use App\Entity\Store;
use App\Repository\EquipmentRepository;
use App\Repository\StoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entityManager = $options['entity_manager'];

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

        $formModifier = function (FormEvent $event) use ($entityManager) {
            $form = $event->getForm();
            $data = $event->getData();

            $equipment = null;
            if ($data instanceof Assignment) {
                $equipment = $data->getEquipment();
            } elseif (is_array($data) && !empty($data['equipment'])) {
                $equipmentId = $data['equipment'];
                $equipment = $entityManager->getRepository(Equipment::class)->find($equipmentId);
            }

            $stock = $equipment ? $equipment->getStockQuantity() : 0;
            $choices = $stock > 0 ? range(1, $stock) : [];

            $form->add('quantity', ChoiceType::class, [
                'choices' => array_combine($choices, $choices),
                'placeholder' => $stock > 0 ? 'Choisir une quantité' : 'Stock indisponible',
                'label' => 'Quantité',
                'attr' => [
                    'data-qty' => 'quantity'
                ],
                'required' => true,
                'disabled' => $stock === 0,
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            $formModifier
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            $formModifier
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assignment::class,
        ]);

        $resolver->setRequired('entity_manager');
        $resolver->setAllowedTypes('entity_manager', EntityManagerInterface::class);
    }
}
