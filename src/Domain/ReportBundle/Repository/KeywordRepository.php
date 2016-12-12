<?php

namespace Domain\ReportBundle\Repository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Domain\BusinessBundle\Entity\BusinessProfile;
use Domain\ReportBundle\Entity\Keyword;
use Domain\ReportBundle\Model\DataType\ReportDatesRangeVO;
use Oxa\DfpBundle\Model\DataType\DateRangeVO;

/**
 * KeywordRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class KeywordRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param string $keyword
     * @return null|object
     */
    public function findKeywordByValue(string $keyword)
    {
        return $this->findOneBy(['value' => $keyword]);
    }

    /**
     * @param BusinessProfile $businessProfile
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int $limit
     * @return mixed
     */
    public function getTopKeywordsForBusinessProfile(
        BusinessProfile $businessProfile,
        \DateTime $start,
        \DateTime $end,
        int $limit = 5
    ) {
        $qb = $this->createQueryBuilder('k');

        $qb
            ->addSelect('count(search_logs.keyword) as cnt')
            ->innerJoin('k.searchLogs', 'search_logs', Join::WITH)
            ->where('search_logs.businessProfile = :businessProfile')
            ->andWhere('search_logs.createdAt >= :start')
            ->andWhere('search_logs.createdAt <= :end')
            ->groupBy('search_logs.keyword, k.value, k.id')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('businessProfile', $businessProfile)
            ->setParameter('start', $start->modify('00:00:00'))
            ->setParameter('end', $end->modify('23:59:59'))
        ;

        $stats = array_reduce($qb->getQuery()->getResult(), function ($result, $item) {
            $result[$item[0]->getValue()] = $item['cnt'];
            return $result;
        }, array());

        return $stats;
    }

    /**
     * @param array $values
     * @return ArrayCollection
     */
    public function getKeywordsCollectionFromValuesArray(array $values)
    {
        $keywords = new ArrayCollection();

        $existingKeywords = $this->createQueryBuilder('kw')
            ->where('kw.value IN (:values)')
            ->setParameter('values', $values)
            ->getQuery()
            ->getResult();

        $newKeywords = $this->getNewKeywordValues($existingKeywords, $values);

        foreach ($newKeywords as $value) {
            if ($value <= Keyword::KEYWORD_MAX_LENGTH) {
                $keyword = new Keyword();
                $keyword->setValue($value);

                $this->getEntityManager()->persist($keyword);

                $keywords->add($keyword);
            }
        }

        foreach ($existingKeywords as $existingKeyword) {
            $keywords->add($existingKeyword);
        }

        $this->getEntityManager()->flush();

        return $keywords;
    }

    /**
     * @param $existingKeywords
     * @param $values
     * @return array
     */
    protected function getNewKeywordValues($existingKeywords, $values)
    {
        $foundValues = [];

        /** @var Keyword $item */
        foreach ($existingKeywords as $item) {
            $foundValues[] = $item->getValue();
        }

        return array_diff($values, $foundValues);
    }
}
