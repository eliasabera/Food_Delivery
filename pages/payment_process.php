<?php
session_start();


// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php");
    exit();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  

   
      $email =  $_SESSION['email'];
      $first_name =$_SESSION['first_name'];
      $last_name = $_SESSION['last_name'];
    

    $amount = 10;
    $phone = $_POST['phone_number'];
 $tx_ref = "chewatatest-" . time(); 
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.chapa.co/v1/transaction/initialize',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(array(
      "amount" => $amount,
      "currency" => "ETB",
      "email" => $email,
      "first_name" => $first_name,
      "last_name" => $last_name,
      "phone_number" => $phone,
      "tx_ref" => $tx_ref,
      "callback_url" => "https://webhook.site/077164d6-29cb-40df-ba29-8a00e59a7e60",
      "return_url" => "https://www.google.com/",
      "customization" => array(
        "title" => "Payment",
        "description" => "I love online payments."
      ),
      "meta" => array(
        "hide_receipt" => "true"
      )
    )),
    CURLOPT_HTTPHEADER => array(
      'Authorization: Bearer CHASECK_TEST-JJUndeBmPmz3oeBzaHlfciwH4UWaVsd1',
      'Content-Type: application/json'
    ),
  ));

  $response = curl_exec($curl);
  curl_close($curl);
  $data = json_decode($response, true);

  $riderct_url = $data['data']['checkout_url'];


   header('location:'.$riderct_url);
}
?>