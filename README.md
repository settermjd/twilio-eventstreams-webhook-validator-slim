# Slim v4 Twilio Event Streams Webhook Validator Middleware

This is a small piece of middleware for Slim v4 that validates a Twilio Event Streams webhook.
If the webhook is valid, the next request is called. Otherwise, a 400 Bad Request is returned, along with a problem details response, formatted based on the request's accept header.

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

$app = new \Slim\App();

$eventStreamsWebhookValidatorMiddleware = new EventStreamsWebhookValidatorMiddleware(
    'https://f4bc-87-121-75-154.ngrok-free.app',
    'webhook'
);

$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write(' Hello ');
    return $response;
})->add($eventStreamsWebhookValidatorMiddleware);

$app->run();
```

In the example above the EventStreamsWebhookValidatorMiddleware is initialised with two constructor parameters: the public URI of the application and the path of the route that handles the webhook request.
This is to ensure that the correct URI is used as part of the webhook validation process.

[slim_middleware_docs_url]: https://www.slimframework.com/docs/v3/concepts/middleware.html
[slim_docs_route_middleware_url]: https://www.slimframework.com/docs/v3/concepts/middleware.html#route-middleware