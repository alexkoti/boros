@echo off

:: Este batch script cria junction links(symlinks) para um plugin unitário, perguntando o slug no prompt

set "jobPath="
set /p jobPath="Digite o caminho da pasta de plugin do job:"
set "slug="
set /p slug="Digite o slug do plugin a ser copiado:"
set "pluginsFolder=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\plugins"

mklink /d "%jobPath%\%slug%" "%pluginsFolder%\%slug%"

pause