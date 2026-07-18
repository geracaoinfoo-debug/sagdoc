@echo off
chcp 65001 >nul
setlocal

set "PROJ=%~dp0"
set "PHP=C:\xampp\php\php.exe"
set "MYSQLD_CHECK=mysqld.exe"
set "PORTA=8000"
set "URL=http://localhost:%PORTA%"

echo ============================================
echo   SAGDOC - Sistema de Apoio a Gestao
echo   Documental Aduaneira
echo ============================================
echo.

REM --- 1. Verificar se o PHP existe -----------------------------------
if not exist "%PHP%" (
    echo [ERRO] Nao encontrei o PHP em "%PHP%".
    echo         Ajuste a variavel PHP no topo deste ficheiro.
    pause
    exit /b 1
)

REM --- 2. Verificar/arrancar o MySQL (MariaDB) do XAMPP ---------------
tasklist /fi "imagename eq %MYSQLD_CHECK%" 2>nul | find /i "%MYSQLD_CHECK%" >nul
if errorlevel 1 (
    echo [MySQL] Nao esta a correr. A tentar arrancar...
    if exist "C:\xampp\mysql_start.bat" (
        call "C:\xampp\mysql_start.bat"
        timeout /t 3 /nobreak >nul
    ) else (
        echo [AVISO] Nao encontrei C:\xampp\mysql_start.bat.
        echo          Abra o XAMPP Control Panel e inicie o modulo MySQL manualmente.
        pause
    )
) else (
    echo [MySQL] Ja esta a correr.
)

REM --- 3. Verificar se ja ha um servidor SAGDOC nesta porta -----------
netstat -ano | findstr ":%PORTA% " | findstr "LISTENING" >nul
if not errorlevel 1 (
    echo [SAGDOC] Ja existe um servidor a correr em %URL%.
    goto abrir_browser
)

REM --- 4. Arrancar o servidor de desenvolvimento do SAGDOC ------------
echo [SAGDOC] A arrancar o servidor em %URL% ...
start "SAGDOC - Servidor (nao feche esta janela)" cmd /k ""%PHP%" -S localhost:%PORTA% -t "%PROJ%public" "%PROJ%bin\dev_router.php""

timeout /t 2 /nobreak >nul

:abrir_browser
echo [SAGDOC] A abrir o navegador...
start "" "%URL%/login"

echo.
echo Pronto. Contas de demonstracao (senha "demo"):
echo   jbarbosa      - Despachante
echo   averificador  - Verificador
echo   chefe         - Chefe de Setor
echo   gestor        - Gestor DGA
echo   admin         - Administrador
echo.
echo Para parar o servidor, feche a janela "SAGDOC - Servidor"
echo ou execute parar_sagdoc.bat
echo.
pause
