<?php
require_once(__DIR__ . '/config.php');

$conn = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($conn->connect_error) {
    die('连接失败: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

function dump_table($conn, $table, &$output) {
    $output .= "===== $table =====\n";
    $res = $conn->query("SHOW CREATE TABLE `$table`");
    if ($res && $row = $res->fetch_assoc()) {
        $output .= $row['Create Table'] . "\n\n";
    }
}

$output = '';
dump_table($conn, DB_PREFIX . 'option', $output);
dump_table($conn, DB_PREFIX . 'option_description', $output);
dump_table($conn, DB_PREFIX . 'option_value', $output);
dump_table($conn, DB_PREFIX . 'option_value_description', $output);
dump_table($conn, DB_PREFIX . 'product_option', $output);
dump_table($conn, DB_PREFIX . 'product_option_value', $output);
dump_table($conn, DB_PREFIX . 'product', $output);

file_put_contents(__DIR__ . '/table_schema_output.txt', $output);

echo "已输出到 table_schema_output.txt";
$conn->close();
?>
