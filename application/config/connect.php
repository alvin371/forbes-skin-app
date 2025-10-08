<?php
$koneksi = mysqli_connect("153.92.15.25", "u944207378_bhskin", "P~2c*8#tg5", "u944207378_bhskin");

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
echo "Koneksi berhasil!";
?>
