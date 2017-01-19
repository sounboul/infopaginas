<?php

namespace Domain\BusinessBundle\Repository;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Oxa\GeolocationBundle\Utils\GeolocationUtils;

/**
 * LocalityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LocalityRepository extends \Doctrine\ORM\EntityRepository
{
    public function getAvailableLocalitiesQb()
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.name');

        return $qb;
    }

    public function getAvailableLocalities()
    {
        $qb = $this->getAvailableLocalitiesQb()
            ->getQuery()
            ->getResult()
        ;

        return $qb;
    }

    public function getLocalityByNameAndLocale(string $localityName, string $locale)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from('DomainBusinessBundle:Locality', 'l')
            ->leftJoin('l.translations', 't')
            ->where('lower(l.name) =:name OR (lower(t.content) = :name AND t.locale = :locale)')
            ->setParameter('name', strtolower($localityName))
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();

        return $query;
    }

    public function getLocalityByName($localityName)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from('DomainBusinessBundle:Locality', 'l')
            ->leftJoin('l.translations', 't')
            ->where('lower(l.name) = :name OR (lower(t.content) = :name)')
            ->setParameter('name', strtolower($localityName))
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $query;
    }

    public function getLocalityBySlug($localitySlug, $customSlug = false)
    {
        $query = $this->getAvailableLocalitiesQb()
            ->where('l.slug = :localitySlug')
            ->setParameter('localitySlug', $localitySlug)
        ;

        if ($customSlug) {
            $query->orWhere('l.slug = :customSlug')
                ->setParameter('customSlug', $customSlug)
            ;
        }

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * @return IterableResult
     */
    public function getAvailableLocalitiesIterator()
    {
        $qb = $this->getAvailableLocalitiesQb();

        $query = $this->getEntityManager()->createQuery($qb->getDQL());

        $iterateResult = $query->iterate();

        return $iterateResult;
    }

    public function getLocalitiesByNameAndLocality($name, $locale)
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.translations', 'lt')
            ->setParameter('name', '%' . strtolower($name) . '%')
            ->setParameter('locale', $locale)
        ;

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('lower(l.name)', ':name'),
            $qb->expr()->andX(
                $qb->expr()->like('lower(lt.content)', ':name'),
                $qb->expr()->eq('lt.locale', ':locale')
            )
        ));

        return $qb->getQuery()->getResult();
    }
}
