@echo off
echo Stoppe PHP CRM Server auf Port 8000...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000') do (
    taskkill /F /PID %%a
)
echo.
echo Versuche MySQL Dienst (MySQL80) zu stoppen...
net stop MySQL80
echo.
echo Server und Datenbank beendet.
pause
