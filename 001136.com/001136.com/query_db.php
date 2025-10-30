<?php
// 连接数据库
$conn = new mysqli('localhost', 'opencart', 'epSNAcH2n3sBfhGb', 'opencart');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "=== 商品分类 ===\n";
$result = $conn->query("SELECT c.category_id, cd.name, c.parent_id FROM oc_category c LEFT JOIN oc_category_description cd ON c.category_id = cd.category_id WHERE cd.language_id = 2 ORDER BY c.parent_id, cd.name");
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['category_id']} - Name: {$row['name']} - Parent: {$row['parent_id']}\n";
}

echo "\n=== 产品表结构 ===\n";
$result = $conn->query("SHOW COLUMNS FROM oc_product");
while($row = $result->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n=== 产品描述表结构 ===\n";
$result = $conn->query("SHOW COLUMNS FROM oc_product_description");
while($row = $result->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n=== 现有产品数量 ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM oc_product");
$row = $result->fetch_assoc();
echo "总产品数: {$row['count']}\n";

$conn->close();
?>
