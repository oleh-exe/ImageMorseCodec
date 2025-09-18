<?php

/**
 * Copyright 2025 Oleh Kovalenko
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ImageMorseCodec;

use GdImage;

class ImageMorseCodec
{
    /**
     * Information about the path to the file.
     *
     * @var array
     */
    private array $pathParts;

    /**
     * Image width.
     *
     * @var int
     */
    private int $imageWidth;

    /**
     * Image height.
     *
     * @var int
     */
    private int $imageHeight;

    /**
     * Image file extension.
     *
     * @var string
     */
    private string $imageFileExtension;

    /**
     * Image file formats and their corresponding numeric codes.
     *
     * @var array
     */
    private const IMAGE_FORMAT_CODES = [
        'png' => 0,
        'jpeg' => 1,
    ];

    /**
     * Image file format encoded with a numeric code.
     *
     * @var int
     */
    private int $imageFileFormat;

    /**
     * GdImage instance or "false" on error.
     *
     * @var GdImage|false
     */
    private GdImage|false $image;

    /**
     * The starting x-coordinate of the pixel.
     * The count starts from the upper left corner of the image.
     *
     * @var int
     */
    private const X = 0;

    /**
     * The starting y-coordinate of the pixel.
     * The count starts from the upper left corner of the image.
     *
     * @var int
     */
    private const Y = 0;

    /**
     * Digits in Morse.
     *
     * @var array
     */
    private const DIGITS_IN_MORSE = [
        '-----', // 0
        '.----', // 1
        '..---', // 2
        '...--', // 3
        '....-', // 4
        '.....', // 5
        '-....', // 6
        '--...', // 7
        '---..', // 8
        '----.', // 9
    ];

    /**
     * Separator between digits of a number.
     *
     * @var string
     */
    private const DIGIT_SEPARATOR = ' ';

    /**
     * Separator between numbers.
     *
     * @var string
     */
    private const NUMBER_SEPARATOR = ' / ';

    /**
     * The function takes as an argument the path to the image file, uses the methods of this class
     * to retrieve the color indices of all the pixels in the image, encodes these indices into Morse code,
     * and saves the encoded indices to a text file.
     * The function returns true if the text file was successfully created, otherwise it returns false.
     *
     * @param string $pathToFile
     * @return bool
     */
    public function toMorse(string $pathToFile): bool
    {
        $result = false;

        if ($this->file($pathToFile, 'image') === true) {
            $this->getInformationAboutImage($pathToFile);
            $this->createImageFrom($pathToFile);
            $indexes = $this->getImagePixelColorIndexes();
            $indexesInMorse = $this->convertPixelColorIndicesToMorse($indexes);
            $imageInformation = $this->encodeImageInformationIntoMorseCode();
            $result = $this->saveFile($indexesInMorse, $imageInformation);
        }

        return $result;
    }

    /**
     * The function takes as an argument the path to a text file that contains the color indices of all the pixels
     * in the image, encoded in Morse code, and uses the methods of this class to create an image
     * based on the file’s contents.
     * The function returns true if the image was successfully created, otherwise it returns false.
     *
     * @param string $pathToFile
     * @return bool
     */
    public function fromMorse(string $pathToFile): bool
    {
        $result = false;

        if ($this->file($pathToFile, 'text') === true) {
            $fileContents = $this->getFileContents($pathToFile);
            $fileContents = $this->createNewImage($fileContents);
            $indexesFromMorse = $this->getColorIndicesFromMorse($fileContents);
            $pixelColors = $this->getColorOfPixelsCorrespondingToIndex($indexesFromMorse);
            $colorIDs = $this->getPixelColorIds($pixelColors);
            $this->saveImage($colorIDs);

            $result = true;
        }

        return $result;
    }

    /**
     * The function takes a file path and its format as arguments, checks whether the file exists,
     * and whether it is an image or a text file.
     * The function returns true if the file exists and has the correct type; otherwise — false.
     *
     * @param string $pathToFile
     * @param string $fileFormat
     * @return bool
     */
    private function file(string $pathToFile,
                          string $fileFormat): bool
    {
        $allowedImageSubtypes = [
            'png',
            'jpeg'
        ];
        $allowedTextSubtypes = [
            'plain'
        ];
        $result = false;
        /* Check the availability of the file
           and that a file is a file. */
        if (is_file($pathToFile)) {
            /* Get information about the file path.
               Example: [
                         'dirname' => '.',
                         'basename' => 'test.png',
                         'extension' => 'png',
                         'filename' => 'test'
                        ]
            */
            $this->pathParts = pathinfo($pathToFile);
            // Get MIME type.
            $mimeType = mime_content_type($pathToFile); // Example: "image/png" or "text/plain".
            // Split the MIME type.
            $typeAndSubtype = explode('/', $mimeType);
            $type = $typeAndSubtype[0]; // Example: "image" or "text".
            $subtype = $typeAndSubtype[1]; // Example: "png" or "plain".
            // Check that the file is an image file.
            if (
                $type === $fileFormat
                && in_array($subtype, $allowedImageSubtypes)
            ) {
                $result = true;
                // Check that the file is a text file.
            } elseif (
                $type === $fileFormat
                && in_array($subtype, $allowedTextSubtypes)
            ) {
                $result = true;
            }

        }

        return $result;
    }

    /**
     * The function takes the path to the image file as an argument
     * and assigns information about the image to the class properties.
     *
     * @param string $pathToImage
     * @return void
     */
    private function getInformationAboutImage(string $pathToImage): void
    {
        /* Get information about the image.
           Example: [
                     0 => 10,
                     1 => 10,
                     2 => 3,
                     3 => 'width="10" height="10"',
                     'bits' => 8,
                     'mime' => 'image/png'
                    ]
        */
        $imageSize = getimagesize($pathToImage);
        // Get the width of the image.
        $this->imageWidth = $imageSize[0]; // Example: "10".
        // Get the height of the image.
        $this->imageHeight = $imageSize[1]; // Example: "10".
        // Split the MIME type. Get the image file extension.
        $this->imageFileExtension = explode('/', $imageSize['mime'])[1]; // Example: "png".
        // Get the numeric code of the image file format.
        $this->imageFileFormat = self::IMAGE_FORMAT_CODES[$this->imageFileExtension]; // Example: "0".
    }

    /**
     * The function takes as an argument the path to the image file
     * and creates a new image from the file.
     * The function assigns either the image object or false to the class property.
     *
     * @param string $pathToImage
     * @return void
     */
    private function createImageFrom(string $pathToImage): void
    {
        switch ($this->imageFileExtension) {
            case 'png':
                $this->image = imagecreatefrompng($pathToImage); // Example: "GdImage" or "false".
                break;
            case 'jpeg':
                $this->image = imagecreatefromjpeg($pathToImage); // Example: "GdImage" or "false".
                break;
        }
    }

    /**
     * The function iterates through each pixel of the image and gets the color index of that pixel
     * and stores the index in an array.
     * The function returns an array containing the color indices of all pixels in the image.
     *
     * @return array
     */
    private function getImagePixelColorIndexes(): array
    {
        $expr = true;
        $x = self::X;
        $y = self::Y;
        $imgWidth = $this->imageWidth - 1; // The count starts from zero.
        $imgHeight = $this->imageHeight - 1; // The count starts from zero.
        $indexes = array();

        while ($expr) {
            // Get the pixel color index.
            $index = imagecolorat($this->image, $x, $y); // Example: "16777215".
            $indexes[] = $index;
            /* If the image width limit is not reached,
               move one pixel to the right. */
            if ($x < $imgWidth) {
                $x++;
                /* If the image width limit is reached,
                   move down one pixel. */
            } elseif ($x == $imgWidth) {
                $x = 0;
                $y++;
            }
            /* If the image height limit is exceeded,
               break the loop. */
            if ($y > $imgHeight) {
                $expr = false;
            }

        }

        return $indexes;
    }

    /**
     * The function takes as an argument an array containing the color indices of all pixels in the image,
     * iterates through each element of the array, and encodes its contents from plain text into Morse code.
     * The function returns an array containing the color indices of all pixels in the image,
     * encoded in Morse code.
     *
     * @param array $indexes
     * @return array
     */
    private function convertPixelColorIndicesToMorse(array $indexes): array
    {
        return $this->encodeToMorse($indexes);
    }

    /**
     * The function encodes image file information — its format (as a numeric code)
     * and size (width and height) — into Morse code.
     * The function returns an array containing information about the image encoded in Morse code.
     *
     * @return array
     */
    private function encodeImageInformationIntoMorseCode(): array
    {
        return $this->encodeToMorse([
            'format' => $this->imageFileFormat,
            'width' => $this->imageWidth,
            'height' => $this->imageHeight
        ]);
    }

    /**
     * The function takes as an argument an array containing the data to be encoded into Morse code,
     * iterates through each element of the array, and encodes its content from plain text into Morse code.
     * The function returns an array containing the data encoded in Morse code.
     *
     * @param array $data
     * @return array
     */
    private function encodeToMorse(array $data): array
    {
        /* New array:
           [
            '-----' => 0,
            '.----' => 1,
            '..---' => 2,
            '...--' => 3,
            '....-' => 4,
            '.....' => 5,
            '-....' => 6,
            '--...' => 7,
            '---..' => 8,
            '----.' => 9
           ]
        */
        $digits = array_flip(self::DIGITS_IN_MORSE);

        $toMorse = function ($value) use ($digits) {
            /* Encodes the value into Morse code.
               Example: ".-----....--...--...--.....---.----.....". */
            return str_replace($digits, self::DIGITS_IN_MORSE, $value);
        };

        return array_map($toMorse, $data);
    }

    /**
     * The function takes two arguments: an array containing the color indices of all the pixels
     * in the image (encoded in Morse code) and the image file information (also encoded in Morse code).
     * The function writes the image file information and the color indices to a text file.
     *
     * @param array $data Indexes in Morse.
     * @param array $imageInformation
     * @return bool
     */
    private function saveFile(array $data,
                              array $imageInformation): bool
    {
        // Add image file information to the beginning of the array.
        array_unshift(
            $data,
            $imageInformation['format'],
            $imageInformation['width'],
            $imageInformation['height']
        );

        $arrayLength = (count($data) - 1);
        $fullPathToFile = $this->pathParts['dirname']
            . DIRECTORY_SEPARATOR
            . $this->pathParts['filename']
            . '.txt'; // Example: "./test.txt".
        $result = true;

        foreach ($data as $key => $value) {
            /* Converting a string to an array.
               Example of a new array: [
                                        0 => ".----",
                                        1 => "-....",
                                        2 => "--...",
                                        3 => "--...",
                                        4 => "--...",
                                        5 => "..---",
                                        6 => ".----",
                                        7 => "....."
                                       ]
            */
            $value = str_split($value, 5);
            // Creating a string from an array.
            $string = implode(
                self::DIGIT_SEPARATOR,
                $value
            ); // Example: ".---- -.... --... --... --... ..--- .---- .....".
            // The last line must not contain a forward slash symbol and an "end of line" symbol.
            if ($arrayLength !== $key) {
                $string .= self::NUMBER_SEPARATOR . PHP_EOL;
            }
            // Writing a Morse-encoded string to a file.
            if (file_put_contents($fullPathToFile, $string, FILE_APPEND) === false) {
                $result = false;
                break;
            }

        }

        return $result;
    }

    /**
     * The function takes as an argument the path to a text file and receives its contents
     * and creates an array from its contents.
     * The function returns an array containing the image file information (encoded in Morse code)
     * and the color indices of all its pixels (also encoded in Morse code).
     *
     * @param string $pathToFile
     * @return array
     */
    private function getFileContents(string $pathToFile): array
    {
        $emptyString = "";
        $space = " ";
        $fileContents = file_get_contents($pathToFile);
        /* Replacing the space " " with an empty string "",
           and then replacing the "end of line" symbol with a space " ". */
        $fileContents = str_replace(
            [
                self::NUMBER_SEPARATOR,
                self::DIGIT_SEPARATOR,
                "\r\n",
                "\n",
                "\r"
            ],
            [
                $emptyString,
                $emptyString,
                $space,
                $space,
                $space
            ],
            $fileContents
        );

        return explode(' ', $fileContents);
    }

    /**
     * The function takes as an argument an array, containing image file information (encoded in Morse code)
     * and the color indices of all its pixels (also encoded in Morse code).
     * It decodes the image file information and creates a new image using this information.
     * The function returns an array that no longer contains the image file information.
     *
     * @param array $fileContents
     * @return array
     */
    private function createNewImage(array $fileContents): array
    {
        // Decoding image file information.
        $imageInformation = $this->decodeFromMorse([$fileContents[0], $fileContents[1], $fileContents[2]]);
        unset($fileContents[0], $fileContents[1], $fileContents[2]);
        /* Example of a new array:
           [
            0 => 'png',
            1 => 'jpeg',
           ]
        */
        $imageFormats = array_flip(self::IMAGE_FORMAT_CODES);
        // Getting the numerical code of the image file format.
        $imageFileFormat = intval($imageInformation[0]); // Example: "0".
        // Getting the image file extension.
        $this->imageFileExtension = $imageFormats[$imageFileFormat]; // Example: "png".
        // Getting the width of the image.
        $this->imageWidth = intval($imageInformation[1]); // Example: "10".
        // Getting the height of the image.
        $this->imageHeight = intval($imageInformation[2]); // Example: "10".
        // Creating a new image.
        $this->image = imagecreatetruecolor($this->imageWidth, $this->imageHeight); // Example: "GdImage" or "false".

        return array_values($fileContents);
    }

    /**
     * The function takes as an argument an array containing the color indices of all the pixels in the image,
     * encoded in Morse, iterates through each element of the array and decodes its contents from Morse
     * into plain text.
     * The function returns an array containing the color indices of all pixels in the image.
     *
     * @param array $fileContents
     * @return array
     */
    private function getColorIndicesFromMorse(array $fileContents): array
    {
        return $this->decodeFromMorse($fileContents);
    }

    /**
     * The function takes as an argument an array containing the data to be decoded from Morse code,
     * iterates through each element of the array, and decodes its content from Morse code into plain text.
     * The function returns an array containing the data in plain text.
     *
     * @param array $data
     * @return array
     */
    private function decodeFromMorse(array $data): array
    {
        /* New array:
           [
            '-----' => 0,
            '.----' => 1,
            '..---' => 2,
            '...--' => 3,
            '....-' => 4,
            '.....' => 5,
            '-....' => 6,
            '--...' => 7,
            '---..' => 8,
            '----.' => 9
           ]
        */
        $digits = array_flip(self::DIGITS_IN_MORSE);

        $fromMorse = function ($morseString) use ($digits) {
            $i = 0;
            $decodedString = '';

            while ($i !== false) {
                // Getting part of the value of an array element.
                $tmp = substr($morseString, $i, 5); // Example: "-----".

                if (!empty($tmp)) {
                    // Decoding a string from Morse code into plain text.
                    $decodedString .= str_replace(self::DIGITS_IN_MORSE, $digits, $tmp); // Example: "0".
                    $i += 5;
                } else {
                    $i = false;
                }

            }

            return $decodedString;
        };

        return array_map($fromMorse, $data);
    }

    /**
     * The function takes as an argument an array containing the color indices of all pixels in the image,
     * and iterates through each index and gets the color of the pixel from the index.
     * The function returns an array containing the colors of all pixels in the image.
     *
     * @param array $indexes
     * @return array
     */
    private function getColorOfPixelsCorrespondingToIndex(array $indexes): array
    {
        $pixelColors = array();

        foreach ($indexes as $index) {
            /* Get colors corresponding to the index.
               Example: [
                         'red' => 255,
                         'green' => 255,
                         'blue' => 255,
                         'alpha' => 0
                        ]
            */
            $rgba = imagecolorsforindex($this->image, $index);
            $pixelColors[] = $rgba;
        }

        return $pixelColors;
    }

    /**
     * The function takes as an argument an array containing the colors of all the pixels in the image,
     * and iterates through each color and gets the color ID from the pixel color.
     * The function returns an array containing the color IDs of all the pixels in the image.
     *
     * @param array $pixelColors
     * @return array
     */
    private function getPixelColorIds(array $pixelColors): array
    {
        $colorIDs = array();

        foreach ($pixelColors as $v) {
            /* Get the color identifier.
               Example: "16777215". */
            $colorID = imagecolorallocatealpha(
                $this->image,
                $v['red'],
                $v['green'],
                $v['blue'],
                $v['alpha']
            );
            $colorIDs[] = $colorID;
        }

        return $colorIDs;
    }

    /**
     * The function takes as an argument an array containing the color IDs of all the pixels in the image,
     * and iterates through each ID and uses it to draw each pixel in the image.
     * The function creates and saves the image to a file.
     *
     * @param array $colorIDs
     * @return void
     */
    private function saveImage(array $colorIDs): void
    {
        $expr = true;
        $x = self::X;
        $y = self::Y;
        $i = 0;
        $imgWidth = $this->imageWidth - 1; // The count starts from zero.
        $imgHeight = $this->imageHeight - 1; // The count starts from zero.
        $imageFileExtension = $this->imageFileExtension;
        $fullPathToImage = $this->pathParts['dirname']
            . DIRECTORY_SEPARATOR
            . $this->pathParts['filename']
            . '.'
            . $imageFileExtension; // Example: "./example.png".

        while ($expr) {
            // Drawing a pixel at the given coordinates.
            imagesetpixel($this->image, $x, $y, $colorIDs[$i]);
            /* If the image width limit is not reached,
               move one pixel to the right. */
            if ($x < $imgWidth) {
                $x++;
                /* If the image width limit is reached,
                   move down one pixel. */
            } elseif ($x == $imgWidth) {
                $x = 0;
                $y++;
            }
            /* If the image height limit is exceeded,
               break the loop. */
            if ($y > $imgHeight) {
                $expr = false;
            }

            $i++;
        }

        switch ($imageFileExtension) {
            case 'png':
                // Saving the PNG image.
                imagepng($this->image, $fullPathToImage, 0);
                break;
            // Saving the JPEG image.
            case 'jpeg':
                imagejpeg($this->image, $fullPathToImage, 100);
                break;
        }
    }
}