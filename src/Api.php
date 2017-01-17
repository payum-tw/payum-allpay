<?php

namespace PayumTW\Allpay;

use Exception;
use Detection\MobileDetect;
use Http\Message\MessageFactory;
use Payum\Core\HttpClientInterface;
use PayumTW\Allpay\Bridge\Allpay\AllInOne;
use PayumTW\Allpay\Bridge\Allpay\DeviceType;
use PayumTW\Allpay\Bridge\Allpay\InvoiceState;

class Api
{
    /**
     * $client.
     *
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * MessageFactory.
     *
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * $options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * $api.
     *
     * @var \PayumTW\Allpay\Bridge\Allpay\AllInOne
     */
    protected $api;

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory, AllInOne $api = null)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
        $this->api = is_null($api) === true ? new AllInOne() : $api;
        $this->api->HashKey = $this->options['HashKey'];
        $this->api->HashIV = $this->options['HashIV'];
        $this->api->MerchantID = $this->options['MerchantID'];
    }

    /**
     * getApiEndpoint.
     *
     * @return string
     */
    public function getApiEndpoint($name = 'AioCheckOut')
    {
        $map = [
            'AioCheckOut' => 'https://payment.allpay.com.tw/Cashier/AioCheckOut/V2',
            'QueryTradeInfo' => 'https://payment.allpay.com.tw/Cashier/QueryTradeInfo/V2',
            'QueryPeriodCreditCardTradeInfo' => 'https://payment.allpay.com.tw/Cashier/QueryCreditCardPeriodInfo',
            'DoAction' => 'https://payment.allpay.com.tw/CreditDetail/DoAction',
            'AioChargeback' => 'https://payment.allpay.com.tw/Cashier/AioChargeback',
        ];

        if ($this->options['sandbox'] === true) {
            $map = [
                'AioCheckOut' => 'https://payment-stage.allpay.com.tw/Cashier/AioCheckOut/V2',
                'QueryTradeInfo' => 'https://payment-stage.allpay.com.tw/Cashier/QueryTradeInfo/V2',
                'QueryPeriodCreditCardTradeInfo' => 'https://payment-stage.allpay.com.tw/Cashier/QueryCreditCardPeriodInfo',
                'DoAction' => null,
                'AioChargeback' => 'https://payment-stage.allpay.com.tw/Cashier/AioChargeback',
            ];
        }

        return $map[$name];
    }

    /**
     * createTransaction.
     *
     * @param array $params
     * @param mixed $request
     *
     * @return array
     */
    public function createTransaction(array $params)
    {
        $this->api->ServiceURL = $this->getApiEndpoint('AioCheckOut');
        $this->api->Send['MerchantTradeDate'] = date('Y/m/d H:i:s');
        $this->api->Send['DeviceSource'] = $this->isMobile() ? DeviceType::Mobile : DeviceType::PC;
        $this->api->Send = array_replace(
            $this->api->Send,
            array_intersect_key($params, $this->api->Send)
        );

        // 電子發票參數
        /*
        $api->Send['InvoiceMark'] = InvoiceState::Yes;
        $api->SendExtend['RelateNumber'] = $MerchantTradeNo;
        $api->SendExtend['CustomerEmail'] = 'test@allpay.com.tw';
        $api->SendExtend['CustomerPhone'] = '0911222333';
        $api->SendExtend['TaxType'] = TaxType::Dutiable;
        $api->SendExtend['CustomerAddr'] = '台北市南港區三重路19-2號5樓D棟';
        $api->SendExtend['InvoiceItems'] = array();
        // 將商品加入電子發票商品列表陣列
        foreach ($api->Send['Items'] as $info)
        {
            array_push($api->SendExtend['InvoiceItems'],array('Name' => $info['Name'],'Count' =>
                $info['Quantity'],'Word' => '個','Price' => $info['Price'],'TaxType' => TaxType::Dutiable));
        }
        $api->SendExtend['InvoiceRemark'] = '測試發票備註';
        $api->SendExtend['DelayDay'] = '0';
        $api->SendExtend['InvType'] = InvType::General;
        */

        return $this->api->formToArray($this->api->CheckOutString());
    }

    /**
     * cancelTransaction.
     *
     * @param array $params
     *
     * @return array
     */
    public function cancelTransaction($params)
    {
        $this->api->ServiceURL = $this->getApiEndpoint('DoAction');
        $this->api->Action = array_replace(
            $this->api->Action,
            array_intersect_key($params, $this->api->Action)
        );

        return $this->api->DoAction();
    }

    /**
     * refundTransaction.
     *
     * @param array $params
     *
     * @return array
     */
    public function refundTransaction($params)
    {
        $this->api->ServiceURL = $this->getApiEndpoint('AioChargeback');
        $this->api->ChargeBack = array_replace(
            $this->api->ChargeBack,
            array_intersect_key($params, $this->api->ChargeBack)
        );

        return $this->api->AioChargeback();
    }

    /**
     * getTransactionData.
     *
     * @param mixed $params
     *
     * @return array
     */
    public function getTransactionData($params)
    {
        $details = [];
        if (empty($params['response']) === false) {
            if ($this->verifyHash($params['response']) === false) {
                $details['RtnCode'] = '10400002';
            } else {
                $details = $params['response'];
            }
        } else {
            $this->api->ServiceURL = $this->getApiEndpoint('QueryTradeInfo');
            $this->api->Query['MerchantTradeNo'] = $params['MerchantTradeNo'];
            $details = $this->api->QueryTradeInfo();
            $details['RtnCode'] = $details['TradeStatus'] === '1' ? '1' : '2';
        }

        return $details;
    }

    /**
     * Verify if the hash of the given parameter is correct.
     *
     * @param array $params
     *
     * @return bool
     */
    public function verifyHash(array $params)
    {
        $result = false;
        try {
            $this->api->CheckOutFeedback($params);
            $result = true;
        } catch (Exception $e) {
        }

        return $result;
    }

    /**
     * isMobile.
     *
     * @return bool
     */
    protected function isMobile()
    {
        $detect = new MobileDetect();

        return ($detect->isMobile() === false && $detect->isTablet() === false) ? false : true;
    }
}
