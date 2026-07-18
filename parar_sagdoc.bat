@echo off
chcp 65001 >nul
setlocal

set "PORTA=8000"

echo A procurar o servidor SAGDOC na porta %PORTA%...

set "PID="
for /f "tokens=5" %%p in ('netstat -ano ^| findstr ":%PORTA% " ^| findstr "LISTENING"') do (
    set "PID=%%p"
)

if not defined PID (
    echo Nao encontrei nenhum servidor SAGDOC a correr na porta %PORTA%.
    pause
    exit /b 0
)

echo A terminar o processo (PID %PID%)...
taskkill /pid %PID% /f >nul 2>&1

if errorlevel 1 (
    echo [AVISO] Nao consegui terminar o processo automaticamente.
    echo          Feche manualmente a janela "SAGDOC - Servidor".
) else (
    echo Servidor SAGDOC parado.
)

echo.
pause
