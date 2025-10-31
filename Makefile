# Makefile for building .deb and .rpm packages for skernel

PACKAGE_NAME := skernel
VERSION := 0.0.1
ARCH := amd64
MAINTAINER := "wheakerd <wheakerd@gmail.com>"
DESCRIPTION := "SuperKernel PHP micro framework binary with static PHP CLI"
LICENSE := MIT
URL := "https://github.com/wheakerd/skernel"

BUILD_DIR := build

DEB_DIR := $(PACKAGE_DIR)/deb
RPM_DIR := $(PACKAGE_DIR)/rpm

build-skernel:
	@mkdir -p "build"
	@echo "Start build skernel tool..."
	@cd sources && skernel build --debug && mv target/release/skernel ../build/skernel
	@chmod +x build/skernel
build-composer:
	@echo "Start build composer tool..."
	@cd tools && ./spc download \
		--with-php=8.4 \
 		--for-extensions \
 		"filter,iconv,mbstring,phar,tokenizer,zlib" \
 		--prefer-pre-built
	@cd tools && ./spc doctor --auto-fix
	@cd tools && ./spc build --build-micro "filter,iconv,mbstring,phar,tokenizer,zlib" \
		--enable-zts \
		--with-upx-pack \
 		--with-micro-fake-cli
	@tools/spc micro:combine tools/composer \
 		--with-micro=tools/buildroot/bin/micro.sfx \
 		--output=build/composer \
 		--with-ini-set="phar.readonly = Off"
	@chmod +x build/skernel build/composer

# ------------------------
# Prepare directories for packaging
# ------------------------
prepare:
	@mkdir -p tools

# ------------------------
# Build .deb package
# ------------------------
deb:

# ------------------------
# Build .rpm package using fpm
# ------------------------
rpm:

# ------------------------
# Clean build directories and all generated packages
# ------------------------
clean:
	@rm -rf target/

.PHONY: clean prepare build