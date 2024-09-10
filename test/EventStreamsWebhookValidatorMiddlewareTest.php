<?php
declare(strict_types=1);

namespace EventStreamsTest;

use EventStreams\EventStreamsWebhookValidatorMiddleware;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Twilio\Security\RequestValidator;

class EventStreamsWebhookValidatorMiddlewareTest extends TestCase
{
    private string $bodyContents = <<<EOF
{"specversion":"1.0","type":"com.twilio.messaging.inbound-message.received","source":"/2010-04-01/Accounts/AC0000000000000000000000000000000d/Messages/SM00000000000000000000000000000006.json","id":"EZ00000000000000000000000000000dee","dataschema":"https://events-schemas.twilio.com/Messaging.InboundMessageV1/1","datacontenttype":"application/json","time":"2024-08-16T00:25:31.000Z","data":{"numMedia":0,"timestamp":"2024-08-16T00:25:31.000Z","accountSid":"AC0000000000000000000000000000000d","to":"whatsapp:+14111111111","numSegments":1,"messageSid":"SMe0000000000000000000000000000006","eventName":"com.twilio.messaging.inbound-message.received","body":"Hello","from":"whatsapp:+61000000001"}}
EOF;

    #[TestWith(['https://example.org/', '/webhook', 'https://example.org', 'webhook'])]
    public function testContinuesWithCurrentRequestIfWebhookIsValid(
        string $baseURL,
        string $requestPath,
        string $expectedBaseURL,
        string $expectedRequestPath,
    ) {
        $body = $this->createMock(StreamInterface::class);
        $body
            ->expects($this->once())
            ->method('__toString')
            ->willReturn($this->bodyContents);

        $webhookSignature = 'I5HQVI+6j6v2+/lGiFdQxZfSoII=';
        $queryParams = [
            "bodySHA256" => "ee2f3dad95394d90e60fbb872121c0b8fe22e347d6378a94ed86af4d436161dc",
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('X-Twilio-Signature')
            ->willReturn($webhookSignature);
        $request
            ->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($queryParams);
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $validator = $this->createMock(RequestValidator::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->with(
                $webhookSignature,
                sprintf("%s/%s?%s", $expectedBaseURL, $expectedRequestPath, http_build_query($queryParams)),
                $this->bodyContents
            )
            ->willReturn(true);

        $middleware = new EventStreamsWebhookValidatorMiddleware($baseURL, $requestPath, $validator);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn((new Response())->withStatus(200));
        $response = $middleware->process(
            $request,
            $handler,
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturnsJsonApiResponseIfWebhookIsInvalidAndRequestClientTypeIsJson()
    {
        $body = $this->createMock(StreamInterface::class);
        $body
            ->expects($this->once())
            ->method('__toString')
            ->willReturn($this->bodyContents);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $validator = $this->createMock(RequestValidator::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $middleware = new EventStreamsWebhookValidatorMiddleware('https://example.org', 'webhook', $validator);
        $response = $middleware->process(
            $request,
            $this->createMock(RequestHandlerInterface::class),
        );

        $expectedResponseBody = <<<EOF
{"request":"https://example.org/webhook?","body":"{\"specversion\":\"1.0\",\"type\":\"com.twilio.messaging.inbound-message.received\",\"source\":\"/2010-04-01/Accounts/AC0000000000000000000000000000000d/Messages/SM00000000000000000000000000000006.json\",\"id\":\"EZ00000000000000000000000000000dee\",\"dataschema\":\"https://events-schemas.twilio.com/Messaging.InboundMessageV1/1\",\"datacontenttype\":\"application/json\",\"time\":\"2024-08-16T00:25:31.000Z\",\"data\":{\"numMedia\":0,\"timestamp\":\"2024-08-16T00:25:31.000Z\",\"accountSid\":\"AC0000000000000000000000000000000d\",\"to\":\"whatsapp:+14111111111\",\"numSegments\":1,\"messageSid\":\"SMe0000000000000000000000000000006\",\"eventName\":\"com.twilio.messaging.inbound-message.received\",\"body\":\"Hello\",\"from\":\"whatsapp:+61000000001\"}}","title":"Webhook is invalid","type":"https://www.twilio.com/docs/usage/webhooks/webhooks-security","status":400,"detail":"The webhook failed validation."}
EOF;

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame("application/problem+json", $response->getHeaderLine('Content-Type'));
        $this->assertSame($expectedResponseBody, (string) $response->getBody());
    }

    public function testReturnsXMLResponseIfWebhookIsInvalidAndRequestClientTypeIsXml()
    {
        $body = $this->createMock(StreamInterface::class);
        $body
            ->expects($this->once())
            ->method('__toString')
            ->willReturn($this->bodyContents);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $request
            ->expects($this->atMost(2))
            ->method('getHeaderLine')
            ->willReturnOnConsecutiveCalls(
                'I5HQVI+6j6v2+/lGiFdQxZfSoII=',
                'application/problem+xml'
            );
        $request
            ->expects($this->once())
            ->method('getQueryParams')
            ->willReturn([
                "bodySHA256" => "ee2f3dad95394d90e60fbb872121c0b8fe22e347d6378a94ed86af4d436161dc",
            ]);

        $validator = $this->createMock(RequestValidator::class);
        $validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $middleware = new EventStreamsWebhookValidatorMiddleware('https://example.org', 'webhook', $validator);
        $response = $middleware->process(
            $request,
            $this->createMock(RequestHandlerInterface::class),
        );

        $expectedResponseBody = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<problem xmlns="urn:ietf:rfc:7807"><request>https://example.org/webhook?bodySHA256=ee2f3dad95394d90e60fbb872121c0b8fe22e347d6378a94ed86af4d436161dc</request><body>{"specversion":"1.0","type":"com.twilio.messaging.inbound-message.received","source":"/2010-04-01/Accounts/AC0000000000000000000000000000000d/Messages/SM00000000000000000000000000000006.json","id":"EZ00000000000000000000000000000dee","dataschema":"https://events-schemas.twilio.com/Messaging.InboundMessageV1/1","datacontenttype":"application/json","time":"2024-08-16T00:25:31.000Z","data":{"numMedia":0,"timestamp":"2024-08-16T00:25:31.000Z","accountSid":"AC0000000000000000000000000000000d","to":"whatsapp:+14111111111","numSegments":1,"messageSid":"SMe0000000000000000000000000000006","eventName":"com.twilio.messaging.inbound-message.received","body":"Hello","from":"whatsapp:+61000000001"}}</body><title>Webhook is invalid</title><type>https://www.twilio.com/docs/usage/webhooks/webhooks-security</type><status>400</status><detail>The webhook failed validation.</detail></problem>

EOF;

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        //$this->assertSame("application/problem+xml", $response->getHeaderLine('Content-Type'));
        $this->assertSame($expectedResponseBody, (string) $response->getBody());
    }
}