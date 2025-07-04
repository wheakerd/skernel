# Composer 脚本设置
COMPOSER_CMD=composer dump-autoload -o --classmap-authoritative

# Hyperf 执行器路径
HYPERF=php bin/hyperf.php

# 清理路径
CONTAINER_CACHE=runtime/container

# .PHONY 逻辑目标
.PHONY: prepare-autoload clean-container dev start build all

# 生成 classmap（强制开启 classmap-authoritative 模式）
prepare-autoload:
	@echo "📦 Generating Composer classmap..."
	$(COMPOSER_CMD)

# 清理容器缓存（统一入口）
clean-container:
	@echo "🧹 Cleaning container cache..."
	rm -rf $(CONTAINER_CACHE)

# 开发模式启动（watch）
dev: prepare-autoload clean-container
	@echo "🚀 Starting Hyperf in watch mode..."
	$(HYPERF) server:watch

# 正式运行
start: prepare-autoload clean-container
	@echo "🚀 Starting Hyperf in normal mode..."
	$(HYPERF) start

# 预构建模式
build: prepare-autoload clean-container
	@echo "🏗️  Building Hyperf application..."

	rm -f ./skernel.phar
	rm -f ./skernel

	$(HYPERF) run:build

	rm -f ./skernel.phar

# 测试编译后 Skernel 工具
test:
	./skernel package

# 默认任务（比如构建）
all: build
