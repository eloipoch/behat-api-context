<?php

namespace Akamon\Behat\ApiContext\ResponseParametersProcessor;

use Symfony\Component\HttpFoundation\Response;

interface ResponseParametersProcessorInterface
{
    function process(Response $response);
}