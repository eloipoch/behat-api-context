Feature: ApiContext
    In order to test an Api
    As a Api creator
    I want to use the ApiContext

    Scenario Outline: StatusCode
        When I request "/users?status_code=<statusCode>" using the method "GET"
        Then the response status code should be "<statusCode>"

        Examples:
        | statusCode |
        | 200        |
        | 404        |
        | 500        |

    Scenario: Single Headers
        When I add the request header "content-type" with "application/json"
        And I add the request header "accept" with "text/xml"
        And I request "/users" using the method "POST"
        Then the response header "content-type" should be "application/json"
        Then the response header "accept" should be "text/xml"

    Scenario: Multiple Headers
        When I add the request headers:
            | content-type | application/json |
            | accept       | text/xml         |
        And I request "/users" using the method "GET"
        Then the request headers should be:
            | content-type | application/json |
            | accept       | text/xml         |

    Scenario: Single Parameters
        When I add the request parameter "one" with "foo"
        And I add the request parameter "two" with "bar"
        And I add the request parameter "three" with "10"
        And I request "/users" using the method "POST"
        Then the response parameter "one" should exist
        And the response parameter "one" should be "foo"
        And the response parameter "two" should be "bar"
        And the response parameter "three" should match "/\d+/"
        And the response parameter "three" should not match "/[a-z]+/"

    Scenario: Multiple Parameters:
        When I add the request parameters:
            | one   | foo |
            | two   | bar |
            | three | 10  |
        And I request "/users" using the method "POST"
        Then the response parameters should exist:
            | one   |
            | two   |
            | three |
        And the response parameters should match:
            | one   | /o/   |
            | three | /\d+/ |
        And the response parameters should be:
            | one   | foo |
            | two   | bar |
            | three | 10  |

    Scenario: Deep Parameters
        When I add the request parameter "one.two" with "foo"
        And I request "/users" using the method "POST"
        Then the response parameter "one.two" should exist
        And the response parameter "one.two" should be "foo"

    Scenario: Print last response
        When I request "/users" using the method "POST"
        Then print last response