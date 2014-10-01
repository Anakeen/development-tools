@ECHO OFF

SETLOCAL

SET DEVTOOL_PATH=%~dp0
SET DEVTOOL_PATH=%DEVTOOL_PATH:~0,-1%

SET PATH=%PATH%;%DEVTOOL_PATH%\devtool-bundle;%DEVTOOL_PATH%\devtool-bundle\bin

"%DEVTOOL_PATH%\devtool-bundle\php.exe" "%DEVTOOL_PATH%\devtool-bundle\dynacase-devtool.phar" %*
