<?php

namespace App\Form;

use App\Entity\Equipment;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('reference')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
                'label' => 'Catégorie',
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'Neuf' => 'neuf',
                    'Utilisé' => 'utilisé',
                    'Endommagé' => 'endommagé',
                ],
                'placeholder' => 'Choisir un état',
            ])
            ->add('stockQuantity')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
