<?php

use Utipd\HmacAuth\Validator;
use \Exception;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class ValidatorTest extends \PHPUnit_Framework_TestCase
{


    public function testValidateComponents() {
        $validator = $this->newValidator();

        $nonce = time();
        $expected_signature = $this->expectedSignature($nonce);

        // will throw an exception if it fails
        $validator->validate('GET', 'http://somesite.com/sample/url', ['foo' => 'bar'], 'myapi123', $nonce, $expected_signature, 'mysecret456');
    } 

    public function testValidateFromRequest() {
        $validator = $this->newValidator();

        $nonce = time();
        $expected_signature = $this->expectedSignature($nonce);

        // $uri, $method = 'GET', $parameters = array(), $cookies = array(), $files = array(), $server = array()
        $request = \Symfony\Component\HttpFoundation\Request::create('http://somesite.com/sample/url?foo=bar', 'GET', [], [], [], []);
        $request->headers->set('X-Utipd-Auth-Api-Token', 'myapi123');
        $request->headers->set('X-Utipd-Auth-Nonce',     $nonce);
        $request->headers->set('X-Utipd-Auth-Signature', $expected_signature);

        $params = $validator->validateFromRequest($request);
    } 


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    protected function expectedSignature($nonce) {
        $str = "GET\nhttp://somesite.com/sample/url\n".json_encode((array)['foo' => 'bar'])."\nmyapi123\n".$nonce;
        return base64_encode(hash_hmac('sha256', $str, 'mysecret456', true));
    }


    protected function newValidator() {
        $validator = new Validator(function($api_token) {
            if ($api_token === 'myapi123') { return 'mysecret456'; }
            throw new Exception("Unknown api token: $api_token", 1);
        });
        return $validator;
    }
}
