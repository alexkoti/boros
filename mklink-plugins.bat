@echo off

:: Este batch script cria junction links(symlinks) para as pastas dos plugins mais comuns no desenvolvimento localhost
:: As pastas sempre serão apontadas para a pasta 'pluginsFolder', que é onde estão concentrados todos os plugins

set "jobPath="
set /p jobPath="Digite o caminho da pasta de plugin do job:"
set "pluginsFolder=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\plugins"
set "list="
set /p "list=Digite os slugs dos plugins que deseja adicionar, separados por espaço:"

for %%i in (%list%) do (
	mklink /d "%jobPath%\%%i" "%pluginsFolder%\%%i"
)

pause