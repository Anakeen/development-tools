.PHONY: usage win32 clean fullclean

BUNDLE_DIR=dynacase-devtool-bundle

SHELL=/bin/bash

php-path = http://windows.php.net/downloads/releases/php-5.6.16-Win32-VC11-x86.zip
getText-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/gettext-0.18.3.2-1-mingw32-dev.tar.xz
libGetText-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/libgettextpo-0.18.3.2-1-mingw32-dll-0.tar.xz
libintl-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/libintl-0.18.3.2-1-mingw32-dll-8.tar.xz
gcc-core-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/gcc-core-4.8.1-4-mingw32-dll.tar.lzma
gcc-c++-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/gcc-c++-4.8.1-4-mingw32-dll.tar.lzma
libconv-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/libiconv/libiconv-1.14-3/libiconv-1.14-3-mingw32-dll.tar.lzma

usage:
	@echo "Usage:"
	@echo ""
	@echo "    make dynacase-devtool.phar"
	@echo ""
	@echo "    -or-"
	@echo ""
	@echo "    make win32"
	@echo ""

composer.phar:
	wget -O $@ https://getcomposer.org/download/1.0.0-alpha8/$@

dynacase-devtool.phar: composer.phar
	php composer.phar install
	./box.phar build

#
# PHP from PHP.net
#

php-get:
	wget -O php.zip $(php-path)

#
# Gettext from MinGW
#

gettext-get:
	wget -O gettext.tar.xz $(getText-path)

libgettextpo-get:
	wget -O libgettextpo.tar.xz $(libGetText-path)

libintl-get:
	wget -O libintl.tar.xz $(libintl-path)

gcc-core-get:
	wget -O gcc-core.tar.lzma $(gcc-core-path)

gcc-c++-get:
	wget -O gcc-c++.tar.lzma $(gcc-c++-path)

libiconv-get:
	wget -O libiconv.tar.lzma $(libconv-path)

win32: dynacase-devtool-win32.zip

dynacase-devtool-win32.zip: php-get gettext-get libgettextpo-get libintl-get gcc-core-get gcc-c++-get libiconv-get dynacase-devtool.phar dynacase-devtool.bat
	mkdir -p "tmp/${BUNDLE_DIR}"
	
	cd "tmp/${BUNDLE_DIR}" && yes | unzip ../../php.zip
	cp "tmp/${BUNDLE_DIR}/php.ini-production" "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "date.timezone=Europe/Paris\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "extension_dir = 'ext'\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "extension=php_bz2.dll\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "extension=php_mbstring.dll\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	
	tar -C "tmp/${BUNDLE_DIR}" -Jxf gettext.tar.xz
	tar -C "tmp/${BUNDLE_DIR}" -Jxf libgettextpo.tar.xz
	tar -C "tmp/${BUNDLE_DIR}" -Jxf libintl.tar.xz
	tar -C "tmp/${BUNDLE_DIR}" --lzma -xf gcc-core.tar.lzma
	tar -C "tmp/${BUNDLE_DIR}" --lzma -xf gcc-c++.tar.lzma
	tar -C "tmp/${BUNDLE_DIR}" --lzma -xf libiconv.tar.lzma
	
	cp dynacase-devtool.phar "tmp/${BUNDLE_DIR}"
	cp dynacase-devtool.bat tmp
	
	cd tmp && zip -r ../dynacase-devtool-win32.zip "${BUNDLE_DIR}" dynacase-devtool.bat

realclean: clean
	rm -f composer.phar
	rm -f php.zip
	rm -f gettext.tar.xz libgettextpo.tar.xz libintl.tar.xz gcc-core.tar.lzma gcc-c++.tar.lzma libiconv.tar.lzma

clean:
	rm -Rf tmp
	rm -f dynacase-devtool.phar
	rm -f dynacase-devtool-win32.zip
