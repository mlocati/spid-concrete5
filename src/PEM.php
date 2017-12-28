<?php

namespace SPID;

/**
 * PEM-Related service.
 */
class PEM
{
    /**
     * PEM KIND: private key.
     *
     * @var string
     */
    const KIND_PRIVATEKEY = 'RSA PRIVATE KEY';

    /**
     * PEM KIND: X.509 certificate.
     *
     * @var string
     */
    const KIND_X509CERTIFICATE = 'CERTIFICATE';

    /**
     * Take a PEM string (optional header and footer, optional multiline) and builds a single-line representation of it.
     *
     * @param string $value the data to be formatted
     *
     * @return string
     */
    public function simplify($value)
    {
        if (is_string($value)) {
            $value = trim($value);
            $value = str_replace("\t", ' ', str_replace("\r", "\n", str_replace("\r\n", "\n", $value)));
            if (preg_match('/^--+ *BEGIN [\w ]+ *--+\n+(.+)\n+--+ *END [\w ]+ *--+$/s', $value, $matches)) {
                $value = $matches[1];
            }
            $value = trim(preg_replace('/\s+/s', '', $value));
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * Take a PEM string (optional header and footer, optional multiline) and builds a multi-line representation of it.
     *
     * @param string $value the data to be formatted
     * @param string $kind One of the PEM::KIND_... constants.
     *
     * @return string
     */
    public function format($value, $kind)
    {
        $value = $this->simplify($value);
        if ($value !== '') {
            $value = "-----BEGIN {$kind}-----\n" . implode("\n", str_split($value, 64)) . "\n-----END {$kind}-----";
        }

        return $value;
    }
}
