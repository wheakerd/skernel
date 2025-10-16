.PHONY: all compile

all: build test

build:
	@echo "Start compiling..."
	@bin/skernel build --disable-binary --debug
	@bin/php -d phar.readonly=0 target/release/pre_skernel.phar build --debug

test:
	@chmod +x target/release/skernel
	@target/release/skernel
	@target/release/skernel -v
	@target/release/skernel build --debug