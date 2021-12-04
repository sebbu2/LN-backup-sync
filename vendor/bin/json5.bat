@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../colinodell/json5/bin/json5
php "%BIN_TARGET%" %*
