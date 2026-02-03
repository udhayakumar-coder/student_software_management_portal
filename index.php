<?php
session_start();
ob_start();
$conn = mysqli_connect("localhost","root","","student_management");
if(!$conn) die("DB Error: ".mysqli_connect_error());

/* ================= AUTH ================= */
if(isset($_POST['register'])){
    $pass=password_hash($_POST['password'],PASSWORD_DEFAULT);
    mysqli_query($conn,"INSERT INTO users(name,email,password,standard) VALUES('$_POST[name]','$_POST[email]','$pass','$_POST[standard]')") or die(mysqli_error($conn));
}
if(isset($_POST['login'])){
    $q=mysqli_query($conn,"SELECT * FROM users WHERE email='$_POST[email]'") or die(mysqli_error($conn));
    $u=mysqli_fetch_assoc($q);
    if($u && password_verify($_POST['password'],$u['password'])){
        session_regenerate_id(true);
        $_SESSION['uid']=$u['id'];
        $_SESSION['name']=$u['name'];
    }
}
if(isset($_GET['logout'])){
    session_destroy();
    header("Location:index.php");
    exit;
}

/* ================= PROFILE ================= */
if(isset($_POST['save_profile'])){
    mysqli_query($conn,"UPDATE users SET
    name='$_POST[name]',
    email='$_POST[email]',
    mobile='$_POST[mobile]',
    city='$_POST[city]',
    occupation='$_POST[occupation]'
    WHERE id=$_SESSION[uid]") or die(mysqli_error($conn));
}

/* CHANGE PASSWORD */
if(isset($_POST['change_pass'])){
    $res=mysqli_query($conn,"SELECT password FROM users WHERE id=$_SESSION[uid]");
    $u=mysqli_fetch_assoc($res);
    if(password_verify($_POST['old'],$u['password'])){
        $new=password_hash($_POST['new'],PASSWORD_DEFAULT);
        mysqli_query($conn,"UPDATE users SET password='$new' WHERE id=$_SESSION[uid]") or die(mysqli_error($conn));
    }
}

/* ================= TODO ================= */
if(isset($_POST['add_todo'])){
    mysqli_query($conn,"INSERT INTO todos(user_id,task,priority,due_date,status) VALUES($_SESSION[uid],'$_POST[task]','$_POST[priority]','$_POST[due_date]','Pending')") or die(mysqli_error($conn));
}
if(isset($_GET['done_todo'])) mysqli_query($conn,"UPDATE todos SET status='Completed' WHERE id=$_GET[done_todo] AND user_id=$_SESSION[uid]");
if(isset($_GET['del_todo'])) mysqli_query($conn,"UPDATE todos SET is_deleted=1 WHERE id=$_GET[del_todo] AND user_id=$_SESSION[uid]");
if(isset($_GET['restore_todo'])) mysqli_query($conn,"UPDATE todos SET is_deleted=0 WHERE id=$_GET[restore_todo] AND user_id=$_SESSION[uid]");

/* ================= EXPENSE ================= */
if(isset($_POST['add_expense'])){
    if($_POST['amount']>0){
        mysqli_query($conn,"INSERT INTO expenses(user_id,expense_date,category,amount,payment_method,description) VALUES($_SESSION[uid],'$_POST[expense_date]','$_POST[category]','$_POST[amount]','$_POST[payment]','$_POST[description]')") or die(mysqli_error($conn));
    }
}
if(isset($_GET['del_expense'])) mysqli_query($conn,"UPDATE expenses SET is_deleted=1 WHERE id=$_GET[del_expense] AND user_id=$_SESSION[uid]");
if(isset($_GET['restore_expense'])) mysqli_query($conn,"UPDATE expenses SET is_deleted=0 WHERE id=$_GET[restore_expense] AND user_id=$_SESSION[uid]");

/* ================= DOCUMENT ================= */
if(isset($_POST['upload_doc'])){
    $f=$_FILES['doc']['name'];
    if($f!=""){
        $new=time()."_".$f;
        move_uploaded_file($_FILES['doc']['tmp_name'],"uploads/".$new);
        mysqli_query($conn,"INSERT INTO documents(user_id,file_name) VALUES($_SESSION[uid],'$new')") or die(mysqli_error($conn));
    }
}
if(isset($_GET['del_doc'])) mysqli_query($conn,"UPDATE documents SET is_deleted=1 WHERE id=$_GET[del_doc] AND user_id=$_SESSION[uid]");
if(isset($_GET['restore_doc'])) mysqli_query($conn,"UPDATE documents SET is_deleted=0 WHERE id=$_GET[restore_doc] AND user_id=$_SESSION[uid]");

/* ================= REMINDER ================= */
if(isset($_POST['add_reminder'])){
    mysqli_query($conn,"INSERT INTO reminders(user_id,message,remind_date) VALUES($_SESSION[uid],'$_POST[msg]','$_POST[date]')") or die(mysqli_error($conn));
}
// Delete reminder
if(isset($_GET['del_reminder'])){
    $id = (int)$_GET['del_reminder'];
    mysqli_query($conn, "DELETE FROM reminders WHERE id=$id AND user_id=".$_SESSION['uid']);
    // Redirect to avoid resubmission
    header("Location: ?page=reminder");
    exit;
}

/* ================= EMERGENCY CONTACT ================= */
if(isset($_POST['add_contact'])){
    mysqli_query($conn,"INSERT INTO emergency_contacts(user_id,name,relation,phone) VALUES($_SESSION[uid],'$_POST[name]','$_POST[relation]','$_POST[phone]')") or die(mysqli_error($conn));
}
if(isset($_GET['del_contact'])) mysqli_query($conn,"UPDATE emergency_contacts SET is_deleted=1 WHERE id=$_GET[del_contact] AND user_id=$_SESSION[uid]");
if(isset($_GET['restore_contact'])) mysqli_query($conn,"UPDATE emergency_contacts SET is_deleted=0 WHERE id=$_GET[restore_contact] AND user_id=$_SESSION[uid]");

/* ================= PAGE ================= */
$page=$_GET['page']??'dashboard';
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Personal Management</title>
<style>
*   {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f6f9;
    color: #333;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 240px;
    height: 100vh;
    position: fixed;
    background: linear-gradient(180deg, #1e1e2f, #2b2b45);
    padding-top: 20px;
}

.sidebar h3 {
    text-align: center;
    color: #fff;
    margin-bottom: 25px;
}

.sidebar a {
    display: block;
    padding: 12px 20px;
    color: #ddd;
    text-decoration: none;
    font-size: 15px;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.12);
    color: #fff;
}

/* ===== CONTENT ===== */
.content {
    margin-left: 260px;
    padding: 25px;
}

/* ===== HEADER ===== */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.profile-btn {
    background: #4f46e5;
    color: #fff;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
}

.profile-btn:hover {
    background: #4338ca;
}

/* ===== BOX ===== */
.box {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* ===== DASHBOARD CARDS ===== */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.card {
    background: linear-gradient(135deg, #ffffff, #f1f5f9);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.card h3 {
    margin: 0;
    font-size: 15px;
    color: #555;
}

.card p {
    margin-top: 10px;
    font-size: 30px;
    font-weight: bold;
    color: #111;
}

/* ===== FORMS ===== */
input, select, button {
    width: 100%;
    padding: 10px;
    margin: 6px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
}

button {
    background: #4f46e5;
    color: #fff;
    border: none;
    cursor: pointer;
}

button:hover {
    background: #4338ca;
}

/* ===== TABLES ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

table th, table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

table th {
    background: #f1f5f9;
    text-align: left;
}

table tr:hover {
    background: #f9fafb;
}

/* ===== LOGIN BOX ===== */
.auth-box {
    width: 380px;
    margin: 80px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    text-align: center;
}
/* ===== MOBILE HEADER ===== */
.mobile-header {
    display: none;
    align-items: center;
    gap: 15px;
    padding: 12px 15px;
    background: #1e1e2f;
    color: #fff;
    position: fixed;
    width: 100%;
    z-index: 1000;
}

.menu-btn {
    font-size: 22px;
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
}

.app-title {
    font-size: 16px;
    font-weight: bold;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {

    .mobile-header {
        display: flex;
    }

    .sidebar {
        position: fixed;
        left: -260px;
        top: 0;
        height: 100%;
        z-index: 999;
        transition: left 0.3s ease;
    }

    .sidebar.active {
        left: 0;
    }

    .content {
        margin-left: 0;
        padding-top: 70px;
    }
}

body{margin:0;font-family:Arial;background:#f2f2f2}
.sidebar{width:230px;height:100vh;position:fixed;background:#222;color:#fff}
.sidebar a{display:block;color:#fff;padding:10px;text-decoration:none}
.sidebar a:hover{background:#444}
.content{margin-left:240px;padding:20px}
.box{background:#fff;padding:15px;border-radius:5px;margin-bottom:15px}
.cards{display:flex;gap:15px;flex-wrap:wrap}
.card{flex:1;background:#eee;padding:15px;border-radius:5px;text-align:center}
input,select,button{width:100%;padding:8px;margin:5px 0}
table{width:100%;border-collapse:collapse}
table,th,td{border:1px solid #ccc;padding:5px;text-align:left}
th{background:#eee}
</style>
</head>
<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
}
</script>
<body>

<?php if(!isset($_SESSION['uid'])){ ?>
<div class="auth-box">
<h2>Register</h2>
<form method="post">
<input name="name" placeholder="Name" required>
<input name="email" placeholder="Email" required>
<input name="standard" placeholder="Standard" required>
<input type="password" name="password" placeholder="Password" required>
<button name="register">Register</button>
</form>
<hr>
<h2>Login</h2>
<form method="post">
<input name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>
<button name="login">Login</button>
</form>
</div>
<?php exit; } ?>

<div class="sidebar" id="sidebar">
    <h3>👋 <?php echo $_SESSION['name']; ?></h3>
    <a href="?page=dashboard">📊 Dashboard</a>
    <a href="?page=todo">📝 Todo</a>
    <a href="?page=expenses">💰 Expenses</a>
    <a href="?page=monthly_category_report">📅 Monthly Report</a>
    <a href="?page=documents">📁 Documents</a>
    <a href="?page=reminder">⏰ Reminder</a>
    <a href="?page=contacts">📞 Emergency Contacts</a>
    <a href="?page=recycle">♻️ Recycle Bin</a>
    <a href="?logout=1">🚪 Logout</a>
    <div class="mobile-header">
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
    <span class="app-title">Student Manager</span>
</div>

</div>
<div class="content">


<?php
/* ================= DASHBOARD ================= */

if ($page == "dashboard") {

    $uid = $_SESSION['uid'];

    // Pending Todos
    $todo_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM todos WHERE user_id=$uid AND status='Pending' AND is_deleted=0");
    $todo_row = mysqli_fetch_assoc($todo_res);
    $todo = $todo_row['cnt'];

    // Documents
    $doc_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM documents WHERE user_id=$uid AND is_deleted=0");
    $doc_row = mysqli_fetch_assoc($doc_res);
    $doc = $doc_row['cnt'];

    // Total Expenses
    $exp_res = mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) AS total FROM expenses WHERE user_id=$uid AND is_deleted=0");
    $exp_row = mysqli_fetch_assoc($exp_res);
    $exp = $exp_row['total'];

    // Today Reminders
    $today = date('Y-m-d');
    $rem_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM reminders WHERE user_id=$uid AND remind_date='$today'");
    $rem_row = mysqli_fetch_assoc($rem_res);
    $rem = $rem_row['cnt'];

    // Emergency Contacts
    $contact_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM emergency_contacts WHERE user_id=$uid AND is_deleted=0");
    $contact_row = mysqli_fetch_assoc($contact_res);
    $contacts = $contact_row['cnt'];
?>
    <div class="header">
    <h2>Dashboard</h2>
    <a href="?page=profile" class="profile-btn">Profile</a>
</div>

<div class="cards">
    <div class="card">
        <h3>📝Pending Todos</h3>
        <p><?php echo $todo; ?></p>
    </div>

    <div class="card">
        <h3>📁Documents</h3>
        <p><?php echo $doc; ?></p>
    </div>

    <div class="card">
        <h3>💰Total Expenses</h3>
        <p><?php echo $exp; ?></p>
    </div>

    <div class="card">
        <h3>⏰Today Reminders</h3>
        <p><?php echo $rem; ?></p>
    </div>

    <div class="card">
        <h3>📞Emergency Contacts</h3>
        <p><?php echo $contacts; ?></p>
    </div>
</div>

<?php
}


/* ================= PROFILE PAGE ================= */
if($page=="profile"){
$u=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$_SESSION[uid]"));
?>
<div class="box">
<h2>Profile</h2>
<form method="post">
<input name="name" value="<?php echo $u['name']; ?>" required>
<input name="email" value="<?php echo $u['email']; ?>" required>
<input name="mobile" value="<?php echo $u['mobile']; ?>">
<input name="city" value="<?php echo $u['city']; ?>">
<input name="occupation" value="<?php echo $u['occupation']; ?>">
<button name="save_profile">Save Profile</button>
</form>
<hr>
<h3>Change Password</h3>
<form method="post">
<input type="password" name="old" placeholder="Old Password" required>
<input type="password" name="new" placeholder="New Password" required>
<button name="change_pass">Change Password</button>
</form>
</div>
<?php
}

/* ================= TODO PAGE ================= */
if($page=="todo"){
?>
<div class="box">
<h2>Add Todo</h2>
<form method="post">
<input name="task" placeholder="Task" required>
<select name="priority"><option>Low</option><option>Medium</option><option>High</option></select>
<input type="date" name="due_date">
<button name="add_todo">Add</button>
</form>
<hr>
<h2>Todo List</h2>
<table>
<tr><th>Task</th><th>Priority</th><th>Due Date</th><th>Status</th><th>Actions</th></tr>
<?php
$q=mysqli_query($conn,"SELECT * FROM todos WHERE user_id=$_SESSION[uid] AND is_deleted=0 ORDER BY id DESC");
while($t=mysqli_fetch_assoc($q)){
    echo "<tr>";
    echo "<td>{$t['task']}</td>";
    echo "<td>{$t['priority']}</td>";
    echo "<td>{$t['due_date']}</td>";
    echo "<td>{$t['status']}</td>";
    echo "<td>";
    if($t['status']=="Pending") echo "<a href='?page=todo&done_todo={$t['id']}'>Complete</a> ";
    echo "<a href='?page=todo&del_todo={$t['id']}'>Delete</a>";
    echo "</td></tr>";
}
?>
</table>
</div>
<?php
}

/* ================= EXPENSE PAGE ================= */
if($page=="expenses"){
?>
<div class="box">
<h2>Add Expense</h2>
<form method="post">
<input type="date" name="expense_date" required>
<select name="category">
<option>Food</option><option>Travel</option><option>Rent</option><option>Medical</option><option>Education</option><option>Entertainment</option>
</select>
<input type="number" step="0.01" name="amount" placeholder="Amount" required>
<select name="payment"><option>Cash</option><option>UPI</option><option>Debit Card</option><option>Credit Card</option></select>
<input name="description" placeholder="Description">
<button name="add_expense">Add Expense</button>
</form>
<hr>
<h2>Expense List</h2>
<table>
<tr><th>Date</th><th>Category</th><th>Amount</th><th>Payment</th><th>Description</th><th>Actions</th></tr>
<?php
$e=mysqli_query($conn,"SELECT * FROM expenses WHERE user_id=$_SESSION[uid] AND is_deleted=0 ORDER BY expense_date DESC");
while($x=mysqli_fetch_assoc($e)){
    echo "<tr>";
    echo "<td>{$x['expense_date']}</td>";
    echo "<td>{$x['category']}</td>";
    echo "<td>{$x['amount']}</td>";
    echo "<td>{$x['payment_method']}</td>";
    echo "<td>{$x['description']}</td>";
    echo "<td><a href='?page=expenses&del_expense={$x['id']}'>Delete</a></td>";
    echo "</tr>";
}
?>
</table>
</div>
<?php
}

/* ========== CATEGORY-WISE MONTHLY EXPENSE REPORT ========== */
if($page=="monthly_category_report"){

    $month = $_GET['month'] ?? date('m');
    $year  = $_GET['year']  ?? date('Y');
?>
<div class="box">
<h2>Category-wise Monthly Expense Report</h2>

<form method="get">
<input type="hidden" name="page" value="monthly_category_report">

<select name="month">
<?php
for($m=1;$m<=12;$m++){
    $mn = date("F", mktime(0,0,0,$m,1));
    $sel = ($m==$month) ? "selected" : "";
    echo "<option value='$m' $sel>$mn</option>";
}
?>
</select>

<select name="year">
<?php
for($y=date('Y');$y>=2020;$y--){
    $sel = ($y==$year) ? "selected" : "";
    echo "<option $sel>$y</option>";
}
?>
</select>

<button>View</button>
</form>

<hr>

<table>
<tr>
<th>Category</th>
<th>Total Amount</th>
</tr>

<?php
$q = mysqli_query($conn,"
    SELECT category, SUM(amount) AS total
    FROM expenses
    WHERE user_id=$_SESSION[uid]
      AND is_deleted=0
      AND MONTH(expense_date)='$month'
      AND YEAR(expense_date)='$year'
    GROUP BY category
");

$grand = 0;
while($row = mysqli_fetch_assoc($q)){
    $grand += $row['total'];
    echo "<tr>
        <td>
            <a href='?page=monthly_category_details
            &month=$month
            &year=$year
            &category={$row['category']}'>
            {$row['category']}
            </a>
        </td>
        <td>{$row['total']}</td>
    </tr>";
}
?>

<tr>
<th>Total</th>
<th><?php echo $grand; ?></th>
</tr>

</table>
</div>
<?php
}

/* ========== MONTH + CATEGORY DRILL DOWN DETAILS ========== */
if($page=="monthly_category_details"){

    $month    = $_GET['month'];
    $year     = $_GET['year'];
    $category = $_GET['category'];
?>
<div class="box">
<h2>
<?php echo $category; ?> Expenses –
<?php echo date("F", mktime(0,0,0,$month,1)); ?> <?php echo $year; ?>
</h2>

<a href="?page=monthly_category_report&month=<?php echo $month; ?>&year=<?php echo $year; ?>">
Back to Category Report
</a>

<hr>

<table>
<tr>
<th>Date</th>
<th>Amount</th>
<th>Payment</th>
<th>Description</th>
</tr>

<?php
$q = mysqli_query($conn,"
    SELECT expense_date, amount, payment_method, description
    FROM expenses
    WHERE user_id=$_SESSION[uid]
      AND is_deleted=0
      AND category='$category'
      AND MONTH(expense_date)='$month'
      AND YEAR(expense_date)='$year'
    ORDER BY expense_date DESC
");

$total = 0;
while($e = mysqli_fetch_assoc($q)){
    $total += $e['amount'];
    echo "<tr>
        <td>{$e['expense_date']}</td>
        <td>{$e['amount']}</td>
        <td>{$e['payment_method']}</td>
        <td>{$e['description']}</td>
    </tr>";
}
?>

<tr>
<th>Total</th>
<th><?php echo $total; ?></th>
<th colspan="2"></th>
</tr>

</table>
</div>
<?php
}

/* ================= DOCUMENT PAGE ================= */
if($page=="documents"){
?>
<div class="box">
<h2>Upload Document</h2>
<form method="post" enctype="multipart/form-data">
<input type="file" name="doc" required>
<button name="upload_doc">Upload</button>
</form>
<hr>
<h2>Documents</h2>
<table>
<tr><th>File Name</th><th>Actions</th></tr>
<?php
$d=mysqli_query($conn,"SELECT * FROM documents WHERE user_id=$_SESSION[uid] AND is_deleted=0 ORDER BY id DESC");
while($doc=mysqli_fetch_assoc($d)){
    echo "<tr><td>{$doc['file_name']}</td><td><a href='?page=documents&del_doc={$doc['id']}'>Delete</a></td></tr>";
}
?>
</table>
</div>
<?php
}

/* ================= REMINDER PAGE ================= */
if($page=="reminder"){
?>
<div class="box">
<h2>Add Reminder</h2>
<form method="post">
    <input name="msg" placeholder="Message" required>
    <input type="date" name="date" required>
    <button name="add_reminder">Add Reminder</button>
</form>
<hr>
<h2>Reminders</h2>
<table border="1" cellpadding="5">
<tr>
    <th>Message</th>
    <th>Date</th>
    <th>Actions</th>
</tr>
<?php
$r=mysqli_query($conn,"SELECT * FROM reminders WHERE user_id=$_SESSION[uid] ORDER BY remind_date DESC");
while($rem=mysqli_fetch_assoc($r)){
    echo "<tr>";
    echo "<td>".htmlspecialchars($rem['message'])."</td>";
    echo "<td>".htmlspecialchars($rem['remind_date'])."</td>";
    echo "<td>
            <a href='?page=reminder&del_reminder={$rem['id']}' 
               onclick=\"return confirm('Delete this reminder?')\">Delete</a>
          </td>";
    echo "</tr>";
}
?>
</table>
</div>
<?php
}


/* ================= EMERGENCY CONTACTS PAGE ================= */


if ($page == "contacts") {

    $uid = (int)$_SESSION['uid'];

    // ---------- Handle Add Contact ----------
    if (isset($_POST['add_contact'])) {

        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $relation = mysqli_real_escape_string($conn, $_POST['relation']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);

        $insert = mysqli_query($conn, "
            INSERT INTO emergency_contacts (user_id, name, relation, phone)
            VALUES ($uid, '$name', '$relation', '$phone')
        ");

        if (!$insert) {
            die("Insert Error: " . mysqli_error($conn));
        }

        // Prevent resubmission
        header("Location: ?page=contacts");
        exit;
    }

    // ---------- Handle Delete Contact ----------
    if (isset($_GET['del_contact'])) {
        $id = (int)$_GET['del_contact'];

        mysqli_query($conn, "
            UPDATE emergency_contacts 
            SET is_deleted = 1 
            WHERE id = $id AND user_id = $uid
        ");

        header("Location: ?page=contacts");
        exit;
    }

    // ---------- Fetch Contacts ----------
    $c = mysqli_query($conn, "
        SELECT * 
        FROM emergency_contacts 
        WHERE user_id = $uid AND is_deleted = 0 
        ORDER BY id DESC
    ");
?>
<div class="box">
    <h2>Add Emergency Contact</h2>
    <form method="post">
        <input name="name" placeholder="Name" required>
        <input name="relation" placeholder="Relation" required>
        <input name="phone" placeholder="Phone" required>
        <button name="add_contact">Add Contact</button>
    </form>

    <hr>

    <h2>Emergency Contacts</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Name</th>
            <th>Relation</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
<?php
    if ($c) {
        if (mysqli_num_rows($c) == 0) {
            echo "<tr><td colspan='4'>No contacts found</td></tr>";
        } else {
            while ($con = mysqli_fetch_assoc($c)) {
                echo "
                <tr>
                    <td>{$con['name']}</td>
                    <td>{$con['relation']}</td>
                    <td>{$con['phone']}</td>
                    <td>
                        <a href='?page=contacts&del_contact={$con['id']}' 
                           onclick=\"return confirm('Delete this contact?')\">Delete</a>
                    </td>
                </tr>";
            }
        }
    } else {
        echo "<tr><td colspan='4'>Query Error: " . mysqli_error($conn) . "</td></tr>";
    }
?>
    </table>
</div>
<?php
}


/* ================= RECYCLE BIN PAGE ================= */

if ($page === "recycle") {

    if (!isset($_SESSION['uid'])) {
        die("Unauthorized access");
    }

    $uid = (int)$_SESSION['uid'];

    // ---------- HANDLE RESTORE ----------
    if (isset($_GET['restore'])) {

        $data = explode("-", $_GET['restore']);
        if (count($data) === 2) {
            $table = $data[0];
            $id    = (int)$data[1];

            switch ($table) {
                case 'todos':
                    mysqli_query($conn, "UPDATE todos SET is_deleted=0 WHERE id=$id AND user_id=$uid");
                    break;

                case 'expenses':
                    mysqli_query($conn, "UPDATE expenses SET is_deleted=0 WHERE id=$id AND user_id=$uid");
                    break;

                case 'documents':
                    mysqli_query($conn, "UPDATE documents SET is_deleted=0 WHERE id=$id AND user_id=$uid");
                    break;

                case 'contacts':
                    mysqli_query($conn, "UPDATE emergency_contacts SET is_deleted=0 WHERE id=$id AND user_id=$uid");
                    break;
            }
        }

        header("Location: ?page=recycle");
        exit;
    }

    // ---------- HANDLE PERMANENT DELETE ----------
    if (isset($_GET['permanent_delete'])) {

        $data = explode("-", $_GET['permanent_delete']);
        if (count($data) === 2) {
            $table = $data[0];
            $id    = (int)$data[1];

            switch ($table) {
                case 'todos':
                    mysqli_query($conn, "DELETE FROM todos WHERE id=$id AND user_id=$uid");
                    break;

                case 'expenses':
                    mysqli_query($conn, "DELETE FROM expenses WHERE id=$id AND user_id=$uid");
                    break;

                case 'documents':
                    mysqli_query($conn, "DELETE FROM documents WHERE id=$id AND user_id=$uid");
                    break;

                case 'contacts':
                    mysqli_query($conn, "DELETE FROM emergency_contacts WHERE id=$id AND user_id=$uid");
                    break;
            }
        }

        header("Location: ?page=recycle");
        exit;
    }

    // ---------- FETCH DELETED DATA ----------
    $todos     = mysqli_query($conn, "SELECT * FROM todos WHERE user_id=$uid AND is_deleted=1 ORDER BY id DESC");
    $expenses  = mysqli_query($conn, "SELECT * FROM expenses WHERE user_id=$uid AND is_deleted=1 ORDER BY id DESC");
    $documents = mysqli_query($conn, "SELECT * FROM documents WHERE user_id=$uid AND is_deleted=1 ORDER BY id DESC");
    $contacts  = mysqli_query($conn, "SELECT * FROM emergency_contacts WHERE user_id=$uid AND is_deleted=1 ORDER BY id DESC");
?>
    <h1>Recycle Bin</h1>

    <!-- DELETED TODOS -->
    <h2>Deleted Todos</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Task</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if ($todos && mysqli_num_rows($todos) > 0) {
            while ($t = mysqli_fetch_assoc($todos)) { ?>
                <tr>
                    <td><?= htmlspecialchars($t['task']) ?></td>
                    <td><?= htmlspecialchars($t['status']) ?></td>
                    <td>
                        <a href="?page=recycle&restore=todos-<?= $t['id'] ?>">Restore</a> |
                        <a href="?page=recycle&permanent_delete=todos-<?= $t['id'] ?>"
                           onclick="return confirm('Delete permanently?')">Delete</a>
                    </td>
                </tr>
        <?php }
        } else { ?>
            <tr><td colspan="3">No deleted todos</td></tr>
        <?php } ?>
    </table>

    <!-- DELETED EXPENSES -->
    <h2>Deleted Expenses</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Amount</th>
            <th>Description</th>
            <th>Action</th>
        </tr>
        <?php if ($expenses && mysqli_num_rows($expenses) > 0) {
            while ($e = mysqli_fetch_assoc($expenses)) { ?>
                <tr>
                    <td><?= $e['amount'] ?></td>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td>
                        <a href="?page=recycle&restore=expenses-<?= $e['id'] ?>">Restore</a> |
                        <a href="?page=recycle&permanent_delete=expenses-<?= $e['id'] ?>"
                           onclick="return confirm('Delete permanently?')">Delete</a>
                    </td>
                </tr>
        <?php }
        } else { ?>
            <tr><td colspan="3">No deleted expenses</td></tr>
        <?php } ?>
    </table>

    <!-- DELETED DOCUMENTS -->
    <h2>Deleted Documents</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>File Name</th>
            <th>Action</th>
        </tr>
        <?php if ($documents && mysqli_num_rows($documents) > 0) {
            while ($d = mysqli_fetch_assoc($documents)) { ?>
                <tr>
                    <td><?= htmlspecialchars($d['file_name']) ?></td>
                    <td>
                        <a href="?page=recycle&restore=documents-<?= $d['id'] ?>">Restore</a> |
                        <a href="?page=recycle&permanent_delete=documents-<?= $d['id'] ?>"
                           onclick="return confirm('Delete permanently?')">Delete</a>
                    </td>
                </tr>
        <?php }
        } else { ?>
            <tr><td colspan="2">No deleted documents</td></tr>
        <?php } ?>
    </table>

    <!-- DELETED CONTACTS -->
    <h2>Deleted Contacts</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Name</th>
            <th>Relation</th>
            <th>Phone</th>
            <th>Action</th>
        </tr>
        <?php if ($contacts && mysqli_num_rows($contacts) > 0) {
            while ($c = mysqli_fetch_assoc($contacts)) { ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['relation']) ?></td>
                    <td><?= htmlspecialchars($c['phone']) ?></td>
                    <td>
                        <a href="?page=recycle&restore=contacts-<?= $c['id'] ?>">Restore</a> |
                        <a href="?page=recycle&permanent_delete=contacts-<?= $c['id'] ?>"
                           onclick="return confirm('Delete permanently?')">Delete</a>
                    </td>
                </tr>
        <?php }
        } else { ?>
            <tr><td colspan="4">No deleted contacts</td></tr>
        <?php } ?>
    </table>

<?php } 
?>
</div>
</body>
</html>
<?php ob_end_flush(); ?>