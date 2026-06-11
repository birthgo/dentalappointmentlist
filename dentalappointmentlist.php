<?php
// 1. เชื่อมต่อ Database
$host = '172.16.16.246';
$user = 'adminwork';
$pass = 'admin1234';
$db   = 'lkhos';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("เชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// 2. รับค่าจาก Form
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : $start_date;
$doctor_filter = isset($_GET['doctor_code']) ? $_GET['doctor_code'] : '';

// Function แปลงวันที่ไทย
function toThaiFullDate($date) {
    if(!$date) return "";
    $months = ["01"=>"มกราคม", "02"=>"กุมภาพันธ์", "03"=>"มีนาคม", "04"=>"เมษายน", "05"=>"พฤษภาคม", "06"=>"มิถุนายน", "07"=>"กรกฎาคม", "08"=>"สิงหาคม", "09"=>"กันยายน", "10"=>"ตุลาคม", "11"=>"พฤศจิกายน", "12"=>"ธันวาคม"];
    $d = explode("-", $date);
    return (int)$d[2] . " " . $months[$d[1]] . " " . ($d[0] + 543);
}

// 3. รายชื่อรหัสทันตแพทย์
$target_doctors_list = ['0675', '0237', '0739', '0768', '0772', '0797', '0730', '0731', '0749', '0778'];
$target_doctors_string = "('" . implode("','", $target_doctors_list) . "')";

$sql_doctor = "SELECT code, name FROM doctor WHERE code IN $target_doctors_string";
$res_doctor = $conn->query($sql_doctor);
$doctor_data = [];
while($row = $res_doctor->fetch_assoc()) {
    $doctor_data[$row['code']] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบนัดหมายทันตกรรม | โรงพยาบาลเลาขวัญ</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f0f9ff; color: #333; margin: 0; padding: 20px; }
        .card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 1400px; margin: auto; }
        h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 0; font-weight: 600; }
        
        .filter-section { margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; }
        input[type='date'], select { padding: 8px; font-size: 15px; border: 1px solid #bae6fd; border-radius: 5px; outline: none; }
        
        .btn { padding: 9px 20px; font-size: 15px; font-weight: 500; border-radius: 5px; border: none; cursor: pointer; text-decoration: none; display: inline-block; color: white; transition: 0.3s; }
        .btn-search { background: #007bff; }
        .btn-today { background: #28a745; }

        .date-banner { 
            background-color: #ffffff; 
            color: #475569;           
            padding: 20px; 
            border-radius: 8px; 
            text-align: center; 
            font-size: 26px; 
            font-weight: 600;
            margin: 10px 0 20px 0;
            border: 2px solid #f1f5f9; 
        }
        .highlight-text { color: #d97706; }

        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: #4285f4; color: white; padding: 12px; border: 1px solid #ddd; font-weight: 500; }
        td { padding: 10px; border: 1px solid #eee; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover td { background-color: #e3f2fd !important; cursor: pointer; }
        .center { text-align: center; }
        .doctor-text { font-weight: bold; color: #0056b3; }
        .status-come { color: #28a745; font-weight: bold; }
        .status-not { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>

<div class="card">
    <h2>🏥 รายการนัดหมายแผนกทันตกรรม โรงพยาบาลเลาขวัญ</h2>
    
    <form method="GET" class="filter-section">
        เริ่ม: <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>"> 
        ถึง: <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>"> 
        <select name="doctor_code">
            <option value="">-- แสดงทั้งหมด --</option>
            <?php foreach($doctor_data as $code => $name) {
                $selected = ($doctor_filter == $code) ? 'selected' : '';
                echo "<option value='$code' $selected>$name</option>";
            } ?>
        </select>
        <button type="submit" class="btn btn-search">ค้นหา</button>
        <a href="?" class="btn btn-today">วันนี้</a>
    </form>

    <div class="date-banner">
        <?php if($start_date == $end_date): ?>
            📅 ประจำวันที่ <span class="highlight-text"><?php echo toThaiFullDate($start_date); ?></span>
        <?php else: ?>
            📅 ระหว่างวันที่ <span class="highlight-text"><?php echo toThaiFullDate($start_date); ?></span> ถึง <span class="highlight-text"><?php echo toThaiFullDate($end_date); ?></span>
        <?php endif; ?>
    </div>

    <?php
    // เงื่อนไขหลักสำหรับรายการนัด
    $where = "WHERE a.nextdate BETWEEN '$start_date' AND '$end_date' AND a.spclty = '11' ";
    if ($doctor_filter != '') { $where .= " AND a.doctor = '$doctor_filter' "; }

    // ปรับ SQL: นับจำนวน Visit ในวันนั้นๆ (ไม่สนแผนก) เพื่อใช้เช็คสถานะการมาโรงพยาบาล
    $sql = "SELECT a.nextdate, a.nexttime, a.hn, p.pname, p.fname, p.lname, p.hometel, 
                   d.name as doctor_name, a.note,
                   (SELECT COUNT(*) FROM ovst WHERE hn = a.hn AND vstdate = a.nextdate) as visit_count
            FROM oapp a
            LEFT JOIN patient p ON p.hn = a.hn
            LEFT JOIN doctor d ON d.code = a.doctor 
            $where
            ORDER BY a.nextdate ASC, a.nexttime ASC";

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo "<table><tr><th>วันที่นัด</th><th>เวลา</th><th>HN</th><th>ชื่อ-นามสกุล</th><th>เบอร์โทร</th><th>หมายเหตุ</th><th>ผู้รักษา</th><th>สถานะ</th></tr>";
        while($row = $result->fetch_assoc()) {
            // เช็คว่า visit_count > 0 หรือไม่ (ถ้ามากกว่า 0 คือมีการเปิด VN ที่ไหนก็ได้ในรพ.)
            $status = ($row['visit_count'] > 0) ? "<span class='status-come'>✓ มาแล้ว</span>" : "<span class='status-not'>✗ ยังไม่มา</span>";
            
            echo "<tr>";
            echo "<td class='center'>".date('d/m/Y', strtotime($row['nextdate']))."</td>";
            echo "<td class='center'>".substr($row['nexttime'],0,5)." น.</td>";
            echo "<td class='center'>".$row['hn']."</td>";
            echo "<td>".$row['pname'].$row['fname']." ".$row['lname']."</td>";
            echo "<td class='center'>".$row['hometel']."</td>";
            echo "<td>".$row['note']."</td>";
            echo "<td class='doctor-text'>".$row['doctor_name']."</td>";
            echo "<td class='center'>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='text-align:center; padding:50px; color:#94a3b8; background:white;'>ไม่พบข้อมูลนัดหมาย</div>";
    }
    ?>
</div>

<div style="text-align:right; margin-top:10px; font-size:11px; color:#94a3b8; max-width:1400px; margin: 10px auto;">
    Developed by birthgo | Laokhwan Hospital
</div>

</body>
</html>