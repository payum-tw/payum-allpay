<?php

use Http\Message\MessageFactory;
use Mockery as m;
use Payum\Core\HttpClientInterface;
use PayumTW\Allpay\Api;

class ApiTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_prepare_payment()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $options = [
            'MerchantID' => '2000132',
            'HashKey'    => '5294y06JbISpM5x9',
            'HashIV'     => 'v77hoKGq4kWxNNIS',
            'sandbox'    => false,
        ];

        $params = [
            'ReturnURL'         => 'http://www.allpay.com.tw/receive.php',
            'MerchantTradeNo'   => 'Test'.time(),
            'MerchantTradeDate' => date('Y/m/d H:i:s'),
            'TotalAmount'       => 2000,
            'TradeDesc'         => 'good to drink',
            'ChoosePayment'     => PaymentMethod::ALL,
            'Items'             => [
                [
                    'Name'     => '歐付寶黑芝麻豆漿',
                    'Price'    => 2000,
                    'Currency' => '元',
                    'Quantity' => 1,
                    'URL'      => 'dedwed',
                ],
            ],
        ];

        $httpClient = m::mock(HttpClientInterface::class);
        $message = m::mock(MessageFactory::class);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $api = new Api($options, $httpClient, $message);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $params = $api->preparePayment($params);
        $this->assertSame(CheckMacValue::generate($params, $options['HashKey'], $options['HashIV'], 0), $params['CheckMacValue']);
        $this->assertSame('https://payment.allpay.com.tw/Cashier/AioCheckOut/V2', $api->getApiEndpoint());
    }

    public function test_parse_result()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $options = [
            'MerchantID' => '2000132',
            'HashKey'    => '5294y06JbISpM5x9',
            'HashIV'     => 'v77hoKGq4kWxNNIS',
            'sandbox'    => false,
        ];

        $params = [
            'MerchantID'           => '2000132',
            'MerchantTradeNo'      => '57CBC66A39F82',
            'PayAmt'               => '340',
            'PaymentDate'          => '2016/09/04 15:03:08',
            'PaymentType'          => 'Credit_CreditCard',
            'PaymentTypeChargeFee' => '3',
            'RedeemAmt'            => '0',
            'RtnCode'              => '1',
            'RtnMsg'               => 'Succeeded',
            'SimulatePaid'         => '0',
            'TradeAmt'             => '340',
            'TradeDate'            => '2016/09/04 14:59:13',
            'TradeNo'              => '1609041459136128',
            'CheckMacValue'        => '6812D213BF2C5B9377EBF101607BF2DF',
        ];

        $httpClient = m::mock(HttpClientInterface::class);
        $message = m::mock(MessageFactory::class);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $api = new Api($options, $httpClient, $message);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $params = $api->parseResult($params);

        $expected = [
            'MerchantID'            => '2000132',
            'MerchantTradeNo'       => '57CBC66A39F82',
            'PayAmt'                => '340',
            'PaymentDate'           => '2016/09/04 15:03:08',
            'PaymentType'           => 'Credit_CreditCard',
            'PaymentTypeChargeFee'  => '3',
            'RedeemAmt'             => '0',
            'RtnCode'               => '1',
            'RtnMsg'                => 'Succeeded',
            'SimulatePaid'          => '0',
            'TradeAmt'              => '340',
            'TradeDate'             => '2016/09/04 14:59:13',
            'TradeNo'               => '1609041459136128',
            'CheckMacValue'         => '6812D213BF2C5B9377EBF101607BF2DF',
            'statusReason'          => '成功',
         ];

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $params[$key]);
        }

        $this->assertSame('https://payment.allpay.com.tw/Cashier/AioCheckOut/V2', $api->getApiEndpoint());
    }

    public function test_parse_result_fail()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $options = [
            'MerchantID' => '2000132',
            'HashKey'    => '5294y06JbISpM5x9',
            'HashIV'     => 'v77hoKGq4kWxNNIS',
            'sandbox'    => false,
        ];

        $params = [
            'MerchantID'           => '2000132',
            'MerchantTradeNo'      => '57CBC66A39F82',
            'PayAmt'               => '340',
            'PaymentDate'          => '2016/09/04 15:03:08',
            'PaymentType'          => 'Credit_CreditCard',
            'PaymentTypeChargeFee' => '3',
            'RedeemAmt'            => '0',
            'RtnCode'              => '1',
            'RtnMsg'               => 'Succeeded',
            'SimulatePaid'         => '0',
            'TradeAmt'             => '340',
            'TradeDate'            => '2016/09/04 14:59:13',
            'TradeNo'              => '1609041459136128',
            'CheckMacValue'        => '6812D213BF2C5B9377EBF101607BF2DD',
        ];

        $httpClient = m::mock(HttpClientInterface::class);
        $message = m::mock(MessageFactory::class);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $api = new Api($options, $httpClient, $message);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $params = $api->parseResult($params);
        $this->assertSame('10400002', $params['RtnCode']);
    }

    public function test_sandbox()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $options = [
            'MerchantID' => '2000132',
            'HashKey'    => '5294y06JbISpM5x9',
            'HashIV'     => 'v77hoKGq4kWxNNIS',
            'sandbox'    => true,
        ];

        $params = [

        ];

        $httpClient = m::mock(HttpClientInterface::class);
        $message = m::mock(MessageFactory::class);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $api = new Api($options, $httpClient, $message);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $this->assertSame('https://payment-stage.allpay.com.tw/Cashier/AioCheckOut/V2', $api->getApiEndpoint());
    }
}