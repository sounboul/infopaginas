<?php
/**
 * Created by PhpStorm.
 * User: Xedin
 * Date: 12.06.16
 * Time: 15:34
 */

namespace Oxa\WistiaBundle\Manager;

use Doctrine\ORM\EntityManager;
use Oxa\WistiaBundle\Entity\WistiaMedia;
use Oxa\WistiaBundle\Repository\WistiaMediaRepository;

class WistiaMediaManager
{
    private $em;

    private $repository;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;

        $this->repository = $entityManager->getRepository(WistiaMediaRepository::SLUG);
    }

    public function find(int $id)
    {
        return $this->getRepository()->find($id);
    }

    public function save(array $wistiaMediaData) : WistiaMedia
    {
        $wistiaMedia = new WistiaMedia($wistiaMediaData);

        $this->em->persist($wistiaMedia);
        $this->em->flush();

        return $wistiaMedia;
    }

    private function getRepository()
    {
        return $this->repository;
    }
}