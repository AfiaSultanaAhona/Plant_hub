<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "DBconnect.php";

$raw_customer=$_SESSION['customer_id']??$_SESSION['user_id']??$_SESSION['Customer_ID']??null;
$customer_id=(int)preg_replace('/[^0-9]/','',(string)$raw_customer);
if($customer_id<=0){header("Location: login.php");exit;}

$message="";$error="";

if($_SERVER["REQUEST_METHOD"]==="POST"&&isset($_POST["submit_exchange"])){
    $offered=(int)($_POST["offered_plant_id"]??-1);
    $received=(int)($_POST["received_plant_id"]??-1);

    if($offered<0||$received<0||$offered===$received)$error="Please select two different plants.";
    else{
        $owned_q=mysqli_query($conn,"SELECT 1 FROM orders WHERE Customer_id=$customer_id AND Plant_id=$offered AND Amount>0 LIMIT 1");
        $received_q=mysqli_query($conn,"SELECT Plant_ID,Plant_name,Unit_price,Stock_quantity FROM plant WHERE Plant_ID=$received LIMIT 1");
        $offered_q=mysqli_query($conn,"SELECT Plant_ID,Plant_name,Unit_price FROM plant WHERE Plant_ID=$offered LIMIT 1");

        if(!$owned_q||mysqli_num_rows($owned_q)===0)$error="You can only offer a plant that you previously purchased.";
        elseif(!$offered_q||mysqli_num_rows($offered_q)===0||!$received_q||mysqli_num_rows($received_q)===0)$error="Selected plant could not be found.";
        else{
            $op=mysqli_fetch_assoc($offered_q);$rp=mysqli_fetch_assoc($received_q);
            if((int)$rp["Stock_quantity"]<=0)$error="The requested plant is currently out of stock.";
            else{
                $difference=round((float)$rp["Unit_price"]-(float)$op["Unit_price"],2);
                $method="N/A";$payment_status="Pending";
                if($difference>0){$method="Cash";$payment_status="Customer pays ৳".number_format($difference,2);}
                elseif($difference<0){$method="Store Wallet Credit";$payment_status="Store refunds ৳".number_format(abs($difference),2)." after approval";}
                else $payment_status="No cash adjustment";

                $direction=$difference>0?"Customer Pays":($difference<0?"Store Pays":"No Adjustment");
                $notes="Customer requests ".$op["Plant_name"]." → ".$rp["Plant_name"];
                $sql="INSERT INTO exchange
                (Exchange_date,Exchange_value,Received_plant_ID,Customer_ID,Offered_plant_ID,status,payment_method,payment_status,adjustment_direction,notes)
                VALUES(CURDATE(),$difference,$received,$customer_id,$offered,'Pending',
                '".mysqli_real_escape_string($conn,$method)."',
                '".mysqli_real_escape_string($conn,$payment_status)."',
                '".mysqli_real_escape_string($conn,$direction)."',
                '".mysqli_real_escape_string($conn,$notes)."')";
                if(mysqli_query($conn,$sql))$message="Exchange request submitted successfully. An employee must process it before inventory or wallet changes occur.";
                else $error="Could not submit exchange: ".mysqli_error($conn);
            }
        }
    }
}

$owned=mysqli_query($conn,"SELECT DISTINCT p.Plant_ID,p.Plant_name,p.Unit_price FROM plant p INNER JOIN orders o ON o.Plant_id=p.Plant_ID WHERE o.Customer_id=$customer_id AND o.Amount>0 ORDER BY p.Plant_name");
$available=mysqli_query($conn,"SELECT Plant_ID,Plant_name,Unit_price,Stock_quantity FROM plant WHERE Stock_quantity>0 ORDER BY Plant_name");
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Plant Exchange - Plant Hub</title>
<style>
body{font-family:Segoe UI,sans-serif;background:#eef7f2;margin:0;color:#1e293b}.box{max-width:550px;margin:40px auto;background:#fff;padding:28px;border-radius:14px;box-shadow:0 4px 12px rgba(0,0,0,.05)}h2{color:#065f46}label{font-weight:700;display:block;margin-top:15px}select,button{width:100%;box-sizing:border-box;padding:11px;margin-top:7px;border-radius:7px}select{border:1px solid #cbd5e1}button{background:#10b981;color:#fff;border:0;font-weight:700;cursor:pointer}.success{background:#dcfce7;color:#166534;padding:12px;border-radius:8px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px}.back{color:#0284c7;text-decoration:none;font-weight:700}
</style></head><body>
<?php if(file_exists("header.php")) include "header.php"; ?>
<div class="box"><a class="back" href="shop.php">← Back to Shop</a><h2>🔄 Request Plant Exchange</h2>
<?php if($message):?><p class="success"><?=htmlspecialchars($message)?></p><?php endif;?>
<?php if($error):?><p class="error"><?=htmlspecialchars($error)?></p><?php endif;?>
<form method="post"><label>Current Plant You Own</label><select name="offered_plant_id" required><option value="">Select your plant</option>
<?php if($owned):while($p=mysqli_fetch_assoc($owned)):?><option value="<?=$p["Plant_ID"]?>"><?=htmlspecialchars($p["Plant_name"])?> — ৳<?=number_format((float)$p["Unit_price"],2)?></option><?php endwhile;endif;?></select>
<label>Plant You Want to Receive</label><select name="received_plant_id" required><option value="">Select target plant</option>
<?php if($available):while($p=mysqli_fetch_assoc($available)):?><option value="<?=$p["Plant_ID"]?>"><?=htmlspecialchars($p["Plant_name"])?> — ৳<?=number_format((float)$p["Unit_price"],2)?> (Stock: <?=$p["Stock_quantity"]?>)</option><?php endwhile;endif;?></select>
<button name="submit_exchange">Submit Exchange Request</button></form></div></body></html>
