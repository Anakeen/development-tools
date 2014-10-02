@ECHO OFF

SETLOCAL

SET DEVTOOL_PATH=%~dp0
SET DEVTOOL_PATH=%DEVTOOL_PATH:~0,-1%

SET PATH=%PATH%;%DEVTOOL_PATH%\dynacase-devtool-bundle;%DEVTOOL_PATH%\dynacase-devtool-bundle\bin

"%DEVTOOL_PATH%\dynacase-devtool-bundle\php.exe" "%DEVTOOL_PATH%\dynacase-devtool-bundle\dynacase-devtool.phar" %*
