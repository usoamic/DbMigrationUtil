<?php
class EncryptionLegacyUtil extends EncryptionBase implements IEncryptionUtil {
    private
        $encryptionSalt;

    /*
     * Public
     */
    public function __construct(
        $salt = OLD_ENCRYPTION_SALT,
        $exceptions = OLD_ENCRYPTION_EXCEPTIONS
    ) {
        $this->encryptionSalt = $salt;
        $this->encryptionExceptions = explode(" ", $exceptions);
    }

    protected function cryptString($text, $encrypt) {
        if($encrypt) {
            return trim(
                base64_encode(
                    mcrypt_encrypt(
                        MCRYPT_RIJNDAEL_256,
                        $this->encryptionSalt,
                        $text,
                        MCRYPT_MODE_ECB,
                        mcrypt_create_iv(
                            mcrypt_get_iv_size(
                                MCRYPT_RIJNDAEL_256,
                                MCRYPT_MODE_ECB
                            ),
                            MCRYPT_RAND
                        )
                    )
                )
            );
        }
        else {
            return trim(
                mcrypt_decrypt(
                    MCRYPT_RIJNDAEL_256,
                    $this->encryptionSalt,
                    base64_decode($text),
                    MCRYPT_MODE_ECB,
                    mcrypt_create_iv(
                        mcrypt_get_iv_size(
                            MCRYPT_RIJNDAEL_256,
                            MCRYPT_MODE_ECB),
                        MCRYPT_RAND
                    )
                )
            );
        }
    }
}

?>