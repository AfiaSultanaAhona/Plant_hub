<?php
if(session_status()===PHP_SESSION_NONE)session_start();
require_once "DBconnect.php";
$raw=$_SESSION['customer_id']??$_SESSION['Customer_ID']??$_SESSION['Customer_id']??$_SESSION['user_id']??null;
$customer_id=(int)preg_replace('/[^0-9]/','',(string)$raw);
if($customer_id<=0){header("Location: login.php");exit;}
$message=$error="";

/* Customer completes ONLY an approved exchange. */
if($_SERVER["REQUEST_METHOD"]==="GET"&&($_GET["action"]??"")==="complete"){
 $id=(int)($_GET["exchange_id"]??0); mysqli_begin_transaction($conn);
 try{
  $q=mysqli_query($conn,"SELECT * FROM exchange WHERE exchange_id=$id AND Customer_ID=$customer_id FOR UPDATE");
  if(!$q||mysqli_num_rows($q)===0)throw new Exception("Exchange request not found.");
  $e=mysqli_fetch_assoc($q);
  if(strcasecmp($e["status"],"Approved")!==0)throw new Exception("This exchange must be approved by an employee first.");
  $old=(int)$e["Offered_plant_ID"]; $new=(int)$e["Received_plant_ID"]; $order=(int)($e["Order_ID"]??0);
  $q=mysqli_query($conn,"SELECT Plant_ID,Plant_name,Unit_price,Stock_quantity FROM plant WHERE Plant_ID IN($old,$new) FOR UPDATE");
  $p=[];while($r=mysqli_fetch_assoc($q))$p[(int)$r["Plant_ID"]]=$r;
  if(!isset($p[$old])||!isset($p[$new]))throw new Exception("One of the plants no longer exists.");
  if((int)$p[$new]["Stock_quantity"]<=0)throw new Exception("The requested plant is currently out of stock.");
  $d=round((float)$p[$new]["Unit_price"]-(float)$p[$old]["Unit_price"],2);
  if($d>0){$method="Cash on Delivery";$pay="COD due ৳".number_format($d,2);$dir="Customer Pays";}
  elseif($d<0){
   $refund=abs($d);
   $w=mysqli_query($conn,"UPDATE customer SET wallet_balance=COALESCE(wallet_balance,0)+$refund WHERE Customer_ID=$customer_id");
   if(!$w||mysqli_affected_rows($conn)!==1)throw new Exception("Could not credit the store wallet.");
   $method="Store Wallet Credit";$pay="Refunded ৳".number_format($refund,2)." to store wallet";$dir="Store Refunds";
  }else{$method="N/A";$pay="No price adjustment";$dir="No Adjustment";}
  if(!mysqli_query($conn,"UPDATE plant SET Stock_quantity=Stock_quantity+1 WHERE Plant_ID=$old")||mysqli_affected_rows($conn)!==1)throw new Exception("Could not return the old plant.");
  if(!mysqli_query($conn,"UPDATE plant SET Stock_quantity=Stock_quantity-1 WHERE Plant_ID=$new AND Stock_quantity>0")||mysqli_affected_rows($conn)!==1)throw new Exception("Could not remove the requested plant.");
  $m=mysqli_real_escape_string($conn,$method);$ps=mysqli_real_escape_string($conn,$pay);$di=mysqli_real_escape_string($conn,$dir);
  $note=mysqli_real_escape_string($conn,"Completed exchange: returned ".$p[$old]["Plant_name"]." and received ".$p[$new]["Plant_name"]);
  if(!mysqli_query($conn,"UPDATE exchange SET Exchange_value=$d,status='Completed',payment_method='$m',payment_status='$ps',adjustment_direction='$di',notes='$note' WHERE exchange_id=$id AND Customer_ID=$customer_id"))throw new Exception("Could not complete the exchange.");
  if($order>0)mysqli_query($conn,"UPDATE orders SET Exchange_status='Completed' WHERE Order_id=$order AND Customer_id=$customer_id");
  mysqli_commit($conn);$message="Exchange #$id completed successfully.";
  if($d>0)$message.=" Extra ৳".number_format($d,2)." is payable by COD.";
  elseif($d<0)$message.=" ৳".number_format(abs($d),2)." was added to your store wallet.";
 }catch(Throwable $x){mysqli_rollback($conn);$error=$x->getMessage();}
}

/* Customer submits request; NO stock/wallet change here. */
if($_SERVER["REQUEST_METHOD"]==="POST"&&isset($_POST["submit_exchange"])){
 $order=(int)($_POST["order_id"]??0);$old=(int)($_POST["offered_plant_id"]??0);$new=(int)($_POST["received_plant_id"]??0);
 if($order<=0||$old<=0||$new<=0)$error="Invalid exchange request.";
 elseif($old===$new)$error="Please select a different plant to receive.";
 else{
  $oq=mysqli_query($conn,"SELECT Order_id FROM orders WHERE Order_id=$order AND Customer_id=$customer_id AND Plant_id=$old AND Amount>0 LIMIT 1");
  $opq=mysqli_query($conn,"SELECT Plant_ID,Plant_name,Unit_price FROM plant WHERE Plant_ID=$old LIMIT 1");
  $npq=mysqli_query($conn,"SELECT Plant_ID,Plant_name,Unit_price,Stock_quantity FROM plant WHERE Plant_ID=$new LIMIT 1");
  if(!$oq||mysqli_num_rows($oq)===0)$error="You can only exchange a plant from your own purchase history.";
  elseif(!$opq||!$npq||mysqli_num_rows($opq)===0||mysqli_num_rows($npq)===0)$error="Selected plant could not be found.";
  else{$op=mysqli_fetch_assoc($opq);$np=mysqli_fetch_assoc($npq);
   if((int)$np["Stock_quantity"]<=0)$error="The requested plant is currently out of stock.";
   else{$a=mysqli_query($conn,"SELECT exchange_id FROM exchange WHERE Order_ID=$order AND Customer_ID=$customer_id AND status IN('Pending','Approved') LIMIT 1");
    if($a&&mysqli_num_rows($a)>0)$error="This order already has an active exchange request.";
    else{$d=round((float)$np["Unit_price"]-(float)$op["Unit_price"],2);
     if($d>0){$method="Cash on Delivery";$pay="COD due ৳".number_format($d,2);$dir="Customer Pays";}
     elseif($d<0){$method="Store Wallet Credit";$pay="Refund ৳".number_format(abs($d),2)." to store wallet after completion";$dir="Store Refunds";}
     else{$method="N/A";$pay="No price adjustment";$dir="No Adjustment";}
     $n=mysqli_real_escape_string($conn,"Customer requested exchange: ".$op["Plant_name"]." → ".$np["Plant_name"]);
     $method=mysqli_real_escape_string($conn,$method);$pay=mysqli_real_escape_string($conn,$pay);$dir=mysqli_real_escape_string($conn,$dir);
     $s="INSERT INTO exchange(Exchange_date,Exchange_value,Received_plant_ID,Customer_ID,Employee_ID,Offered_plant_ID,Order_ID,status,payment_method,payment_status,adjustment_direction,notes) VALUES(CURDATE(),$d,$new,$customer_id,NULL,$old,$order,'Pending','$method','$pay','$dir','$n')";
     if(mysqli_query($conn,$s)){mysqli_query($conn,"UPDATE orders SET Exchange_status='Pending' WHERE Order_id=$order AND Customer_id=$customer_id");$message="Exchange request submitted. Please wait for employee approval.";}else $error=mysqli_error($conn);
    }
   }
  }
 }
 }
}
$order=(int)($_GET["order_id"]??0);$old=(int)($_GET["offered_plant_id"]??0);
$owned=$order&&$old?mysqli_query($conn,"SELECT p.Plant_ID,p.Plant_name,p.Unit_price,o.Order_id FROM orders o JOIN plant p ON p.Plant_ID=o.Plant_id WHERE o.Order_id=$order AND o.Customer_id=$customer_id AND o.Plant_id=$old AND o.Amount>0 LIMIT 1"):mysqli_query($conn,"SELECT DISTINCT p.Plant_ID,p.Plant_name,p.Unit_price,o.Order_id FROM orders o JOIN plant p ON p.Plant_ID=o.Plant_id WHERE o.Customer_id=$customer_id AND o.Amount>0 ORDER BY o.Order_id DESC");
$available=mysqli_query($conn,"SELECT Plant_ID,Plant_name,Unit_price,Stock_quantity FROM plant WHERE Stock_quantity>0 ORDER BY Plant_name");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Plant Exchange - Plant Hub</title><style>
body{font-family:Segoe UI,sans-serif;background:#eef7f2;margin:0;color:#1e293b}.box{max-width:600px;margin:40px auto;background:#fff;padding:28px;border-radius:14px}.back{color:#0284c7;font-weight:700;text-decoration:none}.success{background:#dcfce7;color:#166534;padding:12px;border-radius:8px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px}.info{background:#eff6ff;color:#1e40af;padding:12px;border-radius:8px;margin:15px 0}label{display:block;font-weight:700;margin-top:15px}select,button{width:100%;box-sizing:border-box;padding:11px;margin-top:7px;border-radius:7px}button{background:#10b981;color:#fff;border:0;font-weight:700}</style></head><body>
<?php if(file_exists("header.php"))include"header.php";?><div class="box"><a class="back" href="my_orders.php">← Back to My Orders</a><h2>🔄 Request Plant Exchange</h2>
<?php if($message):?><div class="success"><?=htmlspecialchars($message)?></div><?php endif;?><?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="info"><b>Process:</b> Request → Employee Approval → Customer Completes.<br>Higher price: extra amount is paid by COD. Lower price: refund goes to store wallet.</div>
<form method="post"><input type="hidden" name="order_id" value="<?=$order?>">
<label>Current Plant</label><select name="offered_plant_id" required><option value="">Select purchased plant</option><?php if($owned)while($p=mysqli_fetch_assoc($owned)):?><option value="<?=$p["Plant_ID"]?>" <?=((int)$p["Plant_ID"]===$old?"selected":"")?>><?=htmlspecialchars($p["Plant_name"])?> — ৳<?=number_format((float)$p["Unit_price"],2)?> (Order #<?=$p["Order_id"]?>)</option><?php endwhile;?></select>
<label>Plant to Receive</label><select name="received_plant_id" required><option value="">Select target plant</option><?php if($available)while($p=mysqli_fetch_assoc($available)):?><option value="<?=$p["Plant_ID"]?>"><?=htmlspecialchars($p["Plant_name"])?> — ৳<?=number_format((float)$p["Unit_price"],2)?> (Stock: <?=$p["Stock_quantity"]?>)</option><?php endwhile;?></select>
<button name="submit_exchange">Submit Exchange Request</button></form></div></body></html>