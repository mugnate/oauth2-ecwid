<?php

namespace Mugnate\OAuth2\Client\Provider;


use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Ecwid extends AbstractProvider
{
    /**
     * @var string Key used in a token response to identify the resource owner.
     */
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'store_id';

    public function getBaseAuthorizationUrl()
    {
        return 'https://my.ecwid.com/api/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://my.ecwid.com/api/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://app.ecwid.com/api/v3/' .$token->getResourceOwnerId(). '/profile?token=' . $token->getToken();
    }

    protected function getDefaultScopes()
    {
        return ['read_store_profile'];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error']) || $response->getStatusCode() != 200) {
            throw new IdentityProviderException($data['error'], $response->getStatusCode(), $response);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new EcwidStoreProfile($response);
    }
}