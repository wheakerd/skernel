<?php
declare(strict_types=1);

namespace SuperKernel\Parser\Exception;

use Exception;

/**
 * @FileSystemException
 * @\SuperKernel\Parser\Exception\FileSystemException
 */
final class FileSystemException extends Exception
{
    /**
     * Define constants representing different error types.
     * @formatter:off
     */
    const int FILE_NOT_FOUND    = 1;    // File not found
    const int DIR_NOT_FOUND     = 2;    // Directory not found
    const int FILE_NOT_READABLE = 3;    // File not readable
    const int DIR_NOT_READABLE  = 4;    // Directory not readable
    const int DIR_NOT_WRITABLE  = 5;    // Directory not writable
    /** @formatter:on */

    /**
     * @param int $code Error code, which must be one of the following constants:
     *                          - FileSystemException::FILE_NOT_FOUND
     *                          - FileSystemException::DIR_NOT_FOUND
     *                          - FileSystemException::FILE_NOT_READABLE
     *                          - FileSystemException::DIR_NOT_READABLE
     *                          - FileSystemException::DIR_NOT_WRITABLE
     * @param string $message Error message
     */
    public function __construct(int $code, string $message)
    {
        /** @formatter:off */
        $message = sprintf(
            match ($code) {
                self::FILE_NOT_FOUND    => 'File "%s" not found',
                self::DIR_NOT_FOUND     => 'Directory "%s" not found',
                self::FILE_NOT_READABLE => 'File is not readable',
                self::DIR_NOT_READABLE  => 'Directory "%s" is unreadable',
                self::DIR_NOT_WRITABLE  => 'Directory "%s" is not writable',
            },
            $message,
        );
        /** @formatter:on */

        parent::__construct($message);
    }
}