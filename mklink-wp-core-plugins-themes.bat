@echo off

:: Este batch script cria junction links(symlinks) para as pastas do core do WordPress(wp-admin, wp-includes, plugins, temas,  e arquivos da raiz)

set "jobPath="
set /p jobPath="Digite o caminho da pasta raiz do job:"

:: apontar wp-admin
set "originAdmin=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-admin"
mklink /d "%jobPath%\wp-admin" "%originAdmin%"
echo "%jobPath%\wp-admin" "%originAdmin%"

:: apontar wp-includes
set "originIncludes=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-includes"
mklink /d "%jobPath%\wp-includes" "%originIncludes%"
echo "%jobPath%\wp-includes" "%originIncludes%"

:: apontar wp-content\languages
mklink /d "%jobPath%\wp-content\languages" "D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\languages"
echo "%jobPath%\wp-content\languages" "D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\languages"

:: apontar os arquivos da raiz
for /F "tokens=*" %%A in (D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\plugins\boros\wordpress-core-file-list.txt) do (
	mklink "%jobPath%\%%A" "D:\PHP\xampp\htdocs\xampp\sites\boros\boros\%%A"
	echo "%jobPath%\%%A" "D:\PHP\xampp\htdocs\xampp\sites\boros\boros\%%A"
)

:: apontar plugins
set "list=force-regenerate-thumbnails wp-email-login wp-quick-pages better-lorem boros-newsletter-extended user-role-editor"
set "pluginsFolder=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\plugins"
for %%i in (%list%) do (
	mklink /d "%jobPath%\wp-content\plugins\%%i" "%pluginsFolder%\%%i"
	echo "%jobPath%\wp-content\plugins\%%i" "%pluginsFolder%\%%i"
)

:: apontar themes
set "list=twentyeleven twentyfifteen twentyfourteen twentyten twentythirteen twentytwelve"
set "themesFolder=D:\PHP\xampp\htdocs\xampp\sites\boros\boros\wp-content\themes"
for %%i in (%list%) do (
	mklink /d "%jobPath%\wp-content\themes\%%i" "%themesFolder%\%%i"
	echo "%jobPath%\wp-content\themes\%%i" "%themesFolder%\%%i"
)

pause