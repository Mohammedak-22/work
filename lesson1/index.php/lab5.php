<?php
// lab5.php - نظام تحويل الأموال

// 1. الاتصال بقاعدة البيانات
$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) die("خطأ في الاتصال");

// إنشاء قاعدة البيانات والجداول
$conn->query("CREATE DATABASE IF NOT EXISTS bank_lab5");
$conn->select_db("bank_lab5");

$conn->query("CREATE TABLE IF NOT EXISTS accounts (
    id INT PRIMARY KEY,
    name VARCHAR(50),
    balance DECIMAL(10,2)
)");

$conn->query("CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_acc INT,
    to_acc INT,
    amount DECIMAL(10,2),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 🔥 حذف جميع الحسابات القديمة
$conn->query("DELETE FROM accounts");

// 🔥 إضافة الحسابات الجديدة بالأسماء المطلوبة فقط
$conn->query("INSERT INTO accounts (id, name, balance) VALUES 
    (1, 'محمد', 70000),
    (2, 'حمدي', 60000),
    (3, 'جوهر', 50000)");

// 2. معالجة التحويل
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from = $_POST['from'];
    $to = $_POST['to'];
    $amount = $_POST['amount'];
    
    if ($from && $to && $amount > 0) {
        $conn->begin_transaction();
        
        try {
            $conn->query("UPDATE accounts SET balance = balance - $amount WHERE id = $from");
            $conn->query("UPDATE accounts SET balance = balance + $amount WHERE id = $to");
            $conn->query("INSERT INTO transactions (from_acc, to_acc, amount) VALUES ($from, $to, $amount)");
            
            $conn->commit();
            $message = "<p style='color:green; background:#d4ffd4; padding:10px; border-radius:5px;'>✅ تم التحويل بنجاح!</p>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<p style='color:red; background:#ffd4d4; padding:10px; border-radius:5px;'>❌ فشل التحويل</p>";
        }
    }
}

// 3. جلب البيانات
$accounts = $conn->query("SELECT * FROM accounts");
$transactions = $conn->query("SELECT * FROM transactions ORDER BY date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظام تحويل الأموال</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f0f0f0; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #2c3e50; text-align: center; }
        form { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; }
        select, input { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #27ae60; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: center; }
        th { background: #34495e; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏦 نظام تحويل الأموال</h1>
        <p style="text-align:center; color:#666;">الحسابات: محمد، حمدي، جوهر</p>
        
        <?php echo $message; ?>
        
        <form method="POST">
            <h3>💰 تحويل أموال</h3>
            
            <label>من حساب:</label>
            <select name="from" required>
                <option value="">اختر المرسل</option>
                <?php while($row = $accounts->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo $row['name'] . ' (' . $row['balance'] . ' ريال)'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <?php $accounts->data_seek(0); ?>
            
            <label>إلى حساب:</label>
            <select name="to" required>
                <option value="">اختر المستقبل</option>
                <?php while($row = $accounts->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                <?php endwhile; ?>
            </select>
            
            <label>المبلغ (ريال):</label>
            <input type="number" name="amount" min="1" required placeholder="أدخل المبلغ">
            
            <button type="submit">🔁 تحويل</button>
        </form>
        
        <h3>📊 الحسابات الحالية</h3>
        <?php $accounts->data_seek(0); ?>
        <table>
            <tr><th>الاسم</th><th>الرصيد</th></tr>
            <?php while($row = $accounts->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['balance']; ?> ريال</td>
                </tr>
            <?php endwhile; ?>
        </table>
        
        <h3>📋 آخر العمليات</h3>
        <table>
            <tr><th>من</th><th>إلى</th><th>المبلغ</th><th>الوقت</th></tr>
            <?php if($transactions->num_rows > 0): ?>
                <?php while($row = $transactions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['from_acc']; ?></td>
                        <td><?php echo $row['to_acc']; ?></td>
                        <td><?php echo $row['amount']; ?> ريال</td>
                        <td><?php echo $row['date']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">لا توجد عمليات سابقة</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>