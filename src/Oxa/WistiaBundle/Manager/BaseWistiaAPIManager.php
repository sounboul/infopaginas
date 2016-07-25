<?php
/**
 * Created by PhpStorm.
 * User: Xedin
 * Date: 11.06.16
 * Time: 23:28
 */

namespace Oxa\WistiaBundle\Manager;

use Oxa\WistiaBundle\Service\Model\WistiaApiClientInterface;

abstract class BaseWistiaAPIManager
{
    const ENDPOINT_NOT_DEFINED_ERROR_MESSAGE = 'API endpoint isn\'t defined.';

    protected $endpointModule = '';

    private $wistiaApiClient;

    private $apiPassword;

    public function __construct(WistiaApiClientInterface $apiClient, string $apiPassword)
    {
        $this->wistiaApiClient = $apiClient;
        $this->apiPassword = $apiPassword;
    }

    public function list()
    {
        return $this->doAPICall(WistiaApiClientInterface::HTTP_METHOD_GET, $this->getEndpointModule());
    }

    public function show(string $hash)
    {
        $endpoint = $this->getEndpointModule() . '/' . $hash;
        return $this->doAPICall(WistiaApiClientInterface::HTTP_METHOD_GET, $endpoint);
    }

    public function update(string $hash, array $data)
    {
        $endpoint = $this->getEndpointModule() . '/' . $hash;
        return $this->doAPICall(WistiaApiClientInterface::HTTP_METHOD_PUT, $endpoint, $data);
    }

    public function remove(string $hash)
    {
        $endpoint = $this->getEndpointModule() . '/' . $hash;
        return $this->doAPICall(WistiaApiClientInterface::HTTP_METHOD_DELETE, $endpoint);
    }

    public function copy(string $hash)
    {
        $endpoint = $this->getEndpointModule() . '/' . $hash . '/copy';
        return $this->doAPICall(WistiaApiClientInterface::HTTP_METHOD_POST, $endpoint);
    }

    protected function doAPICall(string $method, string $endpoint, array $data = [])
    {
        $requestData = $this->buildAPIRequestDataArray($data);
        return $this->wistiaApiClient->call($method, $endpoint, $requestData);
    }

    protected function buildAPIRequestDataArray(array $data = []) : array
    {
        $requestData = ['form_params' => $data];
        $requestData['form_params']['api_password'] = $this->getApiPassword();

        return $requestData;
    }

    protected function getEndpointModule() : string
    {
        if (empty($this->endpointModule)) {
            throw new \Exception(self::ENDPOINT_NOT_DEFINED_ERROR_MESSAGE);
        }

        return $this->endpointModule;
    }

    private function getApiPassword() : string
    {
        return $this->apiPassword;
    }
}