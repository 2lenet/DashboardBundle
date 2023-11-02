<?php

namespace Lle\DashboardBundle\Form;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('dataProvider', ChoiceType::class, [
            'choices' => $options['providers'],
            'choice_label' => function ($choice, $key, $value) {
                $repositoryParts = explode('\\', $value);
                $repositoryClass = end($repositoryParts);
                $entity = str_replace('Repository', '', $repositoryClass);

                return $entity;
            },
        ]);

        foreach ($options['confs'] as $key => $conf) {
            $repositoryKey = strtolower(str_replace('\\', '_', $key));

            $builder->add($repositoryKey . '_value', ChoiceType::class, [
                'choices' => $conf['valueSpec'],
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ]);
            $builder->add($repositoryKey . '_groupBy', ChoiceType::class, [
                'choices' => $conf['groupSpec'],
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
            ]);
            $builder->add($repositoryKey . '_nb', NumberType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'providers' => null,
            'confs' => null,
        ]);
    }
}