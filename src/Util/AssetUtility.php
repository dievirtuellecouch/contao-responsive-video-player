<?php

namespace DVC\ResponsiveVideoPlayer\Util;

use Contao\StringUtil;

class AssetUtility
{
    public function __construct(
        private string $projectDir,
        private string $uploadPath,
        private string $webDir,
    ) {
    }

    /**
     * Returns the timestamp for the file at given path.
     * @param string The path to the file.
     * @return string|null The timestamp or null if the file does not exist.
     */
    public function getTimestampForFile(string $src): ?string
    {
        $mtime = null;

        if (file_exists($this->projectDir . '/' . $this->uploadPath . '/' . $src)) {
            $mtime = filemtime($this->projectDir . '/' . $this->uploadPath . '/' . $src);
        }
        else {
            $webDir = StringUtil::stripRootDir($this->webDir);

            // Handle public bundle resources in contao.web_dir
            if (file_exists($this->projectDir . '/' . $webDir . '/' . $this->uploadPath . '/' . $src)) {
                $mtime = filemtime($this->projectDir . '/' . $webDir . '/' . $this->uploadPath . '/' . $src);
            }
        }

        if ($mtime === null) {
            return null;
        }

        return substr(md5($mtime), 0, 8);
    }
}
