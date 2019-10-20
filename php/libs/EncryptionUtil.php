<?php
class EncryptionUtil extends EncryptionBase implements IEncryptionUtil
{
    private
        $encryptionSalt,
        $encryptMethod,
        $iv;

    /*
     * Public
     */
    public function __construct(
        $method = NEW_ENCRYPTION_METHOD,
        $salt = NEW_ENCRYPTION_SALT,
        $iv = NEW_ENCRYPTION_IV,
        $exceptions = NEW_ENCRYPTION_EXCEPTIONS
    )
    {
        $this->encryptionSalt = $salt;
        $this->encryptMethod = $method;
        $this->iv = $iv;
        $this->encryptionExceptions = explode(" ", $exceptions);
    }

    protected function cryptString($text, $encrypt)
    {
        $result = null;
        $iv = base64_decode($this->iv);
        if ($encrypt) {
            $result = trim(base64_encode(openssl_encrypt($text, $this->encryptMethod, $this->encryptionSalt, OPENSSL_RAW_DATA, $iv))); //,$iv
        } else {
            $decodeText = base64_decode($text);
            $result = trim((openssl_decrypt($decodeText, $this->encryptMethod, $this->encryptionSalt, OPENSSL_RAW_DATA, $iv)));
        }
        return $result;
    }
}
?>