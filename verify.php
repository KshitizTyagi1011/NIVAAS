<?php

require('config.php');
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');
 
require('razorpay-php/Razorpay.php');

session_start();

require('razorpay-php/Razorpay.php');
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;


unset($_SESSION['room']);

function regenrate_session($uid)
{
    $user_q = select("SELECT * FROM `user_cred` WHERE `id`=? LIMIT 1",[$uid],'i');
    $user_fetch = mysqli_fetch_assoc($user_q);

    $_SESSION['login'] = true;
    $_SESSION['uId'] = $user_fetch['id'];
    $_SESSION['uName'] = $user_fetch['name'];
    $_SESSION['uPic'] = $user_fetch['profile'];
    $_SESSION['uPhone'] = $user_fetch['phonenum'];
    $_SESSION['uEmail'] = $user_fetch['email'];
}

$success = true;

//$error = "Payment Failed";

if (empty($_POST['razorpay_payment_id']) === false)
{
    $api = new Api($keyId, $keySecret);

    try
    {
        // Please note that the razorpay order ID must
        // come from a trusted source (session here, but
        // could be database or something else)
        $attributes = array(
            'razorpay_order_id' => $_SESSION['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        );

        $api->utility->verifyPaymentSignature($attributes);
    }
    catch(SignatureVerificationError $e)
    {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }
}

if ($success === true)
{
    $slct_query = "SELECT `booking_id`, `user_id` FROM `booking_order`
     WHERE `order_id`='$_POST[ORDER_ID]'";

    $slct_res = mysqli_query($con,$slct_query);
    if(mysqli_num_rows($slct_res)==0){
        redirect('index.php');
    } 
    
    $slct_fetch = mysqli_fetch_assoc($slct_res);

    if(!(isset($_SESSION['login']) && $_SESSION['login'] == true)){
        //regenerate session
        regenrate_session($slct_res['user_id']);
    }        
     
    $upd_query = "UPDATE `booking_order` SET `booking_status`='booked',
        `trans_id`='$_POST[shopping_order_id]',`trans_amt`='$_POST[TXN_AMOUNT]',
        `trans_status`='success',`trans_resp_msg`='done'
         WHERE `booking_id`='$slct_fetch[booking_id]' ";

         mysqli_query($con,$upd_query);
         redirect('pay_status.php?order='.$_POST['ORDER_ID']);
}
else
{
    $upd_query = "UPDATE `booking_order` SET `booking_status`='payment failed',
    `trans_id`='$_POST[shopping_order_id]',`trans_amt`='$_POST[TXN_AMOUNT]',
    `trans_status`='Payment Failed',`trans_resp_msg`='Not done'
     WHERE `booking_id`='$slct_fetch[booking_id]' ";

    mysqli_query($con,$upd_query);
    redirect('pay_status.php?order='.$_POST['ORDER_ID']);
}



?>
