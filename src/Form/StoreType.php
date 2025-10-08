<?php

namespace App\Form;

use App\Entity\Store;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du magasin',
                'attr' => ['class' => 'form-control mb-3'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control mb-3'],
            ])
            ->add('sr', ChoiceType::class, [
                'label' => 'SR',
                'required' => true,
                'placeholder' => 'Sélectionnez une SR',
                'choices' => [
                    'ALDI BEAUNE SARL' => 'ALDI BEAUNE SARL',
                    'ALDI BOUFFERE SARL' => 'ALDI BOUFFERE SARL',
                    'ALDI BRIE-COMTE-ROBERT SARL' => 'ALDI BRIE-COMTE-ROBERT SARL',
                    'ALDI ENNERY SARL' => 'ALDI ENNERY SARL',
                    'ALDI MARCHE BOIS-GRENIER SARL' => 'ALDI MARCHE BOIS-GRENIER SARL',
                    'ALDI MARCHE CAVAILLON SARL' => 'ALDI MARCHE CAVAILLON SARL',
                    'ALDI MARCHE CESTAS SARL' => 'ALDI MARCHE CESTAS SARL',
                    'ALDI MARCHE COLMAR SARL' => 'ALDI MARCHE COLMAR SARL',
                    'ALDI MARCHE CUINCY SARL' => 'ALDI MARCHE CUINCY SARL',
                    'ALDI MARCHE DAMMARTIN SARL' => 'ALDI MARCHE DAMMARTIN SARL',
                    'ALDI MARCHE HONFLEUR SARL' => 'ALDI MARCHE HONFLEUR SARL',
                    'ALDI MARCHE SARL À ABLIS' => 'ALDI MARCHE SARL À ABLIS',
                    'ALDI MARCHE SARL À OYTIER' => 'ALDI MARCHE SARL À OYTIER',
                    'ALDI MARCHE TOULOUSE SARL' => 'ALDI MARCHE TOULOUSE SARL',
                    'ALDI REIMS SARL' => 'ALDI REIMS SARL',
                    'ALDI SAUVIAN SARL' => 'ALDI SAUVIAN SARL',
                ],
                'attr' => ['class' => 'form-control mb-3'],
            ])
            ->add('codeFR', TextType::class, [
                'label' => 'Code FR',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => true,
                'placeholder' => 'Sélectionnez un statut',
                'choices' => [
                    'FERMÉ' => 'FERMÉ',
                    'ALDI EN ACTIVITÉ' => 'ALDI EN ACTIVITÉ',
                ],
                'attr' => ['class' => 'form-control mb-3'],
            ])
            ->add('typeDeProjet', ChoiceType::class, [
                'label' => 'Type de Projet',
                'required' => true,
                'placeholder' => 'Sélectionnez un type de projet',
                'choices' => [
                    'CRÉATION' => 'CRÉATION',
                    'RESCONTRUCTION' => 'RESCONTRUCTION',
                ],
                'attr' => ['class' => 'form-control mb-3'],
            ])
            ->add('dateOuverture', DateType::class, [
                'label' => 'Date d\'ouverture',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control mb-3'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
