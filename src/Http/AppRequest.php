<?php

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request as SlimRequest;

class AppRequest extends SlimRequest implements ServerRequestInterface
{
}
