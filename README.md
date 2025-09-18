# Image Morse Codec

![PHP Version](https://img.shields.io/badge/PHP-%3E=8.2-blue.svg?logo=php)
![PHP GD Extension](https://img.shields.io/badge/GD%20extension-required-orange)
![License](https://img.shields.io/badge/license-Apache%202.0-green.svg)
![Stand with Ukraine](https://img.shields.io/badge/Stand%20with-Ukraine-blue?style=flat&logo=flag-ukraine)

**Turn images into Morse code — and back again!** 📡✨  
Image Morse Codec is a lightweight PHP library that lets you encode every pixel of a PNG or JPEG image into Morse code and save it as a text file. You can also decode such files to reconstruct the original image.

⚠️ **Warning:** Processing large images may require a lot of RAM!

---

## ✨ Features
- ⚡ **Lightweight & fast**
- 🧩 **Simple API** — just two methods: `toMorse()` and `fromMorse()`
- 🔄 **Bidirectional** — convert images → text and text → images
- 🎨 **Supports PNG and JPEG (JPG)**

---

## ⚡ Requirements
- PHP **>= 8.2**
- PHP **GD extension** enabled

---

## 📖 Usage

### Encode image → Morse
```php
<?php

require_once('ImageMorseCodec/ImageMorseCodec.php');

use ImageMorseCodec\ImageMorseCodec;

$codec = new ImageMorseCodec();

// Instead of "example.png", provide the image name or full path
$codec->toMorse('example.png');
```

### Decode Morse → image
```php
<?php

require_once('ImageMorseCodec/ImageMorseCodec.php');

use ImageMorseCodec\ImageMorseCodec;

$codec = new ImageMorseCodec();

// Instead of "example.txt", provide the text file name or full path
$codec->fromMorse('example.txt');
```

---

## 👨‍💻 Author
- [Oleh Kovalenko](https://github.com/oleh-exe) — Owner & Maintainer

---

## 📜 License
[Apache 2.0](LICENSE)