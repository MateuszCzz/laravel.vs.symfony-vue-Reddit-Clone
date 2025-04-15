<?php

namespace App\Bitwise;

abstract class BitwiseFlag
{
    protected int $value;

    public function __construct(int $value = 0)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Check if a specific flag is set
     * @param int $flag The flag to check
     * @return bool True if flag is set, false otherwise
     */
    public function hasFlag(int $flag): bool
    {
        return ($this->value & $flag) === $flag;
    }

    /**
     * Set or unset a specific flag
     * @param int $flag The flag to modify
     * @param bool $value True to set the flag, false to unset it
     * @return self
     */
    public function setFlag(int $flag, bool $value): self
    {
        if ($value) {
            $this->value |= $flag;
        } else {
            $this->value &= ~$flag;
        }
        return $this;
    }

    /**
     * Set or unset all available flags
     * @param bool $value True to set all flags, false to unset all
     * @return self
     */
    public function setAllFlags(bool $value): self
    {
        $flags = static::getFlags();
        foreach ($flags as $flagInfo) {
            $this->setFlag($flagInfo['value'], $value);
        }
        return $this;
    }

    /**
     * Should return an array of all flags and metadata
     * 
     * Format:
     * [
     *     'flag_key_name' => [
     *         'value' => int,
     *         'description' => string
     *     ],
     * ]
     * @return array
     */
    abstract public static function getFlags(): array;
}