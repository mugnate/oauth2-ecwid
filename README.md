# Ecwid Provider for OAuth 2.0 Client
[![Latest Version](https://img.shields.io/github/release/mugnate/oauth2-ecwid.svg?style=flat-square)](https://github.com/mugnate/oauth2-ecwid/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/mugnate/oauth2-ecwid/master.svg?style=flat-square)](https://travis-ci.org/mugnate/oauth2-ecwid)
[![Quality Score](https://img.shields.io/scrutinizer/g/mugnate/oauth2-ecwid.svg?style=flat-square)](https://scrutinizer-ci.com/g/mugnate/oauth2-ecwid)
[![Total Downloads](https://img.shields.io/packagist/dt/mugnate/oauth2-ecwid.svg?style=flat-square)](https://packagist.org/packages/mugnate/oauth2-ecwid)

This package provides LinkedIn OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require mugnate/oauth2-ecwid
```

## Usage

Usage is the same as The League's OAuth client, using `\League\OAuth2\Client\Provider\Ecwid` as the provider.

### Configuration

```php
$provider = new Mugnate\OAuth2\Client\Provider\Ecwid([
    'clientId'          => '{ecwid-client-id}',
    'clientSecret'      => '{ecwid-client-secret}',
    'redirectUri'       => 'https://yoursite.com/callback-url',
]);
```

### Link
```php
$authUrl = $provider->getAuthorizationUrl();
$_SESSION['oauth2-ecwid-state'] = $provider->getState();

echo '<a href="' .$authUrl. '"></a>';
```

### Callback
```php
if (! isset($_GET['code'])) {
    exit('Invalid code');
}
// Check given state against previously stored one to mitigate CSRF attack
elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2-ecwid-state'])) {

    unset($_SESSION['oauth2-ecwid-state']);
    exit('Invalid state');

} else {

    try {

        // Try to get an access token (using the authorization code grant)
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Your email %s!', $user->getEmail());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Ecwid authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['read_store_profile', 'read_catalog', 'read_orders'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/mugnate/oauth2-ecwid/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Nikolay Votintsev](https://github.com/votintsev)
- [All Contributors](https://github.com/mugnate/oauth2-ecwid/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/mugnate/oauth2-ecwid/blob/master/LICENSE) for more information.