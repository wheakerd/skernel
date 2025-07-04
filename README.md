# Skernel

**Skernel** is a lightweight, phar-friendly PHP toolchain kernel designed for building, scanning, packaging, and running
PHP applications with speed, determinism, and developer ergonomics in mind.

> ⚡ Skernel focuses on early scanning, AOT class mapping, and phar bootstrapping.  
> 🧩 Designed for framework authors, CLI tools, and performance-sensitive runtime environments.

---

## ✨ Features

- ✅ Declarative configuration via `skernel.config.yaml` (with IDE schema support)
- 🧠 Static classmap scanning (PSR-4 / classmap / PSR-0)
- 📦 Phar packaging with custom `.stub` support
- 🚀 Optimized startup with zero Composer runtime dependency
- 🔍 Optional AOP / proxy generation pre-run
- 🛠️ Integrates into any PHP project as a build step or CLI tool

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

- 🧩 Complete schema available in skernel.schema.json

## 📦 Phar Packaging

```bash
skernel package
```

- Output: target/skernel.phar

> > > > Under improvement...