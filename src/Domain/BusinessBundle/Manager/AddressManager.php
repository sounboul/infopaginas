<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 5/14/16
 * Time: 12:02 PM
 */

namespace Domain\BusinessBundle\Manager;

use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Domain\BusinessBundle\Entity\BusinessProfile;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Ivory\GoogleMap\Exception\Exception;
use Oxa\Sonata\AdminBundle\Model\CopyableEntityInterface;
use Oxa\Sonata\AdminBundle\Model\DeleteableEntityInterface;
use Oxa\Sonata\AdminBundle\Model\Manager\DefaultManager;

/**
 * Class AdminManager
 * @package Domain\BusinessBundle\Manager
 */
class AddressManager extends DefaultManager
{
    /**
     * @return \Ivory\GoogleMap\Services\Geocoding\Geocoder|object
     */
    protected function getGeocoder()
    {
        return $this->getContainer()->get('ivory_google_map.geocoder');
    }

    /**
     * @param $fullAddress
     */
    public function getGoogleAddresses(string $fullAddress)
    {
        $response = $this->getGeocoder()->geocode($fullAddress);

        return $response->getResults();
    }

    /**
     * @param $googleAddress
     * @param BusinessProfile $businessProfile
     */
    public function setGoogleAddress($googleAddress, BusinessProfile $businessProfile)
    {
        $businessProfile->setLatitude($googleAddress->getGeometry()->getLocation()->getLatitude());
        $businessProfile->setLongitude($googleAddress->getGeometry()->getLocation()->getLongitude());

        if ($object = current($googleAddress->getAddressComponents('country'))) {
            $country = $this->getEntityManager()
                ->getRepository('DomainBusinessBundle:Address\Country')
                ->findOneBy(['shortName' => $object->getShortName()]);

            $businessProfile->setCountry($country);
        }

        if ($object = current($googleAddress->getAddressComponents('locality'))) {
            $businessProfile->setCity($object->getLongName());
        }

        if ($object = current($googleAddress->getAddressComponents('administrative_area_level_1'))) {
            $businessProfile->setState($object->getLongName());
        } else {
            $businessProfile->setState(null);
        }

        if ($object = current($googleAddress->getAddressComponents('administrative_area_level_2'))) {
            $businessProfile->setExtendedAddress($object->getLongName());
        } else {
            $businessProfile->setExtendedAddress(null);
        }

        if ($object = current($googleAddress->getAddressComponents('postal_code'))) {
            $businessProfile->setZipCode($object->getShortName());
        } else {
            $businessProfile->setZipCode(null);
        }

        if ($object = current($googleAddress->getAddressComponents('route'))) {
            $businessProfile->setStreetAddress($object->getLongName());
        } else {
            $businessProfile->setStreetAddress(null);
        }

        if ($object = current($googleAddress->getAddressComponents('street_number'))) {
            $businessProfile->setStreetNumber($object->getLongName());
        } else {
            $businessProfile->setStreetNumber(null);
        }
    }

    /**
     * Check if google provide valid address data for address string
     *
     * @param $address
     * @return array|bool
     */
    public function validateAddress(string $address)
    {
        $response = [
            'result' => null,
            'error' => ''
        ];

        try {
            $results = $this->getGoogleAddresses($address);
        } catch (Exception $e) {
            return $response['error'] = $e->getMessage();
        }

        if ($results) {
            // get first address result
            // usually google returns list of addresses
            // even searching by specific address or coordinates
            // but the first one the best one (more correct)
            $result = array_shift($results);

            // data bellow is required
            // street, city, country, zip_code
            if (
                !$result->getAddressComponents('route') ||
                !$result->getAddressComponents('locality') ||
                !$result->getAddressComponents('country') ||
                !$result->getAddressComponents('postal_code')
            ) {
                $response['error'] = 'Invalid address. Please, be more specific';
            } else {
                // check if we get address from allowed country list
                $countries = $this->getEntityManager()
                    ->getRepository('DomainBusinessBundle:Address\Country')
                    ->getCountriesShortNames();

                $country = current($result->getAddressComponents('country'));
                if (!array_key_exists($country->getShortName(), $countries)) {
                    $response['error'] = sprintf(
                        'Country "%s" is not allowed. Must be one of: %s',
                        $country->getLongName(),
                        implode(', ', $countries)
                    );
                }
            }
            // return first address result to use it next
            $response['result'] = $result;
        } else {
            $response['error'] = 'Invalid address';
        }

        return $response;
    }
}
