# 🖼️ PHP Image Converter (via Rust FFI)

This project demonstrates how to use **Rust** for high-performance image processing and expose it to **PHP** using **FFI (Foreign Function Interface)**.

It supports converting images between **JPG**, **PNG**, and **WEBP** formats.

---

## 📁 Project Structure

```
.
├── index.php                # PHP script with FFI integration
├── Makefile                 # Build system for Rust + directory setup
├── rustffi/                 # Rust crate (lib) for image conversion
├── lib/                     # (Generated) Holds the compiled Rust library
├── output/                  # (Generated) Stores converted images
└── .gitignore               # Ignores lib/ and output/
```

---

## ⚙️ Setup

### Requirements

- **PHP** ≥ 7.4 with FFI enabled
- **Rust** & Cargo
- `make` (Linux/macOS) or compatible on Windows
- `fzf` – for interactive image selection

📦 To install fzf:
  - macOS (Homebrew): brew install fzf
  - Arch Linux: pacman -S fzf
  - Debian/Ubuntu: sudo apt install fzf
  - others: [fzf_github](https://github.com/junegunn/fzf)

---

## 🚀 Build & Run

### Step 1: Compile the Rust Library

```bash
make
```

This command:
- Builds the Rust code from `rustffi/`
- Detects your OS and copies the correct `.so`, `.dll`, or `.dylib` into `lib/`
- Creates the `output/` directory if missing

---

### Step 2: Run the Converter

```bash
php index.php
```

You’ll be prompted to:
- Provide the path to your input image
- Select the output format (JPG/PNG/WEBP)
- Provide a name for the output file (without extension)

Converted images are saved inside the `output/` directory.

---

## 🧹 Clean Up

```bash
make clean
```

This deletes compiled files and output images.

---

## 💡 Example

```
Enter path to input image:
> /home/user/pics/dog.png

Choose output format:
0 => JPG
1 => PNG
2 => WEBP
Output format (0-2):
> 0

Enter the output file name (no-extension):
> dog_converted

✅ Image conversion successful!
```

---

## 🧠 How It Works

- Rust performs the image processing using the [`image`](https://docs.rs/image/) crate.
- The Rust function is exposed via `extern "C"` and compiled to a shared library.
- PHP uses `FFI::cdef()` to bind the `convert_image` function and call it natively.
- `fzf` enables interactive file selection within your home directory.

---

## 🛠 Troubleshooting

- **FFI not enabled in PHP?**  
  Enable it in `php.ini`:

  ```
  extension=ffi
  ffi.enable=true
  ```

- **Permission denied or file not found?**  
  Ensure:
  - Input file path is valid
  - `lib/librust_image_converter.*` exists after running `make`
  - `fzf` is installed and available in your `$PATH`

---

## 👨‍💻 Author

**Manish Acharya**  
🌐 [www.manishacharya.name.np](https://www.manishacharya.name.np)  
🔗 [GitHub](https://github.com/manish-ach) | [LinkedIn](https://linkedin.com/in/manish-acharya-08345a260)
