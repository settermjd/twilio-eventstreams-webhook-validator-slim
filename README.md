# Slim v4 Twilio Event Streams Webhook Validator Middleware

![testing workflow](https://github.com/settermjd/twilio-eventstreams-webhook-validator-slim/actions/workflows/php.yml/badge.svg)

This is a small piece of middleware for [the Slim Framework (v4)][slim_url] that validates a [Twilio Event Streams Webhook][twilio_event_streams_webhook_url].

## How Does It Work?

After adding the middleware to one or more of your application's routes, when a route is dispatched, the webhook is validated.
If the webhook _is_ valid, the next request is called. 
Otherwise, an [HTTP 400 Bad Request][http_400_bad_request_url] is returned, along with a [problem details][problem_details_rfc_url] response, formatted as JSON or XML based on the request's [Accept header][accept_header_url].

## Why Use It?

You could code up the functionality yourself; and [check out the tutorial on the Twilio blog][twilio_blog_url] if you'd like to.
But, this middleware avoids the need to do so.
Instead, just initialise it and add it to the relevant routes, then focus on the remaining functionality of your application.

## Prerequisites

To use this middleware, you're going to need a Twilio account (either free or paid). 
If you are new to Twilio, [create a free account][twilio_referral_url].

## ⚡️ Quick Start

To use the middleware in your Slim (v4) application, first add the project as a dependency by running the following command:

```bash 
composer require settermjd/twilio-eventstreams-webhook-validator-slim
```

Then, in the appropriate part of your application, initialise a new `EventStreams\EventStreamsWebhookValidatorMiddleware` object, which requires three parameters:

- The application's public URI. During development you could use [ngrok][ngrok_url] to expose the application to the public internet and generate the public URI.
- The webhook URI's path
- A `\Twilio\Security\RequestValidator` object, initialised with your [Twilio Auth Token][twilio_auth_token_url]

**Note:** The first two parameters to `EventStreamsWebhookValidatorMiddleware` ensure that the app's correct public URI is used as part of the webhook validation process.

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

Then, replace `<<THE APP'S PUBLIC URL>>"` with the public URI of the application, `<<THE WEBHOOK PATH>>` with he path of the route that handles the webhook request, and `<<YOUR TWILIO AUTH TOKEN>>` with your Twilio Auth Token.

[accept_header_url]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept
[problem_details_rfc_url]: https://datatracker.ietf.org/doc/html/rfc7807
[http_400_bad_request_url]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
[ngrok_url]: https://ngrok.com/
[twilio_auth_token_url]: https://help.twilio.com/articles/223136027-Auth-Tokens-and-How-to-Change-Them 
[twilio_referral_url]: https://www.twilio.com/referral/QlBtVJ
[twilio_event_streams_webhook_url]: https://www.twilio.com/docs/events/webhook-quickstart 
[slim_url]: https://www.slimframework.com/
[slim_middleware_docs_url]: https://www.slimframework.com/docs/v4/concepts/middleware.html#registering-middleware
[slim_docs_route_middleware_url]: https://www.slimframework.com/docs/v4/concepts/middleware.html#route-middleware
[twilio_blog_url]: https://www.twilio.com/en-us/blog/validate-twilio-event-streams-webhooks-php