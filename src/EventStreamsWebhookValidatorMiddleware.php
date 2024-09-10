<?php

declare(strict_types=1);

namespace EventStreams;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Twilio\Security\RequestValidator;

use function http_build_query;
use function rtrim;
use function ltrim;

/**
 * This is a small piece of middleware for Slim v4 that validates a Twilio Event Streams webhook.
 * If the webhook is valid, the next request is called. Otherwise, a 400 Bad Request is returned,
 * along with a problem details response, formatted based on the request's accept header.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7807#page-9
 * @see https://akrabat.com/api-errors-are-first-class-citizens/
 * @psalm-api
 */
class EventStreamsWebhookValidatorMiddleware implements MiddlewareInterface
{
    public function __construct(private string $baseUrl, private string $requestPath, private readonly RequestValidator $validator)
    {
        $this->baseUrl = rtrim($this->baseUrl, '/');
        $this->requestPath = ltrim($this->requestPath, '/');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestBody = (string) $request->getBody();
        $requestURL = $this->baseUrl . "/$this->requestPath?" . http_build_query($request->getQueryParams());

        if ($this->validator->validate(
            $request->getHeaderLine('X-Twilio-Signature'),
            $requestURL,
            $requestBody
        )) {
            return $handler->handle($request);
        }

        return (new ProblemDetailsResponseFactory(new ResponseFactory()))
            ->createResponse(
                $request,
                400,
                "The webhook failed validation.",
                "Webhook is invalid",
                'https://www.twilio.com/docs/usage/webhooks/webhooks-security',
                [
                    "request" => $requestURL,
                    "body" => $requestBody
                ]
            );
    }
}