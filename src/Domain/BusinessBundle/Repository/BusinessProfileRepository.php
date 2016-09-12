<?php

namespace Domain\BusinessBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Domain\BusinessBundle\Entity\BusinessProfile;
use FOS\UserBundle\Model\UserInterface;
use Domain\BusinessBundle\Model\StatusInterface;
use Doctrine\ORM\QueryBuilder;
use Domain\SearchBundle\Model\DataType\SearchDTO;
use Oxa\GeolocationBundle\Model\Geolocation\LocationValueObject;
use Oxa\GeolocationBundle\Utils\GeolocationUtils;
use Oxa\WistiaBundle\Entity\WistiaMedia;
use Symfony\Component\Config\Definition\Builder\ExprBuilder;
use Doctrine\Common\Collections\Criteria;

/**
 * BusinessProfileRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessProfileRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param int $id
     * @param string $locale
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findWithLocale(int $id, string $locale)
    {
        $qb = $this->createQueryBuilder('bp');

        $qb->select('bp')
            ->where('bp.id = :id')
            ->leftJoin('bp.categories', 'categories')
            ->setParameter('id', $id);

        $query = $qb->getQuery();

        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        // Force the locale
        $query->setHint(
            \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $locale
        );

        return $query->getSingleResult();
    }

    /**
     * @param UserInterface $user
     * @return array
     */
    public function findUserBusinessProfiles(UserInterface $user)
    {
        $businessProfiles = $this->findBy([
            'user' => $user,
            'actualBusinessProfile' => null,
        ]);

        return $businessProfiles;
    }

    /**
     * @param UserInterface $user
     * @return array
     */
    public function findBusinessProfilesReviewedByUser(UserInterface $user)
    {
        $queryBuilder = $this->createQueryBuilder('bp')
            ->select('DISTINCT(bp.name) name, bp.slug')
            ->join('bp.businessReviews', 'br')
            ->where('br.user = :user')
            ->andWhere('bp.isActive = TRUE')
            ->andWhere('bp.locked = FALSE')
            ->setParameter('user', $user)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Main search functionality
     *
     * @param SearchDTO $searchParams
     * @return array
     */
    public function search(SearchDTO $searchParams)
    {
        $searchQuery        = $this->splitPhraseToPlain($searchParams->query);
        $searchLocation     = $this->splitPhraseToPlain($searchParams->locationValue->name);

        $limit  = $searchParams->limit;
        $offset = ($searchParams->page - 1) * $limit;

        $queryBuilder = $this->getQueryBuilder();

        $this->addSearchbByCategoryAndNameWithingAreaQueryBuilder($queryBuilder, $searchQuery);
        $this->addSearchByLocationQueryBuilder($queryBuilder, $searchLocation, $searchParams->locationValue->zip);
        $this->addDistanceBetweenPointsQueryBuilder($queryBuilder, $searchParams->locationValue);

        $this->addLimitOffsetQueryBuilder($queryBuilder, $limit, $offset);

        $this->addOrderByRankQueryBuilder($queryBuilder, Criteria::DESC);
        $this->addOrderByCategoryRankQueryBuilder($queryBuilder, Criteria::DESC);

        $this->addOrderByDistanceQueryBuilder($queryBuilder, Criteria::ASC);
        $this->addOrderBySubscriptionPlanQueryBuilder($queryBuilder, Criteria::DESC);

        if ($category = $searchParams->getCategory()) {
            $categoryFilter = $this->splitPhraseToPlain($category);
            $this->addCategoryFilterToQueryBuilder($queryBuilder, $categoryFilter);
        }

        $results = $queryBuilder->getQuery()->getResult();

        return $results;
    }

    /**
     * Counting search results
     *
     * @param SearchDTO $searchParams
     * @return int
     */
    public function countSearchResults(SearchDTO $searchParams)
    {
        $searchQuery        = $this->splitPhraseToPlain($searchParams->query);
        $searchLocation     = $this->splitPhraseToPlain($searchParams->locationValue->name);

        $queryBuilder = $this->getQueryBuilder();

        $this->addCountToSearchbByCategoryAndNameWithingAreaQueryBuilder($queryBuilder, $searchQuery);
        $this->addSearchByLocationQueryBuilder($queryBuilder, $searchLocation);

        if ($category = $searchParams->getCategory()) {
            $categoryFilter = $this->splitPhraseToPlain($category);
            $this->addCategoryFilterToQueryBuilder($queryBuilder, $categoryFilter);
        }

        $results = $queryBuilder->getQuery()->getResult();

        return count($results);
    }

    public function searchNeighborhood(SearchDTO $searchParams)
    {
        return $this->search($searchParams);
    }

    public function searchAutosuggestWithBuilder(SearchDTO $searchParams, $limit = 5, $offset = 0)
    {
        $searchQuery        = $this->splitPhraseToPlain($searchParams->query);
        $searchLocation     = $this->splitPhraseToPlain($searchParams->locationValue->name);

        $queryBuilder = $this->getQueryBuilder()
            ->addSelect('bp.name');

        $this->addSearchbByCategoryAndNameWithingAreaQueryBuilder($queryBuilder, $searchQuery, $searchLocation);
        $this->addHeadlineToNameQueryBuilder($queryBuilder);
        $this->addLimitOffsetQueryBuilder($queryBuilder, $limit, $offset);
        $this->addOrderByRankQueryBuilder($queryBuilder, Criteria::DESC);

        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function searchWithQueryBuilder(
        $searchQuery,
        $location,
        $categoryFilter = null,
        $neighborhoodFilter = null,
        $limit = 20,
        $offset = 0
    ) {
        $searchQuery    = $this->splitPhraseToPlain($searchQuery);
        $searchLocation = $this->splitPhraseToPlain($location);

        $queryBuilder = $this->getQueryBuilder();

        $this->addSearchbByCategoryAndNameWithingAreaQueryBuilder($queryBuilder, $searchQuery, $searchLocation);

        $this->addAreaRankQueryBuilder($queryBuilder, $searchLocation);

        $this->addCityRankQueryBuilder($queryBuilder);

        $this->addLimitOffsetQueryBuilder($queryBuilder, $limit, $offset);
        $this->addOrderByCategoryRankQueryBuilder($queryBuilder);
        $this->addOrderByRankQueryBuilder($queryBuilder);
        $this->addOrderByCityRankQueryBuilder($queryBuilder);
        $this->addOrderByAreaRankQueryBuilder($queryBuilder);

        if ($categoryFilter) {
            $categoryFilter = $this->splitPhraseToPlain($categoryFilter);
            $this->addCategoryFilterToQueryBuilder($queryBuilder, $categoryFilter);
        }

        $results = $queryBuilder->getQuery()->getResult();

        return $results;
    }

    protected function splitPhraseToPlain(string $phrase)
    {
        $words = explode(' ', $phrase);
        $words = array_filter($words);
        $wordParts = array_map(
            function ($item) {
                return $item . ":*";
            },
            $words
        );
        $plain = implode(' | ', $wordParts);

        return $plain;
    }

    protected function getEmptyQueryBuilder()
    {
        return $this->getEntityManager()->createQueryBuilder();
    }

    protected function getQueryBuilder()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder('bp');

        $queryBuilder->select('bp')
            ->from('DomainBusinessBundle:BusinessProfile', 'bp')
            ->andWhere('bp.isActive = TRUE')
            ->groupBy('bp.id')
        ;

        return $queryBuilder;
    }

    protected function addSearchbByCategoryAndNameWithingAreaQueryBuilder(
        QueryBuilder &$queryBuilder,
        $searchQuery
    ) {
        return $queryBuilder
            ->addSelect('TSRANK(bp.searchFts, :searchQuery) as rank')
            ->join('bp.categories', 'c')
            ->join('bp.areas', 'a')
            ->addSelect('MAX(TSRANK(c.searchFts, :searchQuery)) as rank_c')
            ->andWhere('(
                TSQUERY( c.searchFts, :searchQuery) = true
                OR
                TSQUERY( bp.searchFts, :searchQuery) = true
            )')
            ->setParameter('searchQuery', $searchQuery)
        ;
    }

    protected function addCountToSearchbByCategoryAndNameWithingAreaQueryBuilder(
        QueryBuilder &$queryBuilder,
        $searchQuery
    ) {
        return $queryBuilder
            ->select('count(bp.id) as rows')
            ->join('bp.categories', 'c')
            ->join('bp.areas', 'a')
            ->andWhere('(
                TSQUERY( c.searchFts, :searchQuery) = true
                OR
                TSQUERY( bp.searchFts, :searchQuery) = true
            )')
            ->setParameter('searchQuery', $searchQuery)
        ;
    }

    protected function addSearchByLocationQueryBuilder(QueryBuilder &$queryBuilder, $location, $zip = null)
    {
        $searchString = '(
                TSQUERY( a.searchFts, :searchLocation) = true
                OR
                TSQUERY( bp.searchCityFts, :searchLocation) = true
            )';

        if ($zip) {
            $searchString = '(
                bp.zipCode = :zip
            )';
            $queryBuilder->setParameter('zip', $zip);
        } else {
            $queryBuilder->setParameter('searchLocation', $location);
        }

        return $queryBuilder
            ->andWhere($searchString)
            ;
    }

    protected function addCityRankQueryBuilder(QueryBuilder &$queryBuilder)
    {
        return $queryBuilder
            ->addSelect('TSRANK(bp.searchCityFts, :searchLocation) as rank_city')
            ->orWhere('TSQUERY( bp.searchCityFts, :searchLocation) = true')
        ;
    }

    protected function addCategoryRankQueryBuilder(QueryBuilder &$queryBuilder)
    {
        return $queryBuilder
            ->join('bp.categories', 'c')
            ->addSelect('MAX(TSRANK(c.searchFts, :searchQuery)) as rank_c')
            ->orWhere('TSQUERY( c.searchFts, :searchQuery) = true')
            ->andWhere('TSQUERY( a.searchFts, :searchLocation) = true')
        ;
    }

    protected function addAreaRankQueryBuilder(QueryBuilder &$queryBuilder, $location)
    {
        return $queryBuilder
            ->join('bp.areas', 'a')
            ->addSelect('MAX(TSRANK(a.searchFts, :searchLocation)) as rank_a')
            ->orWhere('TSQUERY( a.searchFts, :searchLocation) = true')
            ->setParameter('searchLocation', $location)
        ;
    }

    protected function addHeadlineToNameQueryBuilder(QueryBuilder &$queryBuilder)
    {
        return $queryBuilder
            ->addSelect('TSHEADLINE(bp.name, :searchQuery ) as data')
        ;
    }

    protected function addLimitOffsetQueryBuilder(QueryBuilder &$queryBuilder, $limit, $offset)
    {
        return $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;
    }

    protected function addOrderByRankQueryBuilder(QueryBuilder &$queryBuilder, $order)
    {
        return $queryBuilder
            ->addOrderBy('rank', $order)
        ;
    }

    protected function addOrderByCategoryRankQueryBuilder(QueryBuilder &$queryBuilder, $order)
    {
        return $queryBuilder
            ->addOrderBy('rank_c', $order)
        ;
    }

    protected function addOrderByCityRankQueryBuilder(QueryBuilder &$queryBuilder, $order)
    {
        return $queryBuilder
            ->addOrderBy('rank_city', $order)
        ;
    }

    protected function addOrderByAreaRankQueryBuilder(QueryBuilder &$queryBuilder, $order)
    {
        return $queryBuilder
            ->addOrderBy('rank_a', $order)
        ;
    }

    protected function addOrderByDistanceQueryBuilder(QueryBuilder &$queryBuilder, $order)
    {
        return $queryBuilder
            ->addOrderBy('distance', $order)
        ;
    }

    protected function addCategoryFilterToQueryBuilder(QueryBuilder &$queryBuilder, $category)
    {
        return $queryBuilder
            ->andWhere('TSQUERY( c.searchFts, :categoryFilter) = true')
            ->setParameter('categoryFilter', $category)
        ;
    }

    protected function addOrderBySubscriptionPlanQueryBuilder(QueryBuilder &$queryBuilder)
    {
        return $queryBuilder
            ->addSelect('sp.rank as subscription')
            ->leftJoin('bp.subscriptions', 's')
            ->leftJoin('s.subscriptionPlan', 'sp')
            ->andWhere('s.status = :subscriptionStatus')
            ->setParameter('subscriptionStatus', StatusInterface::STATUS_ACTIVE)
            ->addGroupBy('sp.rank')
            ->addOrderBy('subscription', 'DESC')
        ;
    }

    /**
     * Get business profiles which do not have active subscription
     * @return BusinessProfile[]|null
     */
    public function getBusinessWithoutActiveSubscription()
    {
        $activeSubscriptionQb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('b')
            ->from('DomainBusinessBundle:BusinessProfile', 'b')
            ->leftJoin('b.subscriptions', 's')
            ->andWhere('s.status = ' . StatusInterface::STATUS_ACTIVE)
        ;

        $qb = $this->getEntityManager()->createQueryBuilder();

        $objects = $qb
            ->select('bp')
            ->from('DomainBusinessBundle:BusinessProfile', 'bp')
            ->andWhere($qb->expr()->notIn('bp', $activeSubscriptionQb->getDQL()))
            ->getQuery()
            ->getResult()
        ;

        return $objects;
    }

    /**
     * Get business profiles ids array
     *
     * @param int|null $limit
     * @return BusinessProfile[]|null
     */
    public function getIndexedBusinessProfileIds(int $limit = null)
    {
        $query = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('bp.id')
            ->from('DomainBusinessBundle:BusinessProfile', 'bp', 'bp.id')
        ;

        if ($limit) {
            $query->setMaxResults($limit);
        }
        
        $result = $query
            ->getQuery()
            ->getResult()
        ;

        return array_keys($result);
    }

    /**
     * Adding distance value between points
     *
     * @param QueryBuilder &$queryBuilder
     * @param LocationValueObject $location
     *
     * @return queryBuilder
     */
    protected function addDistanceBetweenPointsQueryBuilder(QueryBuilder &$queryBuilder, LocationValueObject $location)
    {
        return $queryBuilder
            ->addSelect('(:earthDiameter * sin (
                sqrt (
                    ( 1 - cos (
                        (bp.latitude - :userLatitude) * PI()/180
                        )
                    ) / 2
                    +
                    cos (:userLatitude * PI()/180)
                    *
                    cos (bp.latitude * PI()/180)
                    *
                    ( 1 - cos( ( bp.longitude - :userLongitude ) * PI()/180 ) ) / 2
                )
            )) AS distance')
            ->setParameter('userLatitude', $location->lat)
            ->setParameter('userLongitude', $location->lng)
            ->setParameter('earthDiameter', GeolocationUtils::getEarthDiameterKm())
        ;
    }

    public function getHomepageVideos($limit)
    {
        $qb = $this->getVideosQuery()->setMaxResults($limit);

        $results = new Paginator($qb, $fetchJoin = true);

        return $results;
    }

    public function getVideos()
    {
        $qb = $this->getVideosQuery();

        $results = new Paginator($qb, $fetchJoin = true);

        return $results;
    }

    public function getBusinessProfilesWithAllowedAdUnits() : array
    {
        $qb = $this->createQueryBuilder('bp');
        $qb->where('bp.isActive = True');

        return $qb->getQuery()->getResult();
    }

    private function getVideosQuery()
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('v')
            ->from(WistiaMedia::class, 'v')
            ->leftJoin(BusinessProfile::class, 'bp')
            ->where('bp.isActive = TRUE')
            ->andWhere('bp.actualBusinessProfile IS NULL')
            ->andWhere('bp.locked = FALSE')
            ->orderBy('v.createdAt', 'DESC')
        ;

        return $qb;
    }
}
