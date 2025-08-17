<?php

use App\Orm\OrganizationQuery;
use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$isAuthorized = function (Request $request, Response $response, callable $next) {

    $token = $request->getHeader('X-Token')[0] ?? null;

    if (!$token) {
        return $response->withJson(['msg' => 'X-Token is required'])->withStatus(401);
    }

    if (strlen($token) < 128) {
        // Handle API token
        $organization = OrganizationQuery::create()->findOneByApiKey($token);
        $userId = $organization->getUsers()->getFirst()->getId();
        if (!$organization) {
            return $response->withJson(['msg' => 'Token not found'])->withStatus(401);
        }

        $jwtData = (object) ['organization_id' => $organization->getId(), 'user_id' => $userId];

        return $next($request->withAttribute('jwt_data', $jwtData), $response);
    }

    try {
        // Handle JWT
        $data = JWT::decode($token, new Key($_ENV['JWT_SECRET_KEY'], 'HS256'));
    } catch (\Throwable $e) {
        return $response->withJson(['msg' => $e->getMessage()])->withStatus(401);
    }

    return $next($request->withAttribute('jwt_data', $data), $response);
};
