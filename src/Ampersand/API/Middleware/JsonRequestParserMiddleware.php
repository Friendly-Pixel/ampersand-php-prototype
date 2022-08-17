<?php

namespace Ampersand\API\Middleware;

use Ampersand\Exception\BadRequestException;
use JsonException;

class JsonRequestParserMiddleware
{
    public function __invoke($input)
    {
        try {
            // Set accoc param to false, this will return php stdClass object instead of array for json objects {}
            return json_decode($input, false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestException("JSON error: {$e->getMessage()}", previous: $e);
        }
    }
}
