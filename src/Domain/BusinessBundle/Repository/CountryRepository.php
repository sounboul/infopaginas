<?php

namespace Domain\BusinessBundle\Repository;

use Domain\BusinessBundle\Entity\Address\Country;

/**
 * CountryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CountryRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @return array
     */
    public function getCountriesShortNames()
    {
        $countries = $this->createQueryBuilder('c')
            ->select('c')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($countries as $country) {
            /** @var Country $country */
            $result[$country->getShortName()] = $country->getName();
        }
 
        return $result;
    }
}
