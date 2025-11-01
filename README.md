<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

<div align="center">

# 🧠 skernel

**Official Build & Bootstrap Tool for the Super-Kernel Framework**

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4-777bb4.svg?logo=php)](https://www.php.net)
[![Super-Kernel](https://img.shields.io/badge/framework-super--kernel-blue.svg)](https://github.com/wheakerd/super-kernel)
[![Platform](https://img.shields.io/badge/platform-linux-lightgrey.svg?logo=linux)](https://www.kernel.org)

> Build with consistency, execute with determinism, eliminate chaos.  
> **skernel is the official build and bootstrap tool for the Super-Kernel framework.**

</div>

---

## 🧩 Introduction

`skernel` is the **dedicated build and bootstrap tool** for the **Super-Kernel framework**, designed to:

- **Unify execution forms** – every execution must originate from a build artifact.
- **Enforce strict development discipline** – purely OOP / AOP, no procedural code.
- **Eliminate runtime uncertainty** – all logic is resolved during the build phase.

It generates:

- ⚙️ Executable PHP binaries
- 📦 PHAR archives

---

## 🧭 Core Principles

- **No runtime code generation**  
  All logic and metadata must be finalized at build time. Runtime code creation or modification is forbidden.

- **OOP / AOP oriented**  
  Only object-oriented and aspect-oriented design is allowed. Procedural logic and global states are prohibited.

- **Unified bootstrap flow**  
  Every execution must pass through the `skernel` bootstrap process.  
  Manual control over entrypoints, configurations, or loaders is disallowed.

- **Strict PHP configuration boundaries**  
  PHP behaviors must be configured via `php.ini`.  
  Any runtime configuration changes (`ini_set()`, `error_reporting()`, etc.) are considered violations.

- **Super-Kernel exclusive support**  
  `skernel` performs automatic classmap scanning, annotation collection, AOP weaving, loader generation, and build
  integrity verification during build time.

- **Annotation stripping & trace optimization**  
  All annotations are removed from build outputs to minimize size and improve performance.  
  Exceptions and logs trace class names, not file paths.

---

## ⚙️ Features

| Feature                            | Description                                                  |
|------------------------------------|--------------------------------------------------------------|
| 🧱 **Unified Build Output**        | Build executable binaries and PHAR archives                  |
| 🔗 **Automatic Bootstrap**         | Generate unified entrypoint and loader                       |
| 🧭 **AOP Support**                 | Automatic annotation scanning and aspect weaving             |
| 🪶 **Annotation Stripping**        | Reduce size and improve execution speed                      |
| 💥 **Configuration Isolation**     | All settings are managed through php.ini                     |
| 🧩 **Classmap & Dependency Graph** | Automatically generate class diagrams and dependency indexes |
| 🛡 **Secure & Controlled**         | Prevent runtime code injection and configuration changes     |

---

## ⚡ Performance Optimizations

- Significant performance improvements in build phase
- Reduced PHAR memory usage
- Parallelized classmap and scanning mechanisms
- Fault-tolerant build pipeline — continues even on non-critical errors

---

## 🧰 Installation

### Install from GitHub Releases

Visit:

👉 [**wheakerd/skernel - Releases**](https://github.com/wheakerd/skernel/releases)

Download the binary that matches your architecture (e.g. `skernel-x86_64` or `skernel-aarch64`), then run:

```bash
chmod +x skernel
sudo mv skernel /usr/local/bin/
```

Verify installation:

```bash
skernel
```

## 📢 Example

```text
build Builds a binary or PHAR archive.

Usage:
  build [options]

Options:
      --disable-binary  Disable binary build, only build the PHAR archive.
      --dev             Use `require-dev` requirements from `composer.json`.
      --debug           Enable debug mode for detailed logs.
```

## 📋 License

This project is released under the MIT License.
You are free to use, modify, and distribute it, provided that the original license notice is retained.

## 💡 Philosophy

> “Above the framework, there shall be no chaos.”
>
> `skernel` is not merely a build tool — it is the guardian of the Super-Kernel worldview.
>
> It rejects dynamics and uncertainty, leaving behind a reproducible, verifiable, and deterministic runtime.
>
> Any logic not permitted by the build artifact is beyond the boundary.

<div align="center">
🌀 skernel is developed and maintained by <a href="https://github.com/wheakerd" target="_blank">wheakerd</a>.
<br />
Visit <a href="https://github.com/wheakerd/skernel" target="_blank">https://github.com/wheakerd/skernel</a> for more information.
</div>