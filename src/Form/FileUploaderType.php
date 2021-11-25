<?php

namespace Lnorby\MediaBundle\Form;

use Lnorby\MediaBundle\Form\DataTransformer\MediaTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FileUploaderType extends AbstractType
{
    /**
     * @var MediaTransformer
     */
    private $transformer;

    public function __construct(MediaTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'attr' => [
                    'class' => 'js-file-uploader-media-id',
                ],
            ]
        );
    }

    public function getParent()
    {
        return HiddenType::class;
    }
}