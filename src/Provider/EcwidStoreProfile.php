<?php

namespace Mugnate\OAuth2\Client\Provider;


use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class EcwidStoreProfile implements ResourceOwnerInterface
{
    /**
     * Raw response
     *
     * @var
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Returns the identifier of the store
     *
     * @return mixed
     */
    public function getId()
    {
        return isset($this->response['generalInfo']['storeId']) ? $this->response['generalInfo']['storeId'] : null;
    }


    /**
     * Returns store owner email
     *
     * @return null
     */
    public function getEmail()
    {
        return isset($this->response['account']['accountEmail']) ? $this->response['account']['accountEmail'] : null;
    }


    /**
     * Return all of the owner details available as an array.
     * Array get is RAW
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}