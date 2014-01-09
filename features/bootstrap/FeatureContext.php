<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Akamon\Behat\ApiContext\Domain\ApiContext;
use Akamon\Behat\ApiContext\Domain\Client\ClientInterface;
use Akamon\Behat\ApiContext\Domain\ParameterAccessor\DeepArrayParameterAccessor;
use Akamon\Behat\ApiContext\Domain\ResponseParametersProcessor\JsonResponseParametersProcessor;
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
        $response->setContent(
            $request->request->count() ?
            json_encode($request->request->all()) :
            $request->getContent()
        );

        return $response;
    }
}
