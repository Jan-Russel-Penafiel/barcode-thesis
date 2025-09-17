@echo off
start "" "C:\xampp\xampp-control.exe"
timeout /t 5
net start Apache2.4
net start MySQL 