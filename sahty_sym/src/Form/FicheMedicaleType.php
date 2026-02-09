<?php

namespace App\Form; 
use App\Entity\FicheMedicale;
use App\Entity\Patient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class FicheMedicaleType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Vérifier si l'utilisateur est un médecin
        $isMedecin = $this->security->isGranted('ROLE_MEDECIN');

        $builder
   
            ->add('antecedents', TextareaType::class, [
                'label' => '📋 Antécédents médicaux',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Décrivez vos antécédents médicaux...'
                ]
            ])
            ->add('allergies', TextareaType::class, [
                'label' => '⚠️ Allergies',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Listez vos allergies...'
                ]
            ])
            ->add('traitement_en_cours', TextareaType::class, [
                'label' => '💊 Traitements en cours',
                'required' => false,
                'property_path' => 'traitementEnCours',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Traitements actuels...'
                ]
            ])
            ->add('taille', NumberType::class, [
                'label' => '📏 Taille (en mètres)',
                'required' => false,
                'attr' => [
                    'step' => '0.01',
                    'placeholder' => 'Ex: 1.75'
                ]
            ])
            ->add('poids', NumberType::class, [
                'label' => '⚖️ Poids (en kg)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 70'
                ]
            ])
            // Champs réservés au médecin
            ->add('diagnostic', TextareaType::class, [
                'label' => '🩺 Diagnostic',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'readonly' => !$isMedecin, // ✅ Readonly si pas médecin
                    'placeholder' => $isMedecin ? 'Saisissez le diagnostic...' : 'Réservé au médecin'
                ]
            ])
            ->add('traitement_prescrit', TextareaType::class, [
                'label' => '💉 Traitement prescrit',
                'required' => false,
                'property_path' => 'traitementPrescrit',
                'attr' => [
                    'rows' => 4,
                    'readonly' => !$isMedecin, // ✅ Readonly si pas médecin
                    'placeholder' => $isMedecin ? 'Prescrivez le traitement...' : 'Réservé au médecin'
                ]
            ])
            ->add('observations', TextareaType::class, [
                'label' => '📝 Observations',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'readonly' => !$isMedecin, // ✅ Readonly si pas médecin
                    'placeholder' => $isMedecin ? 'Ajoutez vos observations...' : 'Réservé au médecin'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => '📊 Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Modifié' => 'modifié',
                    'Inactif' => 'inactif',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => !$isMedecin // ✅ Disabled si pas médecin
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FicheMedicale::class,
        ]);
    }

}
