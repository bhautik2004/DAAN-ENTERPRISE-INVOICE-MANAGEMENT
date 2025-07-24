<?php
session_start();

include 'db.php';  // your DB connection + statement manager

// Close all prepared statements and database connection before destroying session
function close_all_statements_and_connection() {
    global $conn, $prepared_statements;

    if (!empty($prepared_statements) && is_array($prepared_statements)) {
        foreach ($prepared_statements as $stmt) {
            if ($stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
        }
    }

    if ($conn instanceof mysqli) {
        $conn->close();
    }
}

close_all_statements_and_connection();

session_unset();
session_destroy();

header("Location: index.php");
exit();
?>
