<?php
    require('../admin/inc/db_config.php');
    require('../admin/inc/essentials.php');
    // require('mail.php');
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;


    function send_mail($email,$name,$token){

        require('PHPMailer/Exception.php');
        require('PHPMailer/SMTP.php');
        require('PHPMailer/PHPMailer.php');

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'kshitiztyagi1011@gmail.com';                     //SMTP username
            $mail->Password   = 'nfgjmgurpjvfsjto';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('kshitiztyagi1011@gmail.com', 'NIVAAS');
            $mail->addAddress($email,$name);     //Add a recipient

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'Account Verification Link';
            $mail->Body    = "Click the link to confirm your email: <br>
                              <a href='".SITE_URL."email_confirm.php?email_confirmation&email=$email&token=$token"."'>
                                CLICK ME
                              </a>
                              ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }

    }

    if(isset($_POST['register']))
    {
        $data = filteration($_POST);

        //match password and confirm password field
        if($data['pass'] != $data['cpass']){
            echo 'pass_mismatch';
            exit;
        }

        //check user exists or not
        $u_exist = select("SELECT * FROM `user_cred` WHERE `email`=? OR `phonenum`=? LIMIT 1",[$data['email'],$data['phonenum']],"ss");
        if(mysqli_num_rows($u_exist) != 0){
            $u_exist_fetch = mysqli_fetch_assoc($u_exist);
            echo ($u_exist_fetch['email'] == $data['email']) ? 'email_already' : 'phone_already';
            exit;
        }

        //upload user image to server
        $img = uploadUserImage($_FILES['profile']);
        if($img == 'inv_img'){
            echo 'inv_img';
            exit;
        }
        else if($img == 'upd_failed'){
            echo 'upd_failed';
            exit;
        }

        //send confirmation Link to user's email
        $token = bin2hex(random_bytes(16));
        if(!send_mail($data['email'],$data['name'],$token)){
            echo 'mail_failed';
            exit;
        }

        $enc_pass = password_hash($data['pass'],PASSWORD_BCRYPT);

        $query = "INSERT INTO `user_cred`(`name`, `email`, `address`, `phonenum`, 
        `pincode`, `dob`, `profile`, `password`, `token`) VALUES (?,?,?,?,?,?,?,?,?)";

        $values = [$data['name'],$data['email'],$data['address'],$data['phonenum'],$data['pincode'],$data['dob'],$img,$enc_pass,$token];

        if(insert($query,$values,'sssssssss')){
            echo 1;
        }
        else{
            echo 'ins_failed';
        }

    }
      
 
 

