@echo off
echo Versuche MySQL Dienst (MySQL80) zu starten...
net start MySQL80
echo.
echo Starte PHP CRM Server auf http://localhost:8000...
php -S localhost:8000 -t httpdocs
pause
