<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    if (!empty($name) && !empty($email) && !empty($phone)) {
        $sql = "UPDATE contacts SET name='$name', email='$email', phone='$phone' WHERE id=$id";

        if (mysqli_query($conn, $sql)) {
            header("Location: index.php");
            exit();
        } else {
            echo "Error updating record: " . mysqli_error($conn);
        }
    } else {
        echo "All fields are required.";
    }
} else {
    header("Location: index.php");
    exit();
}
?>
