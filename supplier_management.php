<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "DBconnect.php";
$raw_employee=$_SESSION['Employee_id']??$_SESSION['employee_id']??$_SESSION['user_id']??null;
$employee_id=(int)preg_replace('/[^0-9]/','',(string)$raw_employee);
if($employee_id<=0){header("Location: login.php");exit;}
$message="";$error="";

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $action=$_POST["action"]??"";
    $sid=(int)($_POST["supplier_id"]??0);
    $name=trim($_POST["supplier_name"]??"");$email=trim($_POST["email"]??"");
    $phone=trim($_POST["phone"]??"");$address=trim($_POST["address"]??"");

    if($action==="add"||$action==="update"){
        if($name==="") $error="Supplier name is required.";
        else{
            $name_e=mysqli_real_escape_string($conn,$name);$email_e=mysqli_real_escape_string($conn,$email);
            $phone_e=mysqli_real_escape_string($conn,$phone);$address_e=mysqli_real_escape_string($conn,$address);
            if($action==="add"){
                $dup=mysqli_query($conn,"SELECT Supplier_ID FROM supplier WHERE LOWER(Supplier_name)=LOWER('$name_e') LIMIT 1");
                if($dup&&mysqli_num_rows($dup)>0)$error="A supplier with this name already exists.";
                else{
                    $q=mysqli_query($conn,"SELECT COALESCE(MAX(Supplier_ID),0)+1 AS next_id FROM supplier");
                    $next=(int)mysqli_fetch_assoc($q)["next_id"];
                    if(mysqli_query($conn,"INSERT INTO supplier(Supplier_ID,Supplier_name,Email,Address,Phone) VALUES($next,'$name_e','$email_e','$address_e','$phone_e')")){
                        if(function_exists("logEmployeeAction"))logEmployeeAction($conn,"SUPPLIER_ADD","Added supplier #$next - $name",$next,$employee_id);
                        $message="Supplier #$next added successfully.";
                    }else $error="Could not add supplier: ".mysqli_error($conn);
                }
            }else{
                if(mysqli_query($conn,"UPDATE supplier SET Supplier_name='$name_e',Email='$email_e',Address='$address_e',Phone='$phone_e' WHERE Supplier_ID=$sid")){
                    if(function_exists("logEmployeeAction"))logEmployeeAction($conn,"SUPPLIER_UPDATE","Updated supplier #$sid",$sid,$employee_id);
                    $message="Supplier updated successfully.";
                }else $error="Could not update supplier: ".mysqli_error($conn);
            }
        }
    }
}
if(isset($_GET["delete"])){
    $sid=(int)$_GET["delete"];
    $used=mysqli_query($conn,"SELECT COUNT(*) AS total FROM purchase WHERE Supplier_ID=$sid");
    $used_count=$used?(int)mysqli_fetch_assoc($used)["total"]:0;
    if($used_count>0)$error="This supplier cannot be deleted because purchase history exists.";
    elseif(mysqli_query($conn,"DELETE FROM supplier WHERE Supplier_ID=$sid")){
        if(function_exists("logEmployeeAction"))logEmployeeAction($conn,"SUPPLIER_DELETE","Deleted supplier #$sid",$sid,$employee_id);
        $message="Supplier deleted successfully.";
    }else $error="Could not delete supplier: ".mysqli_error($conn);
}
$suppliers=mysqli_query($conn,"SELECT * FROM supplier ORDER BY Supplier_ID ASC");
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Supplier Management - Plant Hub</title>
<style>
body{font-family:Segoe UI,sans-serif;background:#f0fdf4;margin:0;color:#1e293b}.container{max-width:1100px;margin:30px auto;padding:0 20px}.card{background:#fff;padding:25px;border-radius:14px;margin-bottom:25px;border:1px solid #e2e8f0}h2{color:#065f46}.back{display:inline-block;margin-bottom:20px;color:#0284c7;text-decoration:none;font-weight:700}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}label{font-weight:700;font-size:14px;display:block;margin-bottom:6px}input{width:100%;box-sizing:border-box;padding:10px;border:1px solid #cbd5e1;border-radius:7px}.full{grid-column:1/-1}button{background:#10b981;color:#fff;border:0;padding:9px 14px;border-radius:6px;font-weight:700;cursor:pointer}.danger{background:#ef4444;color:#fff;padding:8px 11px;border-radius:6px;text-decoration:none;font-size:13px}.success{background:#dcfce7;color:#166534;padding:12px;border-radius:8px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#f8fafc}@media(max-width:700px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head><body>
<?php if(file_exists("header.php")) include "header.php"; ?>
<div class="container"><a class="back" href="employee_dashboard.php">← Back to Employee Dashboard</a>
<div class="card"><h2>🚚 Supplier Management</h2>
<?php if($message):?><div class="success"><?=htmlspecialchars($message)?></div><?php endif;?>
<?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
<h3>Add Supplier</h3><form method="post"><input type="hidden" name="action" value="add"><div class="grid">
<div><label>Supplier Name</label><input name="supplier_name" required></div><div><label>Email</label><input type="email" name="email"></div>
<div><label>Phone</label><input name="phone"></div><div><label>Address</label><input name="address"></div>
<div class="full"><button>➕ Add Supplier</button></div></div></form></div>
<div class="card"><h2>📋 Supplier List</h2>
<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr>
<?php while($s=mysqli_fetch_assoc($suppliers)): ?><tr><form method="post"><input type="hidden" name="action" value="update"><input type="hidden" name="supplier_id" value="<?=$s["Supplier_ID"]?>">
<td>#<?=$s["Supplier_ID"]?></td><td><input name="supplier_name" value="<?=htmlspecialchars($s["Supplier_name"]??"")?>" required></td>
<td><input type="email" name="email" value="<?=htmlspecialchars($s["Email"]??"")?>"></td><td><input name="phone" value="<?=htmlspecialchars($s["Phone"]??"")?>"></td>
<td><input name="address" value="<?=htmlspecialchars($s["Address"]??"")?>"></td><td><button>Save</button> <a class="danger" href="?delete=<?=$s["Supplier_ID"]?>" onclick="return confirm('Delete this supplier?')">Delete</a></td></form></tr>
<?php endwhile; ?></table></div></div></body></html>
