<?php

namespace app\mobile\controller;
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use PayPal\Api\PaymentExecution;
class Paypal extends MobileApi
{
    const clientId = 'AUOpawUd-zdbwjn3dEH2pNhxTYpDwCgysiAF02_DtmgsZNhTbk2NPnGWXQ1strKJdEXXlEc3QMCsqb8R';//ID

    const clientSecret = '';//秘钥

    const accept_url = 'http://test.pay.com/PayPal/Callback';//回调地址

    const Currency = 'USD';//币种

    const error_log = 'PayPal-error.log';//错误日志

    const success_log = 'PayPal-success.log';//成功日志

    protected $PayPal;


    public function __construct()
    {

        $this->PayPal = new ApiContext(
            new OAuthTokenCredential(
                self::clientId,
                self::clientSecret
            )
        );

        $this->PayPal->setConfig(
            array(
                'mode' => 'sandbox', //'mode' => 'live',
                'http.ConnectionTimeOut' => 30,
            )
        );
    }


    public function index()
    {
        $product = input('product');
        if (empty($product)) {
            return json(['status'=>false,'msg'=>'商品不能为空']);
        }

        $price = input('price');
        if (empty($price)) {
            return json(['status'=>false,'msg'=>'价格不能为空']);
        }

        $shipping = input('shipping', 0);


        $description = input('description');
        if (empty($description)) {
            return json(['status'=>false,'msg'=>'描述内容不能为空']);
        }

        $this->pay($product, $price, $shipping, $description);
    }

    /**
     * @param
     * $product 商品
     * $price 价钱
     * $shipping 运费
     * $description 描述内容
     */
    public function pay($product, $price, $shipping = 0, $description)
    {
        $paypal = $this->PayPal;

        $total = $price + $shipping;//总价

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item = new Item();
        $item->setName($product)->setCurrency(self::Currency)->setQuantity(1)->setPrice($price);

        $itemList = new ItemList();
        $itemList->setItems([$item]);

        $details = new Details();
        $details->setShipping($shipping)->setSubtotal($price);

        $amount = new Amount();
        $amount->setCurrency(self::Currency)->setTotal($total)->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList)->setDescription($description)->setInvoiceNumber(uniqid());

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(self::accept_url . '?success=true')->setCancelUrl(self::accept_url . '/?success=false');

        $payment = new Payment();
        $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);

        try {
            $payment->create($paypal);
        } catch (PayPalConnectionException $e) {
            echo $e->getData();
            die();
        }

        $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");
    }

}