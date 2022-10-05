<?php

namespace Lnorby\MediaBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
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

    public function add(Media $media): void
    {
        $this->entityManager->persist($media);
        $this->entityManager->flush();
    }

    public function remove(Media $media): void
    {
        $this->entityManager->remove($media);
        $this->entityManager->flush();
    }

    /**
     * @throws CouldNotFindMedia
     */
    public function getById(int $id): Media
    {
        $dql = '
            SELECT m
            FROM Lnorby\MediaBundle\Entity\Media m
            WHERE m.id = :id
        ';

        try {
            return $this->entityManager
                ->createQuery($dql)
                ->setParameter('id', $id)
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw CouldNotFindMedia::withId($id);
        }
    }

    /**
     * @throws CouldNotFindMedia
     */
    public function getByPath(string $path): Media
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
        } catch (NoResultException $e) {
            throw CouldNotFindMedia::withPath($path);
        }
    }
}
