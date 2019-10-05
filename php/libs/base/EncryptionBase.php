<?php

abstract class EncryptionBase
{
    protected $encryptionExceptions;

    public function encryptString($text) {
        return $this->cryptString($text, true);
    }

    public function decryptString($text) {
        return $this->cryptString($text, false);
    }

    public function encryptArrayElement($key, $text) {
        if($text == NULL) return $text;
        return $this->cryptArrayElement($key, $text, true);
    }

    public function decryptArrayElement($key , $text) {
        return $this->cryptArrayElement($key, $text, false);
    }

    public function encryptArray($arr) {
        return $this->cryptArr($arr, true);
    }

    public function decryptArray($arr) {
        return $this->cryptArr($arr, false);
    }

    /*
     * Protected
     */
    protected abstract function cryptString($text, $encrypt);

    protected function cryptArrayElement($key, $element, $encrypt) {
        if(is_empty($element)) return $element;
        return ((!in_array($key, $this->encryptionExceptions)) ? $this->cryptString($element, $encrypt) : $element);
    }

    private function cryptArr($arg, $encrypt, $status = true) {
        $result = ($status) ? null : $arg;

        if($status) {
            if (is_array($arg)) {
                $result = array();
                $keys = array_keys($arg);

                foreach ($keys as $key) {
                    $element = $arg[$key];
                    if (is_array($element)) {
                        $sub_keys = array_keys($element);
                        foreach ($sub_keys as $sub_key) {
                            $result[$key][$sub_key] = $this->cryptArrayElement($sub_key, $element[$sub_key], $encrypt);
                        }
                    } else $result[$key] = $this->cryptArrayElement($key, $element, $encrypt);

                }

            } else {
                $result = $this->cryptString($arg, $encrypt);
            }
        }
        return $result;
    }
}