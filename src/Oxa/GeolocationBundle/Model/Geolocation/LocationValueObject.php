<?php

namespace Oxa\GeolocationBundle\Model\Geolocation;

use Oxa\ManagerArchitectureBundle\Model\DataType\AbstractValueObject;

class LocationValueObject extends AbstractValueObject
{
    public $name;
    public $lat;
    public $lng;
    public $locality;
    public $ignoreLocality;

    public function __construct($name = null, $lat = null, $lng = null, $locality = null, $ignoreLocality = false)
    {
        if (null === $name && null  === $lat && null === $lng) {
            throw new Exception("All params can not be NULL", 1);
        }

        $this->name     = $name;
        $this->lat      = $lat;
        $this->lng      = $lng;
        $this->locality = $locality;
        $this->ignoreLocality = $ignoreLocality;
    }
}
