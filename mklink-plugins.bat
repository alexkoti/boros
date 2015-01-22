@echo off

:: Este batch script cria junction links(symlinks) para as pastas dos plugins mais comuns no desenvolvimento localhost
:: As pastas sempre serão apontadas para a pasta 'pluginsFolder', que é onde estão concentrados todos os plugins

set "jobPath="
set /p jobPath="Digite o caminho da pasta de plugin do job:"
set "pluginsFolder=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\plugins"
set "list=force-regenerate-thumbnails wp-email-login wp-quick-pages better-lorem boros-newsletter-extended user-role-editor"

for %%i in (%list%) do (
	mklink /d "%jobPath%\%%i" "%pluginsFolder%\%%i"
)

pause