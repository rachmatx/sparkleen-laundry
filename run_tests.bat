@echo off
echo ====================================================
echo    SPARKLEEN LAUNDRY - AUTOMATED TEST RUNNER
echo ====================================================
echo.

echo [1/3] Menyiapkan direktori laporan...
if not exist "build\logs" mkdir "build\logs"

echo [2/3] Menjalankan test menggunakan PHPUnit...
call vendor\bin\phpunit --log-junit build\logs\logfile.xml --testdox

echo.
echo [3/3] Membuat Laporan HTML...
php tests\generate_report.php

echo.
echo ====================================================
echo SELESAI! Laporan pengujian dalam bentuk web/HTML
echo dapat Anda buka melalui browser pada path berikut:
echo public\reports\test-report.html
echo ====================================================
