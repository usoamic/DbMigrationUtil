<?php

interface IEncryptionUtil
{
    public function encryptString($text);
    public function decryptString($text);
    public function encryptArrayElement($key, $text);
    public function decryptArrayElement($key , $text);
    public function encryptArray($arr);
    public function decryptArray($arr);
}