<?php

declare(strict_types=1);

namespace App\Enums;

enum HttpStatus: int
{
    // 2xx Success
    case Ok = 200;
    case Created = 201;
    case NoContent = 204;

    // 3xx Redirection
    case MovedPermanently = 301;
    case Found = 302;

    // 4xx Client errors
    case BadRequest = 400;
    case Unauthorized = 401;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case UnprocessableEntity = 422;
    case TooManyRequests = 429;

    // 5xx Server errors
    case InternalServerError = 500;
    case ServiceUnavailable = 503;
}
