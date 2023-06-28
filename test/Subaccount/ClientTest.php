<?php

declare(strict_types=1);

namespace VonageTest\Subaccount;

use Laminas\Diactoros\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Vonage\Client;
use Vonage\Client\APIResource;
use Vonage\Subaccount\Client as SubaccountClient;
use Vonage\Subaccount\Filter\Subaccount;
use Vonage\Subaccount\Request\NumberTransferRequest;
use Vonage\Subaccount\Request\TransferRequest;
use Vonage\Subaccount\SubaccountObjects\Account;
use Vonage\Subaccount\SubaccountObjects\BalanceTransfer;
use Vonage\Subaccount\SubaccountObjects\CreditTransfer;
use VonageTest\Psr7AssertionTrait;
use VonageTest\VonageTestCase;

class ClientTest extends VonageTestCase
{
    use Psr7AssertionTrait;

    protected APIResource $api;

    protected Client|ObjectProphecy $vonageClient;
    protected $subaccountClient;

    public function setUp(): void
    {
        $this->vonageClient = $this->prophesize(Client::class);
        $this->vonageClient->getCredentials()->willReturn(
            new Client\Credentials\Basic('abc', 'def'),
        );

        /** @noinspection PhpParamsInspection */
        $this->api = (new APIResource())
            ->setIsHAL(true)
            ->setErrorsOn200(false)
            ->setClient($this->vonageClient->reveal())
            ->setBaseUrl('https://api.nexmo.com/accounts');

        $this->subaccountClient = new SubaccountClient($this->api);
    }

    public function testClientInitialises(): void
    {
        $this->assertInstanceOf(SubaccountClient::class, $this->subaccountClient);
    }

    public function testUsesCorrectAuth(): void
    {
        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $this->assertEquals(
                'Basic ',
                mb_substr($request->getHeaders()['Authorization'][0], 0, 6)
            );
            return true;
        }))->willReturn($this->getResponse('get-success'));

        $apiKey = 'something';
        $response = $this->subaccountClient->getPrimaryAccount($apiKey);
    }

    public function testWillUpdateSubaccount(): void
    {
        $apiKey = 'acc6111f';
        $subaccountKey = 'bbe6222f';

        $payload = [
            'suspended' => true,
            'use_primary_account_balance' => false,
            'name' => 'Subaccount department B'
        ];

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/subaccounts/bbe6222f',
                $uriString
            );
            $this->assertRequestMethod('PATCH', $request);

            return true;
        }))->willReturn($this->getResponse('patch-success'));

        $response = $this->subaccountClient->updateSubaccount($apiKey, $subaccountKey, $payload);
        $this->assertIsArray($response);
    }

    public function testCanGetPrimaryAccount(): void
    {
        $apiKey = 'acc6111f';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/subaccounts',
                $uriString
            );
            $this->assertRequestMethod('GET', $request);


            return true;
        }))->willReturn($this->getResponse('get-success'));

        $response = $this->subaccountClient->getPrimaryAccount($apiKey);
        $this->assertInstanceOf(Account::class, $response);
    }

    public function testWillCreateSubaccount(): void
    {
        $apiKey = 'acc6111f';

        $payload = [
            'name' => 'sub name',
            'secret' => 's5r3fds',
            'use_primary_account_balance' => false
        ];

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/subaccounts',
                $uriString
            );
            $this->assertRequestMethod('POST', $request);
            $this->assertRequestJsonBodyContains('name', 'sub name', $request);
            $this->assertRequestJsonBodyContains('secret', 's5r3fds', $request);
            $this->assertRequestJsonBodyContains('use_primary_account_balance', false, $request);

            return true;
        }))->willReturn($this->getResponse('create-success'));

        $response = $this->subaccountClient->createSubaccount($apiKey, $payload);
        $this->assertEquals('sub name', $response['name']);
        $this->assertEquals('s5r3fds', $response['secret']);
        $this->assertEquals(false, $response['use_primary_account_balance']);
    }

    public function testWillGetAccount(): void
    {
        $apiKey = 'acc6111f';
        $subaccountKey = 'bbe6222f';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/subaccounts/bbe6222f',
                $uriString
            );
            $this->assertRequestMethod('GET', $request);


            return true;
        }))->willReturn($this->getResponse('get-individual-success'));

        $response = $this->subaccountClient->getSubaccount($apiKey, $subaccountKey);
        $this->assertInstanceOf(Account::class, $response);
        $this->assertEquals('Get Subaccount', $response->getName());
    }

    public function testCanGetSubaccounts(): void
    {
        $apiKey = 'acc6111f';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/subaccounts',
                $uriString
            );
            $this->assertRequestMethod('GET', $request);

            return true;
        }))->willReturn($this->getResponse('get-success-subaccounts'));

        $response = $this->subaccountClient->getSubaccounts($apiKey);

        foreach ($response as $item) {
            $this->assertInstanceOf(Account::class, $item);
        }
    }

    public function testWillTransferCredit(): void
    {
        $apiKey = 'acc6111f';

        $payload = [
            'from' => 'acc6111f',
            'to' => 's5r3fds',
            'amount' => '123.45',
            'reference' => 'this is a credit transfer'
        ];

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/credit-transfers',
                $uriString
            );
            $this->assertRequestMethod('POST', $request);
            $this->assertRequestJsonBodyContains('from', 'acc6111f', $request);
            $this->assertRequestJsonBodyContains('to', 's5r3fds', $request);
            $this->assertRequestJsonBodyContains('amount', 123.45, $request);
            $this->assertRequestJsonBodyContains('reference', 'this is a credit transfer', $request);

            return true;
        }))->willReturn($this->getResponse('make-credit-transfer-success'));

        $transferRequest = (new TransferRequest($apiKey))
            ->setFrom($payload['from'])
            ->setTo($payload['to'])
            ->setAmount($payload['amount'])
            ->setReference($payload['reference']);

        $response = $this->subaccountClient->makeCreditTransfer($transferRequest);
        $this->assertInstanceOf(CreditTransfer::class, $response);
    }

    public function testWillListCreditTransfers(): void
    {
        $apiKey = 'acc6111f';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/credit-transfers?start_date=2022-01-01&end_date=2022-01-05&subaccount=s5r3fds',
                $uriString
            );
            $this->assertRequestMethod('GET', $request);
            $this->assertRequestQueryContains('start_date', '2022-01-01', $request);
            $this->assertRequestQueryContains('end_date', '2022-01-05', $request);
            $this->assertRequestQueryContains('subaccount', 's5r3fds', $request);

            return true;
        }))->willReturn($this->getResponse('get-credit-transfers-success'));

        $filter = new Subaccount([
            'start_date' => '2022-01-01',
            'end_date'=> '2022-01-05',
            'subaccount' => 's5r3fds'
        ]);

        $response = $this->subaccountClient->getCreditTransfers($apiKey, $filter);

        foreach ($response as $item) {
            $this->assertInstanceOf(CreditTransfer::class, $item);
        }

        $this->assertCount(2, $response);
    }

    public function testWillListBalanceTransfers(): void
    {
        $apiKey = 'acc6111f';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/balance-transfers?start_date=2022-01-01&end_date=2022-01-05&subaccount=s5r3fds',
                $uriString
            );
            $this->assertRequestMethod('GET', $request);
            $this->assertRequestQueryContains('start_date', '2022-01-01', $request);
            $this->assertRequestQueryContains('end_date', '2022-01-05', $request);
            $this->assertRequestQueryContains('subaccount', 's5r3fds', $request);

            return true;
        }))->willReturn($this->getResponse('get-balance-transfers-success'));

        $filter = new Subaccount([
            'start_date' => '2022-01-01',
            'end_date'=> '2022-01-05',
            'subaccount' => 's5r3fds'
        ]);

        $response = $this->subaccountClient->getBalanceTransfers($apiKey, $filter);

        foreach ($response as $item) {
            $this->assertInstanceOf(BalanceTransfer::class, $item);
        }

        $this->assertCount(2, $response);
    }

    public function testCanTransferBalance(): void
    {
        $apiKey = 'acc6111f';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/balance-transfers',
                $uriString
            );
            $this->assertRequestMethod('POST', $request);

            $this->assertRequestJsonBodyContains('from', 'acc6111f', $request);
            $this->assertRequestJsonBodyContains('to', 's5r3fds', $request);
            $this->assertRequestJsonBodyContains('amount', 123.45, $request);
            $this->assertRequestJsonBodyContains('reference', 'this is a balance transfer', $request);

            return true;
        }))->willReturn($this->getResponse('make-balance-transfer-success'));

        $balanceTransferRequest = (new TransferRequest($apiKey))
            ->setTo('s5r3fds')
            ->setFrom('acc6111f')
            ->setAmount('123.45')
            ->setReference('this is a balance transfer');

        $response = $this->subaccountClient->makeBalanceTransfer($balanceTransferRequest);

        $this->assertInstanceOf(BalanceTransfer::class, $response);
    }

    public function testWillTransferNumber(): void
    {
        $apiKey = 'acc6111f';

        $this->vonageClient->send(Argument::that(function (RequestInterface $request) {
            $uri = $request->getUri();
            $uriString = $uri->__toString();
            $this->assertEquals(
                'https://api.nexmo.com/accounts/acc6111f/transfer-number',
                $uriString
            );
            $this->assertRequestMethod('POST', $request);

            $this->assertRequestJsonBodyContains('from', 'acc6111f', $request);
            $this->assertRequestJsonBodyContains('to', 's5r3fds', $request);
            $this->assertRequestJsonBodyContains('number', '4477705478484', $request);
            $this->assertRequestJsonBodyContains('country', 'GB', $request);

            return true;
        }))->willReturn($this->getResponse('number-transfer-success'));

        $numberTransferRequest = (new NumberTransferRequest($apiKey))
            ->setFrom('acc6111f')
            ->setTo('s5r3fds')
            ->setNumber('4477705478484')
            ->setCountry('GB');

        $response = $this->subaccountClient->makeNumberTransfer($numberTransferRequest);
        $this->assertIsArray($response);
        $this->assertEquals('acc6111f', $response['from']);
    }

    /**
     * This method gets the fixtures and wraps them in a Response object to mock the API
     */
    protected function getResponse(string $identifier, int $status = 200): Response
    {
        return new Response(fopen(__DIR__ . '/Fixtures/Responses/' . $identifier . '.json', 'rb'), $status);
    }
}
