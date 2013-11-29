<?php

namespace Akamon\Behat\ApiContext;

use Akamon\Behat\ApiContext\Client\ClientInterface;
use Akamon\Behat\ApiContext\ParameterAccessor\ParameterAccessorInterface;
use Akamon\Behat\ApiContext\ResponseParametersProcessor\ResponseParametersProcessorInterface;
use Akamon\Behat\ApiContext\RequestFilter\RequestFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Felpado as f;

class ApiContext extends BehatContext
{
    private $client;
    private $parameterAccessor;
    private $responseParametersProcessor;

    private $requestFilter;

    private $requestHeaders = array();
    private $requestParameters = array();
    private $response;
    private $responseParameters;

    public function __construct(ClientInterface $client, ParameterAccessorInterface $parameterAccessor, ResponseParametersProcessorInterface $responseParametersProcessor)
    {
        $this->client = $client;
        $this->parameterAccessor = $parameterAccessor;
        $this->responseParametersProcessor = $responseParametersProcessor;
    }

    public function setRequestFilter(RequestFilterInterface $requestFilter)
    {
        $this->requestFilter = $requestFilter;
    }

    /**
     * @When /^I add the request header "([^"]*)" with "([^"]*)"$/
     */
    public function addRequestHeader($name, $value)
    {
        $this->requestHeaders[$name] = $value;
    }

    /**
     * @When /^I add the request headers:$/
     */
    public function addRequestHeaders(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->addRequestHeader($row[0], $row[1]);
        }
    }

    /**
     * @When /^I add the request parameter "([^"]*)" with "([^"]*)"$/
     */
    public function addRequestParameter($name, $value)
    {
        $this->requestParameters = $this->parameterAccessor->add($this->requestParameters, $name, $value);
    }

    /**
     * @When /^I add the request parameters:$/
     */
    public function addRequestParameters(TableNode $table)
    {
        foreach ($this->parametersFromTable($table) as $name => $value) {
            $this->addRequestParameter($name, $value);
        }
    }

    private function parametersFromTable(TableNode $table)
    {
        return f::renameKeys(
            f::map(function ($v) {
                return f::first(f::rest($v));
            }, $table->getRows()),
            f::map(array('f', 'first'), $table->getRows())
        );
    }

    /**
     * @When /^I make a "([^"]*)" request to "([^"]*)"$/
     */
    public function request($method, $uri)
    {
        $request = $this->createRequest($method, $uri);

        $response = $this->client->request($request);
        $this->setResponse($response);
    }

    private function createRequest($method, $uri)
    {
        $request = Request::create($uri, $method);

        $request->headers->replace($this->requestHeaders);
        $request->request->replace($this->requestParameters);

        return $this->filterRequest($request);
    }

    private function filterRequest(Request $request)
    {
        if ($this->requestFilter) {
            return $this->requestFilter->filter($request);
        }

        return $request;
    }

    private function setResponse(Response $response)
    {
        $this->response = $response;

        $this->responseParameters = $this->responseParametersProcessor->process($response);
    }

    private function getResponse()
    {
        return $this->response;
    }

    /**
     * @When /^I make a "([^"]*)" request to "([^"]*)" with the parameters:$/
     */
    public function requestWith($method, $uri, TableNode $table)
    {
        $this->addRequestParameters($table);
        $this->request($method, $uri);
    }

    /**
     * @Then /^the response status code should be "([^"]*)"$/
     */
    public function theResponseStatusCodeShouldBe($expectedStatusCode)
    {
        $statusCode = $this->getResponse()->getStatusCode();

        if ($statusCode != $expectedStatusCode) {
            throw new \Exception(sprintf('The response status code is "%s" and it should be "%s".', $statusCode, $expectedStatusCode));
        }
    }

    /**
     * @Then /^the response header "([^"]*)" should be "([^"]*)"$/
     */
    public function theResponseHeaderShouldBe($name, $expectedValue)
    {
        $value = $this->getResponse()->headers->get($name);

        if ($value !== $expectedValue) {
            throw new \Exception(sprintf('The response header "%s" is "%s" and it should be "%s".', $name, $value, $expectedValue));
        }
    }

    /**
     * @Then /^the request headers should be:$/
     */
    public function theRequestHeadersShouldBe(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->theResponseHeaderShouldBe($row[0], $row[1]);
        }
    }

    /**
     * @Then /^the response parameter "([^"]*)" should exist$/
     */
    public function theResponseParameterShouldExist($name)
    {
        if (!$this->parameterAccessor->has($this->responseParameters, $name)) {
            throw new \Exception(sprintf('The response parameter "%s" does not exist.', $name));
        }
    }

    /**
     * @Then /^the response parameters should exist:$/
     */
    public function theResponseParametersShouldExist(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->theResponseParameterShouldExist($row[0]);
        }
    }

    /**
     * @Then /^the response parameter "([^"]*)" should be "([^"]*)"$/
     */
    public function theResponseParameterShouldBe($name, $expectedValue)
    {
        $this->theResponseParameterShouldExist($name);

        $value = $this->parameterAccessor->get($this->responseParameters, $name);

        if ($value != $expectedValue) {
            throw new \Exception(sprintf('The response parameter "%s" is "%s" and it should be "%s".', $name, $value, $expectedValue));
        }
    }

    /**
     * @Then /^the response parameters should be:$/
     */
    public function theResponseParametersShouldBe(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->theResponseParameterShouldBe($row[0], $row[1]);
        }
    }

    /**
     * @Then /^the response parameter "([^"]*)" should match "([^"]*)"$/
     */
    public function theResponseParameterShouldMatch($name, $regex)
    {
        $this->theResponseParameterShouldExist($name);

        $value = $this->parameterAccessor->get($this->responseParameters, $name);

        if (!preg_match($regex, $value)) {
            throw new \Exception(sprintf('The response parameter "%s" is "%s" and it should match "%s" but it does not.', $name, $value, $regex));
        }
    }

    /**
     * @Then /^the response parameters should match:$/
     */
    public function theResponseParametersShouldMatch(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->theResponseParameterShouldMatch($row[0], $row[1]);
        }
    }

    /**
     * @Then /^the response parameter "([^"]*)" should not match "([^"]*)"$/
     */
    public function theResponseParameterShouldNotMatch($name, $regex)
    {
        $this->theResponseParameterShouldExist($name);

        $value = $this->parameterAccessor->get($this->responseParameters, $name);

        if (preg_match($regex, $value)) {
            throw new \Exception(sprintf('The response parameter "%s" is "%s" and it should not match "%s" but it does.', $name, $value, $regex));
        }
    }

    /**
     * @Then /^print last response$/
     */
    public function printLastResponse()
    {
        $this->printDebug($this->getResponse());
    }
}