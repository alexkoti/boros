@echo off

:: Este batch script cria links(symlinks) para as pastas do core do WordPress(wp-admin, wp-includes, plugins, temas,  e arquivos da raiz)

set "jobPath="
set /p jobPath="Digite o caminho da pasta raiz do job:"

:: apontar wp-content\languages
mklink /d "%jobPath%\wp-content\languages" "D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\languages"
echo "%jobPath%\wp-content\languages" "D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\languages"

:: apontar plugins
set "list=boros force-regenerate-thumbnails wp-email-login wp-quick-pages better-lorem boros-newsletter-extended user-role-editor"
set "pluginsFolder=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\plugins"
for %%i in (%list%) do (
	mklink /d "%jobPath%\wp-content\plugins\%%i" "%pluginsFolder%\%%i"
	echo "%jobPath%\wp-content\plugins\%%i" "%pluginsFolder%\%%i"
)

:: apontar themes
set "list=twentyfifteen"
set "themesFolder=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\themes"
for %%i in (%list%) do (
	mklink /d "%jobPath%\wp-content\themes\%%i" "%themesFolder%\%%i"
	echo "%jobPath%\wp-content\themes\%%i" "%themesFolder%\%%i"
)

pause