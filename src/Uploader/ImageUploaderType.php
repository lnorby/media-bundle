<?php

namespace Lnorby\MediaBundle\Uploader;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ImageUploaderType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['limit'] = $options['limit'];
        $view->vars['min_height'] = $options['min_height'];
        $view->vars['min_width'] = $options['min_width'];
        $view->vars['sortable'] = $options['sortable'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_add' => true,
                'allow_delete' => true,
                'entry_type' => UploadedImageType::class,
                'min_height' => 250,
                'min_width' => 250,
                'sortable' => false,
            ]
        );

        $resolver->setRequired(['limit']);
        $resolver->setAllowedTypes('limit', ['int']);
        $resolver->setAllowedTypes('min_height', ['int']);
        $resolver->setAllowedTypes('min_width', ['int']);
        $resolver->setAllowedTypes('sortable', ['bool']);
    }

    public function getParent()
    {
        return CollectionType::class;
    }
}
