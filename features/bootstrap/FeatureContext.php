<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Akamon\Behat\Context\ApiContext;
use Akamon\Behat\Context\ApiContext\ClientInterface;
use Akamon\Behat\Context\ApiContext\DeepArrayParameterAccessor;
use Akamon\Behat\Context\ApiContext\JsonResponseParametersProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureContext extends BehatContext
{
    public function __construct()
    {
        $this->useContext('api_context', $this->createApiContext());
    }

    private function createApiContext()
    {
        return new ApiContext(
            new TestingClient(),
            new DeepArrayParameterAccessor('.'),
            new JsonResponseParametersProcessor()
        );
    }
}

class TestingClient implements ClientInterface
{
    public function request(Request $request)
    {
        $response = new Response();

        $response->setStatusCode($request->query->get('status_code', 200));
        $response->headers->replace($request->headers->all());
        $response->setContent(json_encode($request->request->all()));

        return $response;
    }
}