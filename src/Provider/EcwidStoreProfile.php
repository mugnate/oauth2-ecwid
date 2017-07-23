<?php

namespace Mugnate\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class EcwidStoreProfile implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

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
        return $this->getValueByKey($this->response, 'generalInfo.storeId');
    }


    /**
     * Returns store owner email
     *
     * @return null
     */
    public function getEmail()
    {
        return $this->getValueByKey($this->response, 'account.accountEmail');
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
