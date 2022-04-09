<?php

namespace Lnorby\MediaBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Lnorby\MediaBundle\Entity\Media;
use Lnorby\MediaBundle\Exception\CouldNotFindMedia;

final class MediaRepository
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws CouldNotFindMedia
     */
    public function getByPath($path): Media
    {
        $dql = '
            SELECT m
            FROM Lnorby\MediaBundle\Entity\Media m
            WHERE m.path = :path
        ';

        try {
            return $this->entityManager
                ->createQuery($dql)
                ->setParameter('path', $path)
                ->getSingleResult();
        } catch (\Exception $e) {
            throw new CouldNotFindMedia('', 0, $e);
        }
    }
}
