<?php

namespace FM\BaseApiBundle\Features\Context;

use Behat\Gherkin\Node\PyStringNode;

class ApiContext extends BaseFeatureContext
{
    /**
     * @var string
     */
    protected static $authToken;

    /**
     * @var string
     */
    protected static $userToken;

    /**
     * @When /^I GET to "([^"]*)"$/
     */
    public function iGetTo($path)
    {
        $this->request('GET', $path);
    }

    /**
     * @When /^I HEAD to "([^"]*)"$/
     */
    public function iHeadTo($path)
    {
        $this->request('HEAD', $path);
    }

    /**
     * @When /^I POST to "([^"]*)" with:$/
     */
    public function iPostToWith($path, PyStringNode $string)
    {
        $this->request('POST', $path, $string);
    }

    /**
     * @When /^I PUT to "([^"]*)" with:$/
     */
    public function iPutToWith($path, PyStringNode $string)
    {
        $this->request('PUT', $path, $string);
    }

    /**
     * @When /^I DELETE to "([^"]*)"$/
     */
    public function iDeleteTo($path)
    {
        $this->request('DELETE', $path);
    }

    /**
     * @Then /^the response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($content)
    {
        assertNotContains($content, $this->getResponseContent());
    }

    /**
     * @Then /^the response status code should be (\d+)$/
     */
    public function theResponseStatusCodeShouldBe($code)
    {
        assertEquals($code, static::$response->getStatusCode());
    }

    /**
     * @Then /^the response header "([^"]*)" should contain "([^"]*)"$/
     * @Then /^the response header "([^"]*)" contains "([^"]*)"$/
     */
    public function theResponseHeaderContains($header, $value)
    {
        if (false === static::$response->headers->has($header)) {
            throw new \Exception(
                sprintf('Request does not contain %d header', $header)
            );
        }

        assertContains($value, (string) static::$response->headers->get($header));
    }

    /**
     * @Then /^the response should contain "([^"]*)"$/
     * @Then /^the response contains "([^"]*)"$/
     */
    public function theResponseShouldContain($arg1)
    {
        assertContains($arg1, $this->getResponseContent());
    }

    /**
     * @Then /^the response should be (?i)xml(?-i)$/
     * @Then /^the response is (?i)xml(?-i)$/
     */
    public function theResponseIsXml()
    {
        $this->theResponseHeaderContains('Content-type', 'application/xml');

        if (false === simplexml_load_string($this->getResponseContent())) {
            throw new \Exception(
                sprintf(
                    'The response is not valid XML. This was the body: "%s"',
                    $this->getResponseContent()
                )
            );
        }
    }

    /**
     * @Then /^the response should be (?i)json(?-i)$/
     * @Then /^the response is (?i)json(?-i)$/
     */
    public function theResponseIsJson()
    {
        assertThat(
            static::$response->headers->get('Content-Type'),
            logicalOr(
                equalTo('application/json'),
                equalTo('text/javascript')
            )
        );

        assertJson($this->getResponseContent());
    }

    /**
     * @Given /^I have a valid token$/
     */
    public function iHaveAValidToken()
    {
        $this->iHaveAValidTokenForUsernameAndPassword('admin', '1234');
    }

    /**
     * @Given /^I have a valid token for "([^"]*)" with password "([^"]*)"$/
     * @Given /^I have a valid token for username "([^"]*)" and password "([^"]*)"$/
     */
    public function iHaveAValidTokenForUsernameAndPassword($user, $pass)
    {
        $server = [
            'HTTP_HOST' => $this->getContainer()->getParameter('fm_api.token_host')
        ];

        $data = [
            'auth' => [
                'passwordCredentials' => [
                    'username' => $user,
                    'password' => $pass,
                ]
            ]
        ];

        $response = parent::request('POST', '/tokens', json_encode($data), [], $server);
        $result   = json_decode($response->getContent(), true);

        static::$authToken = $result['access']['token']['id'];
    }

    /**
     * @Given /^I have a valid user token$/
     */
    public function iHaveAValidUserToken()
    {
        $this->iHaveAValidUserTokenForUsernameAndPassword('admin', '1234');
    }

    /** @Given /^I have a valid user token for username "([^"]*)" and password "([^"]*)"$/
     */
    public function iHaveAValidUserTokenForUsernameAndPassword($user, $pass)
    {
        $data = [
            'username' => $user,
            'password' => $pass,
        ];

        $response = $this->request('POST', '/login/', json_encode($data));
        $result = json_decode($response->getContent(), true);

        static::$userToken = $result['result']['token'];
    }

    /**
     * @Then /^the (?i)api(?-i) result should be ok$/
     */
    public function theResultShouldBeOk()
    {
        $this->theResponseIsJson();

        $response = $this->getApiResponse();
        assertArrayHasKey('ok', $response);
        assertTrue($response['ok']);
    }

    /**
     * @Then /^the (?i)api(?-i) result should be not ok$/
     */
    public function theResultShouldBeNotOk()
    {
        $this->theResponseIsJson();

        $response = $this->getApiResponse();
        assertArrayHasKey('ok', $response);
        assertFalse($response['ok']);
    }

    /**
     * @Then /^the (?i)api(?-i) result should contain (\d+) items?$/
     * @Then /^the (?i)api(?-i) result contains (\d+) items?$/
     */
    public function theResultContainsExactlyItems($count)
    {
        $result = $this->getApiResult();

        assertContainsOnly('array', $result);
        assertEquals($count, count($result));
    }

    /**
     * @Then /^the (?i)api(?-i) result should contain at least (\d+) items?$/
     * @Then /^the (?i)api(?-i) result contains at least (\d+) items?$/
     */
    public function theResultContainsAtLeastItems($count)
    {
        $result = $this->getApiResult();

        assertContainsOnly('array', $result);
        assertGreaterThanOrEqual($count, count($result));
    }

    /**
     * @Then /^the (?i)api(?-i) result should contain key "([^"]*)"$/
     */
    public function theResultShouldContainKey($key)
    {
        $result = $this->getApiResult();
        assertArrayHasKey($key, $result);
    }

    /**
     * @Then /^the (?i)api(?-i) result key "([^"]*)" should not equal null$/
     */
    public function theResultKeyShouldNotEqualNull($key)
    {
        $result = $this->getApiResult();
        assertArrayHasKey($key, $result);
        assertNotNull($result[$key]);
    }

    /**
     * @Then /^the (?i)api(?-i) result key "([^"]*)" should equal "([^"]*)"$/
     */
    public function theResultKeyShouldEqual($key, $value)
    {
        $result = $this->getApiResult();
        assertArrayHasKey($key, $result);
        assertEquals($value, $result[$key]);
    }

    /**
     * @inheritdoc
     */
    protected function request($method, $uri, $data = null, array $headers = [], array $server = [])
    {
        if (!array_key_exists('HTTP_HOST', $server)) {
            $server['HTTP_HOST'] = $this->getContainer()->getParameter('fm_api.host');
        }

        if (null !== static::$authToken) {
            $headers['X-Auth-Token'] = static::$authToken;
        }

        if (null !== static::$userToken) {
            $headers['X-User-Token'] = static::$userToken;
        }

        // TODO version should be configurable
        return parent::request($method, '/v1/' . ltrim($uri, '/'), $data, $headers, $server);
    }

    /**
     * Returns the complete response from the last api call, decoded to an array.
     *
     * @return array
     */
    protected function getApiResponse()
    {
        return json_decode($this->getResponseContent(), true);
    }

    /**
     * Returns the "result" key of the last api response, json decoded.
     *
     * @return mixed
     */
    protected function getApiResult()
    {
        return $this->getApiResponse()['result'];
    }
}
