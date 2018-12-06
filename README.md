# Easy LTI 1.1 Provider

This is a PHP library to connect your PHP application to an LTI consumer platform with outcome and deeplinking support.

# Install library

    @TODO add this library to packagist.org so an easy composer install and autoload is possible.

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

## Deeplinking (Content-Item Message)

Insert content items into the tool consumer.

```php
$contentItems = array(
  array(
    '@type' => 'LtiLinkItem',
    'mediaType' => 'application/vnd.ims.lti.v1.ltilink',
    'title' => $task->title,
    'icon' => array(
      '@id' => $server_protocol . $_SERVER['SERVER_NAME'].'/curios2/code/img/curios_small.gif',
      'width' => 32,
      'height' => 32
    ),
    'custom' => array(
      'task' => $_GET['task']
    )
  )
);

$lti->returnContentItems($url, $contentItems);
```

This action will submit an auto generated form back to the tool consumer.
