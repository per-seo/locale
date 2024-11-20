<?php

namespace PerSeo\Middleware\Locale;

use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;

class Locale implements MiddlewareInterface
{
	/** @var array{languages: array<string>, language: string, locale: bool} */
    private array $settings;
	
	/** @var App<ContainerInterface> */
    private App $app;
	
	private bool $active;
	
	/** @var array<string> */
	private array $languages;

    public function __construct(App $app, ContainerInterface $container)
    {
		/** @var array{languages: array<string>, language: string, locale: bool} $settings */
        $this->settings = (array) ($container->has('settings_global') ? $container->get('settings_global') : ['languages' => ['en', 'it'], 'language' => 'en', 'locale' => true]);
        $this->app = $app;
		$this->active = $this->settings['locale'];
		$this->languages = $this->settings['languages'];
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
		$server = (array) $request->getServerParams();
		$fulluri = (string) $request->getUri()->getPath();
		$basepath = (string) $this->app->getBasePath();
		$httplang = (string) array_key_exists('HTTP_ACCEPT_LANGUAGE', $server) ? strtolower(substr($server['HTTP_ACCEPT_LANGUAGE'], 0, 2)) : ($this->settings['language'] ?? 'en');
		$uri = trim(substr((string) $fulluri, strlen((string) $basepath)));
		$uri = empty($uri) ? '/' : $uri;
		if ($uri === '/') {
			$redirectLang = in_array($httplang, $this->languages) ? $httplang : $this->settings['language'];
			$redirectUri = rtrim($basePath, '/') . '/' . $redirectLang . '/';
			$response = new \Slim\Psr7\Response(301); // Crea una risposta con codice 301
			return $response->withHeader('Location', $redirectUri);
		}
		if (($request->getMethod() == 'GET') && ($this->active) && ($uri != '/')) {
			preg_match("/^\/(([a-zA-Z]{2})$|([a-zA-Z]{2})\/)/",$uri,$matches);
			$curlang = (!empty($matches[1]) ? preg_replace('/[^\da-zA-Z]/i', '', $matches[1]) : NULL);
			if (!empty($curlang) && in_array($curlang, $this->languages, true)) {
				$calcuri = ((strlen($uri) == 3) ? '/' : substr($uri, 3));
				$fulluri = (string) $basepath . $calcuri;
				$request = $request->withAttribute('locale', $curlang);
				$request = $request->withUri($request->getUri()->withPath($fulluri));
			}
			else {
				throw new \Slim\Exception\HttpNotFoundException($request);
			}
		}
        $response = $handler->handle($request);    
        return $response;
    }
}