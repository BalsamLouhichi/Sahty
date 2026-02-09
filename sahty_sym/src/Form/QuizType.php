<?php

namespace App\Form;

use App\Entity\Quiz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom du Quiz',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', null, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('questions', TextareaType::class, [
                'label' => 'Questions (format JSON)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 12,
                    'placeholder' => 'Exemple valide :\n[{"question":"Vous sentez-vous stressé ?","options":[{"text":"Jamais","score":0},{"text":"Souvent","score":5}]}]',
                ],
            ])
        ;

        // Transformer JSON string ↔ array
        $builder->get('questions')
            ->addModelTransformer(new CallbackTransformer(
                // Transforme array → string pour l'affichage dans le textarea
                function ($arrayFromDb): string {
                    if (!is_array($arrayFromDb)) {
                        return '';
                    }
                    try {
                        return json_encode($arrayFromDb, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } catch (\Exception $e) {
                        return '';
                    }
                },
                // Transforme string soumise → array pour Doctrine
                function ($stringFromForm): array {
                    if (!is_string($stringFromForm) || trim($stringFromForm) === '') {
                        return [];
                    }
                    $decoded = json_decode($stringFromForm, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Si JSON invalide, on renvoie [] (ou tu peux throw une erreur si tu veux)
                        return [];
                    }
                    return is_array($decoded) ? $decoded : [];
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
        ]);
    }
}