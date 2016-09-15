.PHONY: all linux win32 install clean-all clean-buildtools clean-bin clean clean-libs clean-tmp help

BUNDLE_DIR=dynacase-devtool-bundle
COMPOSER_VERSION=1.0.0
PHP_VERSION=7.0.11
SHELL=/bin/bash

composer-path   = https://getcomposer.org/download/$(COMPOSER_VERSION)/composer.phar
composer-sha256 = 1acc000cf23bd9d19e1590c2edeb44fb915f88d85f1798925ec989c601db0bd6

php-path   = http://windows.php.net/downloads/releases/php-$(PHP_VERSION)-Win32-VC14-x86.zip
php-sha256 = 4cb2064c484cb4b632867a81243bfda3d702b5e5548fed037d6787ec0c43d7e3

getText-path   = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/gettext-0.18.3.2-1-mingw32-dev.tar.xz
getText-sha256 = 1cf8a5f9b9c6e29985e84c9918928c4b5ffc236b72b1789235eb7cb3cce53439

libGetText-path   = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/libgettextpo-0.18.3.2-1-mingw32-dll-0.tar.xz
libGetText-sha256 = 93974ceb8d259f0e502ba94cf93116aa488ea1308c795873edc651a4b5d1c9ed

libintl-path   = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gettext/gettext-0.18.3.2-1/libintl-0.18.3.2-1-mingw32-dll-8.tar.xz
libintl-sha256 = a2ffd68d7991e0e44aa26c6224e5f0223bce29143bdbdf4b5d5d4798990cda76

gcc-core-path   = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/gcc-core-4.8.1-4-mingw32-dll.tar.lzma
gcc-core-sha256 = 0dab5f923c5d289b8e7e22a3b16ebff5ff8b7c7c0d295ac71806d97ef87b8bee

gcc-c++-path   = http://downloads.sourceforge.net/project/mingw/MinGW/Base/gcc/Version4/gcc-4.8.1-4/gcc-c++-4.8.1-4-mingw32-dll.tar.lzma
gcc-c++-sha256 = 53f9bc499d606460196b0cda57c6331d019d58de46d3ddb1708984e06781b8d3

libiconv-path   = http://downloads.sourceforge.net/project/mingw/MinGW/Base/libiconv/libiconv-1.14-3/libiconv-1.14-3-mingw32-dll.tar.lzma
libiconv-sha256 = fbdab03c19c6c50f15b58d02a3cb8c31e8d95baafaa67239f389b9023c7757fd

box2installer-path   = https://github.com/box-project/box2/releases/download/2.7.4/box-2.7.4.phar
box2installer-sha256 = bb4896d231f64e7e0383660e5548092e5447619aaface50103ac0af41b6f29ac

all: linux win32 ## generate all binaries

linux: dynacase-devtool.phar ## generate binary for linux

win32: dynacase-devtool-win32.zip ## generate binary for windows

install: dynacase-devtool.phar dynacase-devtool-win32.zip
	@test "x${DESTDIR}" = "x" && (echo -e "\n\n*** Missing DESTDIR variable! ***\n\n" && false) || true
	mkdir -p "${DESTDIR}"
	cp $^ "${DESTDIR}"

composer.phar:
	./fetch $(composer-path) $@ $(composer-sha256)

box.phar:
	./fetch $(box2installer-path) $@ $(box2installer-sha256)

dynacase-devtool.phar: composer.phar box.phar
	php composer.phar install
	php -d phar.readonly=false box.phar build

######################
#  PHP from PHP.net  #
######################
php.zip:
	./fetch $(php-path) $@ $(php-sha256)

######################
# Gettext from MinGW #
######################

gettext.tar.xz:
	./fetch $(getText-path) $@ $(getText-sha256)

libgettextpo.tar.xz:
	./fetch $(libGetText-path) $@ $(libGetText-sha256)

libintl.tar.xz:
	./fetch $(libintl-path) $@ $(libintl-sha256)

gcc-core.tar.lzma:
	./fetch $(gcc-core-path) $@ $(gcc-core-sha256)

gcc-c++.tar.lzma:
	./fetch $(gcc-c++-path) $@ $(gcc-c++-sha256)

libiconv.tar.lzma:
	./fetch $(libiconv-path) $@ $(libiconv-sha256)

dynacase-devtool-win32.zip: php.zip gettext.tar.xz libgettextpo.tar.xz libintl.tar.xz gcc-core.tar.lzma gcc-c++.tar.lzma libiconv.tar.lzma dynacase-devtool.phar dynacase-devtool.bat
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
	cp  dynacase-devtool.phar "tmp/${BUNDLE_DIR}"
	cp dynacase-devtool.bat tmp
	cd tmp && zip -r ../dynacase-devtool-win32.zip "${BUNDLE_DIR}" dynacase-devtool.bat

clean-all: clean-buildtools clean-bin clean-libs clean-tmp ## remove temp, lib, binaries files and build tools

clean-buildtools: ## remove build tools
	rm -f composer.phar
	rm -f box.phar

clean-bin: ## remove binaries
	rm -Rf dynacase-devtool.phar dynacase-devtool-win32.zip

clean: clean-libs clean-tmp ## remove temp and lib files

clean-libs: ## remove lib files
	rm -f php.zip
	rm -f gettext.tar.xz libgettextpo.tar.xz libintl.tar.xz gcc-core.tar.lzma gcc-c++.tar.lzma libiconv.tar.lzma

clean-tmp: ## remove temp files
	rm -Rf tmp
	rm -f fetch.tmp.*

######################
#        HELP        #
######################
.DEFAULT_GOAL := help

HELP_WIDTH=20

help: ## Show this help message
	@grep -E '^[0-9a-zA-Z_-.]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-$(HELP_WIDTH)s\033[0m %s\n", $$1, $$2}'
