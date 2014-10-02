.PHONY: usage win32 clean fullclean

BUNDLE_DIR=dynacase-devtool-bundle

SHELL=/bin/bash

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

php-5.4.33-nts-Win32-VC9-x86.zip:
	wget -O $@ http://windows.php.net/downloads/releases/$@

#
# Gettext from MinGW
#

gettext-0.18.3.2-1-mingw32-dev.tar.xz:
	wget -O $@ http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/$@

libgettextpo-0.18.3.2-1-mingw32-dll-0.tar.xz:
	wget -O $@ http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/$@

libintl-0.18.3.2-1-mingw32-dll-8.tar.xz:
	wget -O $@ http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/$@

gcc-core-4.8.1-4-mingw32-dll.tar.lzma:
	wget -O $@ http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/$@

gcc-c++-4.8.1-4-mingw32-dll.tar.lzma:
	wget -O $@ http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/$@

libiconv-1.14-3-mingw32-dll.tar.lzma:
	wget -O $@ http://downloads.sourceforge.net/project/mingw/MinGW/Base/libiconv/libiconv-1.14-3/$@

win32: dynacase-devtool-win32.zip

dynacase-devtool-win32.zip: php-5.4.33-nts-Win32-VC9-x86.zip gettext-0.18.3.2-1-mingw32-dev.tar.xz libgettextpo-0.18.3.2-1-mingw32-dll-0.tar.xz dynacase-devtool.phar libintl-0.18.3.2-1-mingw32-dll-8.tar.xz gcc-core-4.8.1-4-mingw32-dll.tar.lzma libiconv-1.14-3-mingw32-dll.tar.lzma gcc-c++-4.8.1-4-mingw32-dll.tar.lzma dynacase-devtool.bat
	mkdir -p "tmp/${BUNDLE_DIR}"
	
	cd "tmp/${BUNDLE_DIR}" && yes | unzip ../../php-5.4.33-nts-Win32-VC9-x86.zip
	cp "tmp/${BUNDLE_DIR}/php.ini-production" "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "date.timezone=Europe/Paris\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "extension_dir = 'ext'\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "extension=php_bz2.dll\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	echo -e "extension=php_mbstring.dll\r" >> "tmp/${BUNDLE_DIR}/php.ini"
	
	tar -C "tmp/${BUNDLE_DIR}" -Jxf gettext-0.18.3.2-1-mingw32-dev.tar.xz
	tar -C "tmp/${BUNDLE_DIR}" -Jxf libgettextpo-0.18.3.2-1-mingw32-dll-0.tar.xz
	tar -C "tmp/${BUNDLE_DIR}" -Jxf libintl-0.18.3.2-1-mingw32-dll-8.tar.xz
	tar -C "tmp/${BUNDLE_DIR}" --lzma -xf gcc-core-4.8.1-4-mingw32-dll.tar.lzma
	tar -C "tmp/${BUNDLE_DIR}" --lzma -xf libiconv-1.14-3-mingw32-dll.tar.lzma
	tar -C "tmp/${BUNDLE_DIR}" --lzma -xf gcc-c++-4.8.1-4-mingw32-dll.tar.lzma
	
	cp dynacase-devtool.phar "tmp/${BUNDLE_DIR}"
	cp dynacase-devtool.bat tmp
	
	cd tmp && zip -r ../dynacase-devtool-win32.zip "${BUNDLE_DIR}" dynacase-devtool.bat

realclean: clean
	rm -f composer.phar
	rm -f php-5.4.33-nts-Win32-VC9-x86.zip
	rm -f gettext-0.18.3.2-1-mingw32-dev.tar.xz libgettextpo-0.18.3.2-1-mingw32-dll-0.tar.xz libintl-0.18.3.2-1-mingw32-dll-8.tar.xz gcc-core-4.8.1-4-mingw32-dll.tar.lzma libiconv-1.14-3-mingw32-dll.tar.lzma gcc-c++-4.8.1-4-mingw32-dll.tar.lzma

clean:
	rm -Rf tmp
	rm -f dynacase-devtool.phar
	rm -f dynacase-devtool-win32.zip
