<?php
// src/Form/DemandeAnalyseType.php

namespace App\Form;

use App\Entity\DemandeAnalyse;
use App\Entity\Patient;
use App\Entity\Medecin;
use App\Entity\Laboratoire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeAnalyseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userRole = $options['user_role'];
        $userEntity = $options['user_entity'];
        $laboratoire = $options['laboratoire'] ?? null;

        $builder
            ->add('type_bilan', TextType::class, [
                'label' => 'Type de bilan',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Bilan sanguin complet, Bilan lipidique...'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes complémentaires',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Informations complémentaires, symptômes, etc.'
                ]
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Normale' => 'normale',
                    'Haute' => 'haute',
                    'Urgent' => 'urgent'
                ],
                'required' => true,
                'data' => 'normale',
            ]);

        // Si ce n'est pas un patient, on affiche le champ patient
        if ($userRole !== 'ROLE_PATIENT') {
            $builder->add('patient', EntityType::class, [
                'label' => 'Patient',
                'class' => Patient::class,
                'choice_label' => function(Patient $patient) {
                    return $patient->getNomComplet() . ' (' . $patient->getEmail() . ')';
                },
                'required' => true,
                'placeholder' => 'Sélectionnez un patient',
            ]);
        }

        // Champ médecin - TOUJOURS optionnel pour tous les rôles
        $builder->add('medecin', EntityType::class, [
            'label' => 'Médecin prescripteur (optionnel)',
            'class' => Medecin::class,
            'choice_label' => function(Medecin $medecin) {
                return 'Dr. ' . $medecin->getNomComplet() . ($medecin->getSpecialite() ? ' - ' . $medecin->getSpecialite() : '');
            },
            'required' => false,
            'placeholder' => 'Aucun médecin (facultatif)',
            'empty_data' => null,
            'help' => 'Laissez vide si vous n\'avez pas de médecin référent',
        ]);

        // Champ laboratoire
        $builder->add('laboratoire', EntityType::class, [
            'label' => 'Laboratoire',
            'class' => Laboratoire::class,
            'choice_label' => function(Laboratoire $laboratoire) {
                return $laboratoire->getNom() . ' - ' . $laboratoire->getVille();
            },
            'required' => true,
            'placeholder' => 'Sélectionnez un laboratoire',
            'data' => $laboratoire, // Pré-rempli si laboratoire spécifié
        ]);

        // Date programmée (optionnelle)
        $builder->add('programme_le', DateTimeType::class, [
            'label' => 'Date et heure programmée (optionnel)',
            'required' => false,
            'widget' => 'single_text',
            'html5' => true,
            'attr' => [
                'class' => 'datetime-picker'
            ]
        ]);

        // Le statut est determine automatiquement a partir du PDF resultat
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeAnalyse::class,
            'user_role' => null,
            'user_entity' => null,
            'laboratoire' => null,
        ]);
    }
}