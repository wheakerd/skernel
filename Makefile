# Makefile for building and testing my PHP micro+phar tool

# 配置变量
PHAR_NAME = mytool.phar
DEB_NAME = mytool-$(VERSION).deb
RPM_NAME = mytool-$(VERSION).x86_64.rpm
SRC_DIR = src
BUILD_DIR = build
DIST_DIR = dist
DEBIAN_DIR = debian
RPM_DIR = rpm
VERSION = 1.0.0

# 定义基础路径
CURRENT_DIR := $(shell pwd)
PHP_BUILD_DIR := $(CURRENT_DIR)/build/php
SPC := $(PHP_BUILD_DIR)/spc

# 构建目标
.PHONY: all build-phar build-deb build-rpm test clean

# 默认目标：构建 PHAR、DEB 和 RPM，进行测试
all: build-php build-deb build-rpm test

# 构建 PHP 文件
build-php:
	@echo "Building PHP file..."
	@mkdir -p $(PHP_BUILD_DIR) # 创建 build/php 目录
	@cp build/spc $(PHP_BUILD_DIR) # 复制 spc 文件到 build/php
	@chmod +x $(SPC) # 给 spc 文件加执行权限
	@cd $(PHP_BUILD_DIR) &&	$(SPC) download --with-php=8.4 --for-extensions "filter,iconv,phar,tokenizer,openssl,zlib" --prefer-pre-built --debug # 下载并构建 PHP 扩展
	@#cd $(PHP_BUILD_DIR) &&	$(SPC) install-pkg upx --debug # 下载 UPX 命令
	@cd $(PHP_BUILD_DIR) &&	$(SPC) doctor --auto-fix --debug # 自动检查和准备构建环境命令
	@cd $(PHP_BUILD_DIR) &&	$(SPC) build --build-cli --build-micro "filter,iconv,phar,tokenizer,openssl,zlib" --debug --with-upx-pack # 构建所需扩展

# 构建 skernel 工具
build-skernel:
#build-php
	@echo "Building skernel tool..."
	@#cd src && yes | COMPOSER_ALLOW_SUPERUSER=1 $(CURRENT_DIR)/build/php/buildroot/bin/php $(CURRENT_DIR)/build/composer skernel serve
	@cd src && yes | COMPOSER_ALLOW_SUPERUSER=1 composer skernel serve
	@cd $(CURRENT_DIR)/src/target/runtime && php -d phar.readonly=0 bin.php build --disable-binary --debug
	@php ini.php
	@cd build/php/buildroot/bin && cat micro.sfx $(CURRENT_DIR)/php.bin $(CURRENT_DIR)/src/target/runtime/target/release/skernel.phar > $(CURRENT_DIR)/dist/skernel
	@chmod 0755 $(CURRENT_DIR)/dist/skernel
	@echo "Please use it: $(CURRENT_DIR)/dist/skernel"

# 构建 DEB 包
build-deb:
	@echo "Building DEB package..."
	# 设置 DEB 打包目录
	mkdir -p $(BUILD_DIR)/mytool-deb/usr/bin
	cp $(DIST_DIR)/$(PHAR_NAME) $(BUILD_DIR)/mytool-deb/usr/bin/mytool
	# 设置控制文件
	cp $(DEBIAN_DIR)/control $(BUILD_DIR)/mytool-deb/DEBIAN/control
	# 打包 DEB
	dpkg-deb --build $(BUILD_DIR)/mytool-deb $(DIST_DIR)/$(DEB_NAME)

# 构建 RPM 包
build-rpm:
	@echo "Building RPM package..."
	# 设置 RPM 打包目录
	mkdir -p $(RPM_DIR)/SOURCES
	cp $(DIST_DIR)/$(PHAR_NAME) $(RPM_DIR)/SOURCES/mytool
	# 设置 SPEC 文件
	cp $(RPM_DIR)/SPECS/mytool.spec $(RPM_DIR)/SPECS/mytool.spec
	# 打包 RPM
	rpmbuild -ba $(RPM_DIR)/SPECS/mytool.spec --define "_topdir $(RPM_DIR)"

# 运行测试
test:
	@echo "Running tests..."
	php vendor/bin/phpunit --configuration phpunit.xml

# 清理构建产物
clean:
	@echo "Cleaning build and dist directories..."
	rm -rf $(BUILD_DIR) $(DIST_DIR) mytool-*.rpm mytool-*.deb
