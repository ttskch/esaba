<?php
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

$form = $app['form.factory']->createBuilder(FormType::class)
    ->add('name', TextType::class, [
        'attr' => [
            'autofocus' => true,
        ],
        'constraints' => [
            new Assert\NotBlank(),
        ],
    ])
    ->add('email', EmailType::class, [
        'constraints' => [
            new Assert\NotBlank(),
            new Assert\Email(),
        ],
    ])
    ->add('gender', ChoiceType::class, [
        'choices' => $genderChoices = [
            'male' => 'male',
            'female' => 'female',
            'other' => 'other',
        ],
        'choice_attr' => function ($value, $key, $index) {
            return [
                'class' => 'form-check-inline',
            ];
        },
        'data' => 'male',
        'expanded' => true,
        'multiple' => false,
        'constraints' => [
            new Assert\NotBlank(),
            new Assert\Choice(array_keys($genderChoices)),
        ],
    ])
    ->add('interesting_services', ChoiceType::class, [
        'required' => false,
        'choices' => [
            'Service A' => 'Service A',
            'Service B' => 'Service B',
            'Service C' => 'Service C',
        ],
        'expanded' => true,
        'multiple' => true,
    ])
    ->add('message', TextareaType::class, [
        'attr' => [
            'rows' => 5,
        ],
        'constraints' => [
            new Assert\NotBlank(),
        ],
    ])
    ->getForm()
;

return $form;
