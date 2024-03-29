<?php

namespace Lnorby\MediaBundle\Form;

use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Form\DataTransformer\MediaTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FileUploaderType extends AbstractType
{
    public function __construct(private readonly MediaTransformer $transformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /**
         * @var Media|null $media
         */
        $media = $form->getData();

        $view->vars['deletable'] = $options['deletable'];
        $view->vars['file_name'] = $media instanceof Media ? $media->name() : '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'deletable' => true,
            ]
        );

        $resolver->setAllowedTypes('deletable', 'bool');
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
