# skernel - Super-Kernel Tool

## Introduction

`skernel` is a versatile tool designed to simplify the process of building PHP binaries and PHAR archives for Super
Kernel framework projects. It provides a simple command-line interface (CLI) to handle build tasks and supports a range
of options for different use cases.

---

## 🚀 Features

- **Executable Binaries**: Generates ready-to-run binaries for Super Kernel framework projects without the need for a
  PHP installation.
- **PHAR Archive Generation**: Generates PHAR archives for Super Kernel framework projects, simplifying deployment and
  distribution.
- **Autoloader**: The generated PHAR file includes an autoloader for seamless project deployment.
- **Class Diagrams and Annotations**: Automatically generates class diagrams and annotations for your project to improve
  maintainability and eliminate redundant work.
- **Skip File Parsing Errors**: Skips file parsing errors to avoid interruptions, especially when dealing with
  non-critical issues.
- **Loading Development Dependencies**: Allows you to load development dependencies to increase the flexibility of your
  development environment.

## ⚡ Performance Improvements

- Improved tool execution efficiency, reduced archive memory usage, and improved overall performance.
- Optimized the autoloader and class diagram generation process to provide faster and more accurate results.

## 🛠 Installation

```shell
curl -s https://api.github.com/repos/wheakerd/skernel/releases/latest | jq -r '.assets[] | select(.name | test("skernel$")) | .browser_download_url' | xargs -I {} curl -sL {} | sudo tee /usr/bin/skernel > /dev/null && sudo chmod 755 /usr/bin/skernel
```

## 📢 Usage

### Available Commands:

```text
build Builds a binary or PHAR archive.

Usage:
  build [options]

Options:
      --disable-binary  Disable binary build, only build the PHAR archive.
      --dev             Use `require-dev` requirements from `composer.json`.
      --debug           Enable debug mode to provide more detailed logs.
```

## 📋 License

The `skernel` is open-source software licensed under the MIT license.