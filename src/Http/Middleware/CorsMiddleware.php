<?php

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CorsMiddleware
{
    /**
     * Cors middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $origin = $request->getHeader('Origin')[0] ?? 'https://localhost';
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Expose-Headers', '*');

        if ($request->getMethod() === 'OPTIONS') {
            $response = $response->withHeader('Access-Control-Allow-Methods', 'PUT, POST, GET, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, region, locale, x-token, x-key, Content-Type, Accept, Authorization, access-control-allow-origin');

            return $response;
        }

        return $next($request, $response);
    }
}
