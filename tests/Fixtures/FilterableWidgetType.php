<?php

namespace Lle\Tests\Fixtures;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class FilterableWidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('level', IntegerType::class, ['required' => false])
            ->add('ecosystem', ChoiceType::class, [
                'required' => false,
                'choices' => ['composer' => 'composer', 'npm' => 'npm'],
            ]);
    }
}
