<?php
/**
 * Created by PhpStorm.
 * User: Alexander Polevoy <xedinaska@gmail.com>
 * Date: 18.08.16
 * Time: 13:07
 */

namespace Domain\ReportBundle\Google\Analytics;

use Domain\ReportBundle\Model\DataType\ReportDatesRangeVO;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class DataFetcher
 * @package Domain\ReportBundle\Google\Analytics
 */
class DataFetcher extends \Happyr\GoogleAnalyticsBundle\Service\DataFetcher
{
    const DEFAULT_DATE_RANGE_FROM = '-1 year';
    const DEFAULT_DATE_RANGE_TO = 'now';

    const GA_DATE_FORMAT = 'Y-m-d';

    protected $analytics;

    /**
     * DataFetcher constructor.
     *
     * @param CacheItemPoolInterface $cache
     * @param \Google_Client $client
     * @param int $viewId
     * @param int $cacheLifetime
     */
    public function __construct(CacheItemPoolInterface $cache, \Google_Client $client, $viewId, $cacheLifetime)
    {
        parent::__construct($cache, $client, $viewId, $cacheLifetime);

        $this->analytics = new \Google_Service_Analytics($this->client);
    }

    /**
     * @param string $uri
     * @param ReportDatesRangeVO $datesRange
     * @param string $regex
     * @return mixed
     * @throws \Exception
     */
    public function getViews($uri, ReportDatesRangeVO $datesRange, $regex = '$')
    {
        $start = $this->getFormattedStartDate($datesRange->getStartDate());
        $end   = $this->getFormattedEndDate($datesRange->getEndDate());

        $item = $this->loadDataFromCache($uri, $start, $end);

        if (!$item->isHit()) {
            //check if we got a token
            if (null === $this->client->getAccessToken()) {
                throw new \Exception('No google access token!');
            }

            $gaId    = 'ga:' . $this->viewId;
            $metrics = 'ga:pageviews';

            $filters = [
                'filters' => 'ga:pagePath=~' . $uri . $regex,
                'dimensions' => 'ga:date',
            ];

            $data     = $this->getAnalyticsDataResource()->get($gaId, $start, $end, $metrics, $filters);
            $prepared = $this->prepareResults($data);

            $this->saveDataToCache($item, $prepared);

            return $prepared;
        }

        return $item->get();
    }

    /**
     * @param $productId
     * @param ReportDatesRangeVO $datesRangeVO
     * @return mixed
     * @throws \Exception
     */
    public function getImpressions($productId, ReportDatesRangeVO $datesRangeVO)
    {
        $start = $this->getFormattedStartDate($datesRangeVO->getStartDate());
        $end   = $this->getFormattedEndDate($datesRangeVO->getEndDate());

        $item = $this->loadDataFromCache($productId, $start, $end);

        if (!$item->isHit()) {
            //check if we got a token
            if (null === $this->client->getAccessToken()) {
                throw new \Exception('No google access token!');
            }

            $gaId    = 'ga:' . $this->viewId;
            $metrics = 'ga:productListViews';

            $filters = [
                'filters' => 'ga:productSku=~' . $productId,
                'dimensions' => 'ga:date',
            ];

            $data     = $this->getAnalyticsDataResource()->get($gaId, $start, $end, $metrics, $filters);
            $prepared = $this->prepareResults($data);

            return $prepared;
        }

        return $item->get();
    }

    /**
     * @param \Google_Service_Analytics_GaData $data
     * @return mixed
     */
    protected function prepareResults(\Google_Service_Analytics_GaData $data)
    {
        return $data->getRows();
    }

    /**
     * @param \DateTime $startDate
     * @return string
     */
    protected function getFormattedStartDate(\DateTime $startDate)
    {
        if ($startDate === null) {
            $startDate = new \DateTime(self::DEFAULT_DATE_RANGE_FROM);
        }

        return $startDate->format(self::GA_DATE_FORMAT);
    }

    /**
     * @param \DateTime $endDate
     * @return string
     */
    protected function getFormattedEndDate(\DateTime $endDate)
    {
        if ($endDate === null) {
            $endDate = new \DateTime(self::DEFAULT_DATE_RANGE_TO);
        }

        return $endDate->format(self::GA_DATE_FORMAT);
    }

    /**
     * @param string $uri
     * @param string $startDate
     * @param string $endDate
     * @return CacheItemInterface
     */
    protected function loadDataFromCache(string $uri, string $startDate, string $endDate) : CacheItemInterface
    {
        $cacheKey = sha1($uri . $startDate . '-' . $endDate);
        return $this->cache->getItem($cacheKey);
    }

    /**
     * @param CacheItemInterface $item
     * @param $data
     */
    protected function saveDataToCache(CacheItemInterface $item, $data)
    {
        $item->set($data)->expiresAfter($this->cacheLifetime);
        $this->cache->save($item);
    }

    /**
     * @return \Google_Service_Analytics_DataGa_Resource
     */
    protected function getAnalyticsDataResource()
    {
        return $this->analytics->data_ga;
    }
}
