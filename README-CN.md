# Skernel

**Skernel** 是针对于 **super-kernel** 框架的开发工具，为开发者在使用该框架时提供以下功能。

- Phar 构建
- Phar 转二进制包（免除PHP环境安装）【伪编译】
- Phar 热重启
- PHP 代码编译为二进制
- <div style="color: #ff0">此外，本工具可能介入 `composer` 扫描、运行 !!!</div>

> ⚡ `Skernel` 专注于早期扫描、AOT类映射和phar自举。
> 🧩 专为框架作者、CLI工具和性能敏感的运行时环境而设计。

---

## ✨ Features

- ✅ 通过`skernel.config.yaml`进行声明性配置（支持IDE模式）
- 🧠 静态类图扫描（PSR-4/类图/PSR-0）
- 📦 支持自定义“.stub”的Phar包装
- 🚀 优化启动，无Composer运行时依赖性
- 🔍 可选AOP/代理生成预运行
- 🛠️ 作为构建步骤或CLI工具集成到任何PHP项目中

---

## 📂 Project Structure (Typical)

your-project/
├── app/
├── extend/
├── vendor/
├── runtime/
│ └── classmap.php # Generated static classmap
├── bin/
│ ├── skernel # CLI entrypoint (optional)
│ └── skernel.stub.php # Phar stub
├── build/
│ └── build-phar.php # Build script
├── skernel.config.yaml # Configuration file
├── skernel.schema.json # (optional) Schema for IDE validation
└── ...

---

## ⚙️ Configuration (`skernel.config.yaml`)

```yaml
# yaml-language-server: $schema=./skernel.schema.json

skernel:
    version: 1.0

    scan:
        enabled: true
        paths:
            - app/
            - extend/
        exclude:
            - tests/
        classmapOutput: runtime/classmap.php

    build:
        binary: false
        phar:
            enabled: true
            stubFile: bin/skernel.stub.php
            output: dist/skernel.phar
```

- 🧩 skernel.schema.json中提供了完整的模式

## 📦 Phar Packaging

```bash
skernel package
```

- Output: target/skernel.phar

> > > > 改善中...