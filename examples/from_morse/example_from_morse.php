<?php

/**
 * Example code from the "ImageMorseCodec" project
 * Copyright 2025 Oleh Kovalenko
 *
 * Licensed under the Apache License, Version 2.0
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Note: This is example/demo code. Use at your own risk ("AS IS").
 */

require_once __DIR__
    . DIRECTORY_SEPARATOR
    . '..'
    . DIRECTORY_SEPARATOR
    . '..'
    . DIRECTORY_SEPARATOR
    . 'src'
    . DIRECTORY_SEPARATOR
    . 'ImageMorseCodec.php';

use ImageMorseCodec\ImageMorseCodec;

$classImageMorseCodec = new ImageMorseCodec();

var_dump($classImageMorseCodec->fromMorse('example.txt'));