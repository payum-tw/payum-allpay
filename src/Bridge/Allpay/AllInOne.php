<?php

namespace PayumTW\Allpay\Bridge\Allpay;

use PayumTW\Ecpay\Bridge\Ecpay\FormToArrayTrait;

class AllInOne extends \AllInOne
{
    use FormToArrayTrait;

    //取得付款結果通知的方法
    public function CheckOutFeedback()
    {
        $params = func_get_arg(0);

        return CheckOutFeedback::CheckOut($params, $this->HashKey, $this->HashIV, 0);
    }
}
