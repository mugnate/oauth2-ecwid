<?php

namespace Mugnate\OAuth2\Client\Provider\Tests;

use Faker\Factory as FakerFactory;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Mugnate\OAuth2\Client\Provider\Ecwid as EcwidProvider;
use Mugnate\OAuth2\Client\Provider\EcwidStoreProfile;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class EcwidTest extends TestCase
{
    /**
     * @var EcwidProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new EcwidProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testRequiredArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->provider = new EcwidProvider([
            'clientId' => null,
            'clientSecret' => null,
            'redirectUri' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->provider = new EcwidProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => null,
            'redirectUri' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->provider = new EcwidProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_client_secret',
            'redirectUri' => null,
        ]);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $url_without_parameter = explode('?', $url)[0];
        $uri = parse_url($url);

        parse_str($uri['query'], $query);

        $this->assertSame('https://my.ecwid.com/api/oauth/authorize', $url_without_parameter);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertContains('read_store_profile', $query['scope']);
    }

    public function testBaseAuthorizationUrl()
    {
        $url = $this->provider-> getBaseAuthorizationUrl();
        $this->assertEquals('https://my.ecwid.com/api/oauth/authorize', $url);
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $this->assertEquals('https://my.ecwid.com/api/oauth/token', $url);
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = new AccessToken([
            'access_token' => 'mock_access_token',
            'resource_owner_id' => 'mock_store_id',
        ]);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $this->assertEquals('https://app.ecwid.com/api/v3/mock_store_id/profile?token=mock_access_token', $url);
    }

    public function testDefaultScopes()
    {
        $getDefaultScopes = function () {
            return $this->getDefaultScopes();
        };

        $defaultScopes = $getDefaultScopes->call($this->provider);
        $this->assertTrue(in_array('read_store_profile', $defaultScopes));
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "token_type":"bearer", "store_id":1000}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals(1000, $token->getResourceOwnerId());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
    }

    public function testUserData()
    {
        $faker = FakerFactory::create();

        $email = $faker->email;
        $storeID = $faker->randomDigitNotNull;
        $name = $faker->name;

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "store_id":' .$storeID. '}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{
            "generalInfo": {"storeId": ' .$storeID. '},
            "account": {"accountName": "' .$name. '", "accountEmail": "'.$email.'"}
        }');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $store = $this->provider->getResourceOwner($token);

        $this->assertEquals($storeID, $store->getId());
        $this->assertEquals($storeID, $store->toArray()['generalInfo']['storeId']);

        $this->assertEquals($email, $store->getEmail());
        $this->assertEquals($email, $store->toArray()['account']['accountEmail']);

        $this->assertEquals($name, $store->toArray()['account']['accountName']);
    }

    public function testMissingUserData()
    {
        $faker = FakerFactory::create();

        $storeID = $faker->randomDigitNotNull;

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "store_id":' .$storeID. '}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $store = $this->provider->getResourceOwner($token);

        $this->assertEquals(null, $store->getId());
        $this->assertEquals(null, $store->getEmail());
    }

    public function testCreateResourceOwner()
    {
        $getCreateResourceOwner = function () use (&$response, &$token) {
            return $this->createResourceOwner($response, $token);
        };

        // Stub
        $token = new AccessToken([
            'access_token' => 'mock_access_token',
            'resource_owner_id' => 'mock_store_id',
        ]);
        $response = [
            'id'           => random_int(1, 1000),
        ];

        // Execute
        $resourceOwner = $getCreateResourceOwner->call($this->provider);

        // Verify
        self::assertInstanceOf(EcwidStoreProfile::class, $resourceOwner);
        self::assertSame($response, $resourceOwner->toArray());
    }

    /**
     * @expectedException IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $this->expectException(IdentityProviderException::class);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"error": "invalid_request"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @expectedException IdentityProviderException
     **/
    public function testExceptionThrownWhenRetrunNot200Code()
    {
        $this->expectException(IdentityProviderException::class);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"error": "invalid_request"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(500);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
