<?php

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Stream;
use PerSeo\Middleware\Locale\Locale;
use Psr\Container\ContainerInterface;
use Slim\Exception\HttpNotFoundException;

class LocaleMiddlewareTest extends TestCase
{
	/** @var App<ContainerInterface>&\PHPUnit\Framework\MockObject\MockObject */
    private App $appMock;
	
	/** @var ContainerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ContainerInterface $containerMock;
	
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->appMock = $this->createMock(App::class);
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->responseFactory = new ResponseFactory();

        $this->containerMock->method('has')
            ->with('settings_global')
            ->willReturn(true);
        $this->containerMock->method('get')
            ->with('settings_global')
            ->willReturn([
                'languages' => ['en', 'it'],
                'language' => 'en',
                'locale' => true,
            ]);
    }

    public function testProcessAddsLocaleAttribute(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/it/test', ['HTTP_ACCEPT_LANGUAGE' => 'it']);
        $streamFactory = new StreamFactory();
        $response = $this->responseFactory->createResponse()
            ->withBody($streamFactory->createStream('Response body'));

        $this->appMock->method('getBasePath')->willReturn('');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $request) {
                return $request->getAttribute('locale') === 'it';
            }))
            ->willReturn($response);

        $localeMiddleware = new Locale($this->appMock, $this->containerMock);
        $response = $localeMiddleware->process($request, $handler);
    }

    public function testProcessThrowsHttpNotFoundExceptionForInvalidLocale(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/de/test', ['HTTP_ACCEPT_LANGUAGE' => 'de']);

        $this->appMock->method('getBasePath')->willReturn('');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $localeMiddleware = new Locale($this->appMock, $this->containerMock);
        $localeMiddleware->process($request, $handler);
    }
}
