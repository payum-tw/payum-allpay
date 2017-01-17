<?php

$classes = [
    'ActionType',
    'CarruerType',
    'CheckMacValue',
    'CheckOutFeedback',
    'ClearanceMark',
    'DeviceType',
    'Donation',
    'EncryptType',
    'ExtraPaymentInfo',
    'InvoiceState',
    'InvType',
    'PaymentMethod',
    'PaymentMethodItem',
    'PeriodType',
    'PrintMark',
    'Send',
    'TaxType',
    'UseRedeem',
];

foreach ($classes as $class) {
    class_alias($class, 'PayumTW\Allpay\Bridge\Allpay\\'.$class);
}
