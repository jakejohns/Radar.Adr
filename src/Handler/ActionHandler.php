<?php
namespace Radar\Adr\Handler;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Radar\Adr\Resolver;
use Radar\Adr\Router\Route;

class ActionHandler
{
    protected $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) {
        $route = $request->getAttribute('radar/adr:route');
        $request = $request->withoutAttribute('radar/adr:route');
        $response = $this->response($route, $request, $response);
        return $next($request, $response);
    }

    protected function response(
        Route $route,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $responder = $this->resolver->resolve($route->responder);

        if ($route->domain) {
            $payload = $this->payload($route, $request);
            return $responder($request, $response, $payload);
        }

        return $responder($request, $response);
    }

    protected function payload(Route $route, ServerRequestInterface $request)
    {
        $domain = $this->resolver->resolve($route->domain);

        $input = [];
        if ($route->input) {
            $input = $this->resolver->resolve($route->input);
            $input = (array) $input($request);
        }

        return call_user_func_array($domain, $input);
    }
}