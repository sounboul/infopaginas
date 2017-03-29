<?php

namespace Oxa\GeolocationBundle\Model\Geolocation;

use Oxa\ManagerArchitectureBundle;
use Symfony\Component\HttpFoundation\Request;
use Oxa\ManagerArchitectureBundle\Model\Manager\Manager;

use Doctrine\ORM\EntityManager;
use Oxa\ConfigBundle\Service\Config;
use Oxa\ConfigBundle\Model\ConfigInterface;
use Oxa\GeolocationBundle\Utils\GeolocationUtils;
use Domain\BusinessBundle\Manager\LocalityManager;

class GeolocationManager extends Manager
{
    protected $confingService;

    public function __construct(EntityManager $em, Config $confingService, LocalityManager $localityManager)
    {
        parent::__construct($em);

        $this->confingService = $confingService;
        $this->localityManager = $localityManager;
    }

    public function buildLocationValue($geoData) {
        return new LocationValueObject($geoData);
    }

    public function buildLocationValueFromRequest(Request $request, $useUserGeo = true)
    {
        $geo    = $request->get('geo', null);

        $lat        = null;
        $lng        = null;
        $locality   = null;

        $userLat    = null;
        $userLng    = null;
        $userGeo    = null;

        $searchBoxTopLeftLat = $request->get('tllt', null);
        $searchBoxTopLeftLng = $request->get('tllg', null);
        $searchBoxBottomRightLat = $request->get('brlt', null);
        $searchBoxBottomRightLng = $request->get('brlg', null);

        if ($useUserGeo) {
            $userLat    = $request->get('lat', null);
            $userLng    = $request->get('lng', null);
            $userGeo    = $request->get('geoLoc', null);
        }

        $ignoreLocality = false;

        if ($geo) {
            // get locality by name and locale
            $locality = $this->localityManager->getLocalityByNameAndLocale($geo, $request->getLocale());
        } else {
            // empty search - show default
            $locality = $this->localityManager->getLocalityByNameAndLocale(
                $this->confingService->getValue(ConfigInterface::DEFAULT_SEARCH_CITY),
                $request->getLocale()
            );

            $request->query->set('geo', $locality->getName());

            $ignoreLocality = true;
        }

        if ($locality) {
            $lat = $locality->getLatitude();
            $lng = $locality->getLongitude();
        }

        if ($lat and $lng) {
            $geoData = [
                'geo'                       => $geo,
                'lat'                       => $lat,
                'lng'                       => $lng,
                'locality'                  => $locality,
                'ignoreLocality'            => $ignoreLocality,
                'userGeo'                   => $userGeo,
                'userLat'                   => $userLat,
                'userLng'                   => $userLng,
                'searchBoxTopLeftLat'       => $searchBoxTopLeftLat,
                'searchBoxTopLeftLng'       => $searchBoxTopLeftLng,
                'searchBoxBottomRightLat'   => $searchBoxBottomRightLat,
                'searchBoxBottomRightLng'   => $searchBoxBottomRightLng,
            ];

            $return = $this->buildLocationValue($geoData);
        } else {
            $return = null;
        }

        return $return;
    }
}
