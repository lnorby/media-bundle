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

final class ImageUploaderType extends AbstractType
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

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /**
         * @var Media|null $media
         */
        $media = $form->getData();

        $view->vars['file_picker_label'] = $options['file_picker_label'];
        $view->vars['image_path'] = $media instanceof Media ? $media->getPath() : '';
        $view->vars['min_height'] = $options['min_height'];
        $view->vars['min_width'] = $options['min_width'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'file_picker_label' => 'Fénykép kiválasztása',
                'min_height' => 250,
                'min_width' => 250,
            ]
        );

        $resolver->setAllowedTypes('min_height', ['int']);
        $resolver->setAllowedTypes('min_width', ['int']);
    }

    public function getParent()
    {
        return TextType::class;
    }
}
