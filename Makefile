.PHONY: clean realclean dynacase-devtool.phar dynacase-devtool-win32.zip help

BUNDLE_DIR=dynacase-devtool-bundle
COMPOSER_VERSION=1.0.0
PHP_VERSION=7.0.9

SHELL=/bin/bash

composer-path = https://getcomposer.org/download/$(COMPOSER_VERSION)/composer.phar
php-path = http://windows.php.net/downloads/releases/php-$(PHP_VERSION)-Win32-VC14-x86.zip
getText-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/gettext-0.18.3.2-1-mingw32-dev.tar.xz
libGetText-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/libgettextpo-0.18.3.2-1-mingw32-dll-0.tar.xz
libintl-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/libintl-0.18.3.2-1-mingw32-dll-8.tar.xz
gcc-core-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/gcc-core-4.8.1-4-mingw32-dll.tar.lzma
gcc-c++-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/gcc-c++-4.8.1-4-mingw32-dll.tar.lzma
libconv-path = http://downloads.sourceforge.net/project/mingw/MinGW/Base/libiconv/libiconv-1.14-3/libiconv-1.14-3-mingw32-dll.tar.lzma

all: linux win32 ## generate all binaries

linux: dynacase-devtool.phar ## generate binary for linux

win32: dynacase-devtool-win32.zip ## generate binary for windows

composer.phar:
	wget -O $@ $(composer-path)

box.phar:
	curl -LSs https://box-project.github.io/box2/installer.php | php

dynacase-devtool.phar: composer.phar box.phar
	php composer.phar install
	./box.phar build

######################
#  PHP from PHP.net  #
######################
php-get: php.zip
php.zip:
	wget -O php.zip $(php-path)

######################
# Gettext from MinGW #
######################

gettext-get: gettext.tar.xz
gettext.tar.xz:
	wget -O $@ $(getText-path)

libgettextpo-get: libgettextpo.tar.xz
libgettextpo.tar.xz:
	wget -O $@ $(libGetText-path)

libintl-get: libintl.tar.xz
libintl.tar.xz:
	wget -O $@ $(libintl-path)

gcc-core-get: gcc-core.tar.lzma
gcc-core.tar.lzma:
	wget -O $@ $(gcc-core-path)

gcc-c++-get: gcc-c++.tar.lzma
gcc-c++.tar.lzma:
	wget -O $@ $(gcc-c++-path)

libiconv-get: libiconv.tar.lzma
libiconv.tar.lzma:
	wget -O $@ $(libconv-path)

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

clean-all: clean-buildtools clean-bin clean-libs clean-tmp ## remove temp, lib, binaries files and build tools

clean-buildtools: ## remove build tools
	rm -f composer.phar
	rm -f box.phar

clean-bin: ## remove binaries
	rm -f dynacase-devtool.phar
	rm -f dynacase-devtool-win32.zip

clean: clean-libs clean-tmp ## remove temp and lib files

clean-libs: ## remove lib files
	rm -f php.zip
	rm -f gettext.tar.xz libgettextpo.tar.xz libintl.tar.xz gcc-core.tar.lzma gcc-c++.tar.lzma libiconv.tar.lzma

clean-tmp: ## remove temp files
	rm -Rf tmp

######################
#        HELP        #
######################
.DEFAULT_GOAL := help

HELP_WIDTH=20

help: ## Show this help message
	@grep -E '^[0-9a-zA-Z_-.]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-$(HELP_WIDTH)s\033[0m %s\n", $$1, $$2}'
