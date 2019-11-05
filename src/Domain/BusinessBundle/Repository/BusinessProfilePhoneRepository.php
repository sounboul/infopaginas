<?php

namespace Domain\BusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * BusinessProfilePhoneRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessProfilePhoneRepository extends EntityRepository
{
    /**
     * @param $phone
     * @param array $excludedIds
     *
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getSamePhonesCount($phone, array $excludedIds)
    {
        $qb = $this->createQueryBuilder('bpp');

        $qb
            ->select('count(bpp.id)')
            ->where('bpp.id NOT IN (:ids)')
            ->andWhere('bpp.phone = :phone')
            ->setParameter('ids', $excludedIds)
            ->setParameter('phone', $phone)
        ;

        $result = $qb->getQuery()->getSingleResult();

        return array_shift($result);
    }
}
