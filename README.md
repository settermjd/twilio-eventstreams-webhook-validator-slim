# Slim v4 Twilio Event Streams Webhook Validator Middleware

![testing workflow](https://github.com/settermjd/twilio-eventstreams-webhook-validator-slim/actions/workflows/php.yml/badge.svg)

This is a small piece of middleware for Slim v4 that validates a Twilio Event Streams webhook.
If the webhook is valid, the next request is called. Otherwise, a 400 Bad Request is returned, along with a problem details response, formatted based on the request's accept header.

## Prerequisites

To use this middleware, you're going to need a Twilio account (either free or paid). 
If you are new to Twilio, [create a free account][twilio_referral_url].

## Getting Started

To use this in your Slim v4 application, first add the project as a dependency, by running the following command:

```bash 
composer require settermjd/twilio-eventstreams-webhook-validator-slim
```

Then, follow [the Slim Middleware documentation][slim_middleware_docs_url] to add the middleware to the respective route.
Below, you can find examples of adding it as [Route Middleware][slim_docs_route_middleware_url]:

```php
<?php

declare(strict_types=1);

require_once('vendor/autoload.php');

use EventStreams\EventStreamsWebhookValidatorMiddleware;
use Twilio\Security\RequestValidator;

$app = new \Slim\App();

$eventStreamsWebhookValidatorMiddleware = new EventStreamsWebhookValidatorMiddleware(
    "<<THE APP'S PUBLIC URL>>",
    "<<THE WEBHOOK PATH>>",
     new RequestValidator("<<YOUR TWILIO AUTH TOKEN>>")
);

$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write(' Hello ');
    return $response;
})->add($eventStreamsWebhookValidatorMiddleware);

$app->run();
```

Then, replace `<<THE APP'S PUBLIC URL>>"` with the public URI of the application, `<<THE WEBHOOK PATH>>` with he path of the route that handles the webhook request, and `<<YOUR TWILIO AUTH TOKEN>>` with your Twilio [Auth Token][twilio_auth_token_url].
 
**Note:** The first two parameters to `EventStreamsWebhookValidatorMiddleware` ensure that the app's correct public URI is used as part of the webhook validation process.

[twilio_auth_token_url]: https://help.twilio.com/articles/223136027-Auth-Tokens-and-How-to-Change-Them 
[twilio_referral_url]: https://www.twilio.com/referral/QlBtVJ
[slim_middleware_docs_url]: https://www.slimframework.com/docs/v4/concepts/middleware.html#registering-middleware
[slim_docs_route_middleware_url]: https://www.slimframework.com/docs/v4/concepts/middleware.html#route-middleware