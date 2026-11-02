@echo off
rem Configura la tienda en IIS (puerto 8050). Pide permisos de administrador.
rem Se le pueden pasar opciones, por ejemplo:  configurar_iis.bat -AbrirFirewall
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process powershell -Verb RunAs -ArgumentList '-NoProfile -ExecutionPolicy Bypass -NoExit -File \"%~dp0configurar_iis.ps1\" %*'"
