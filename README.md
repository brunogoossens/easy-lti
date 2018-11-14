# Easy LTI 1.1 Provider

This is a PHP library to connect your PHP application to an LTI consumer platform with outcome support.

# Install library

```bash
$ composer require brunogoossens/easy-lti
```

# How to use

## Validate LTI request

```php
use BrunoGoossens\LTI\LTIProvider;

$lti = new LTIProvider('key', 'secret');
$lti->validateRequest(); // throws an exception if the LTI launch is invalid.
```
> The OAuth nonce parameter is not validated. To validate this you should add some kind of database.

After validating the LTI launch request, you can be sure the $_REQUEST values are secure to use.

## Read a score

```php
$lti->readScore($outcome_service_url, $result_sourcedid);
```

## Write a score

```php
$lti->postScore($outcome_service_url, $result_sourcedid, 0.7); // score is a value between 0 and 1.
```

## Convert roles to a nice array

```php
$roles = LTIProvider::convertRoles($_REQUEST['roles']);
```
