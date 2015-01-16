@echo off

rem -------------------------------------------------------------
rem  Craft command line bootstrap script for Windows.
rem
rem  @link http://buildwithcraft.com/
rem  @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
rem  @license http://buildwithcraft.com/license
rem -------------------------------------------------------------

@setlocal

set CRAFT_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%CRAFT_PATH%craft" %*

@endlocal
