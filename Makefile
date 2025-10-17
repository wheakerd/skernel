.PHONY: all

all: build test

build:
	@rm -rf target/
	@echo "Start compiling..."
	@bin/skernel build --disable-binary --debug
	@bin/php -d phar.readonly=0 target/release/pre_skernel.phar build --debug

test:
	@chmod +x target/release/skernel
	@target/release/skernel
	@target/release/skernel -v