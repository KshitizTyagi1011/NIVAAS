<?php 
    require('admin/inc/db_config.php');
    require('admin/inc/essentials.php');
     

    require('config.php');
    require('razorpay-php/Razorpay.php');

     use Razorpay\Api\Api;
 
    date_default_timezone_set("Asia/Kolkata");
    

    session_start();

    if(!(isset($_SESSION['login']) && $_SESSION['login'] == true))
    {
        redirect('index.php');
    }

   

    if(isset($_POST['pay_now']))
    {
        $ORDER_ID = 'ORD_'.$_SESSION['uId'].random_int(11111,9999999);
        $CUST_ID = $_SESSION['uId'];
        $TXN_AMOUNT = $_SESSION['room']['payment'];
        $phone =  $_SESSION['uPhone'];
        // $Email =  $_SESSION['uEmail'];


        $frm_data = filteration($_POST);
        $query1 = "INSERT INTO `booking_order`(`user_id`, `room_id`, `check_in`, `check_out`, `order_id`) VALUES (?,?,?,?,?)";

        insert($query1,[$CUST_ID,$_SESSION['room']['id'],$frm_data['checkin'],
            $frm_data['checkout'],$ORDER_ID],'issss');
         
        $booking_id = mysqli_insert_id($con);    
        $query2 = "INSERT INTO `booking_details`(`booking_id`, `room_name`, `price`, `total_pay`, 
            `user_name`, `phonenum`, `address`) VALUES (?,?,?,?,?,?,?)";

        insert($query2,[$booking_id,$_SESSION['room']['name'],$_SESSION['room']['price'],
            $TXN_AMOUNT,$frm_data['name'],$frm_data['phonenum'],$frm_data['address']],'issssss');

        
         $api = new Api($keyId, $keySecret);

        //
        // We create an razorpay order using orders api
        // Docs: https://docs.razorpay.com/docs/orders
        //
        $orderData = [
            'receipt'         => 'rcptid_11',
            'amount'          => $TXN_AMOUNT * 100, // 399 rupees in paise
            'currency'        => 'INR'
        ];

        $razorpayOrder = $api->order->create($orderData);

        $razorpayOrder = $api->order->create($orderData);

        $razorpayOrderId = $razorpayOrder['id'];

        $_SESSION['razorpay_order_id'] = $razorpayOrderId;

        $displayAmount = $amount = $orderData['amount'];

        if ($displayCurrency !== 'INR')
        {
            $url = "https://api.fixer.io/latest?symbols=$displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);

            $displayAmount = $exchange['rates'][$displayCurrency] * $amount / 100;
        }


        $data = [
            "key"               => $keyId,
            "amount"            => $amount,
            "name"              => "NIVAAS",
            "description"       => "Tron Legacy",
            "image"             => "https://s29.postimg.org/r6dj1g85z/daft_punk.jpg",
            "prefill"           => [
            "name"              => "Daft Punk",
            "email"             => "demo@gmail.com",
            "contact"           => "$phone",
            ],
            "notes"             => [
            "address"           => "Hello World",
            "merchant_order_id" => "12312321",
            ],
            "order_id"          => $razorpayOrderId,
        ];

        if ($displayCurrency !== 'INR')
        {
            $data['display_currency']  = $displayCurrency;
            $data['display_amount']    = $displayAmount;
        }

        $json = json_encode($data); 
    }
?>

<form action="verify.php" method="POST">
        <script
            src="https://checkout.razorpay.com/v1/checkout.js"
            data-key="<?php echo $data['key']?>"
            data-amount="<?php echo $data['amount']?>"
            data-currency="INR"
            data-name="<?php echo $data['name']?>"
            data-image="<?php echo $data['image']?>"
            data-description="<?php echo $data['description']?>"
            data-prefill.name="<?php echo $data['prefill']['name']?>"
            data-prefill.email="<?php echo $data['prefill']['email']?>"
            data-prefill.contact="<?php echo $data['prefill']['contact']?>"
            data-notes.shopping_order_id="<?php echo 'Tid_'.$_SESSION['uId'].random_int(11111,9999999); ?>"
            data-order_id="<?php echo $data['order_id']?>"
            <?php if ($displayCurrency !== 'INR') { ?> data-display_amount="<?php echo $data['display_amount']?>" <?php } ?>
            <?php if ($displayCurrency !== 'INR') { ?> data-display_currency="<?php echo $data['display_currency']?>" <?php } ?>
        >
        </script>
        <!-- Any extra fields to be submitted with the form but not sent to Razorpay -->
        <input type="hidden" name="shopping_order_id" value="<?php echo 'Tid_'.$_SESSION['uId'].random_int(11111,9999999); ?>">
           <!-- Include ORDER_ID as a hidden input -->
            <input type="hidden" name="ORDER_ID" value="<?php echo $ORDER_ID; ?>">
            <input type="hidden" name="TXN_AMOUNT" value="<?php echo  $TXN_AMOUNT; ?>">
        </form>