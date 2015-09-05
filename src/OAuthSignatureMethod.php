<?php
namespace PMVC\PlugIn\auth;

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class OAuthSignatureMethod {
  /**
   * Build up the signature
   * NOTE: The output of this function MUST NOT be urlencoded.
   * the encoding is handled in OAuthRequest when the final
   * request is serialized
   * @param OAuthRequest $request
   * @param OAuthConsumer $consumer
   * @param OAuthToken $token
   * @return string
   */
  abstract public function build_signature($request, $consumer, $token);
  /**
   * Verifies that a given signature is correct
   * @param OAuthRequest $request
   * @param OAuthConsumer $consumer
   * @param OAuthToken $token
   * @param string $signature
   * @return bool
   */
  public function check_signature($request, $consumer, $token, $signature) {
    $built = $this->build_signature($request, $consumer, $token);
    // Check for zero length, although unlikely here
    if (strlen($built) == 0 || strlen($signature) == 0) {
      return false;
    }
    if (strlen($built) != strlen($signature)) {
      return false;
    }
    // Avoid a timing leak with a (hopefully) time insensitive compare
    $result = 0;
    for ($i = 0; $i < strlen($signature); $i++) {
      $result |= ord($built{$i}) ^ ord($signature{$i});
    }
    return $result == 0;
  }

    public function get_signature_string($method,$url,$params) {
        ksort($params);
        $parts = array(
            $method,
            $url,
            http_build_query($params)
        );
        $parts = $this->urlencode_rfc3986($parts);
        return join('&', $parts);
    }

    public function urlencode_rfc3986($input)
    {
        if (is_array($input)) {
            return array_map(array($this, 'urlencode_rfc3986'), $input);
        } elseif (is_scalar($input)) {
            return str_replace(
                    '+',
                    ' ',
                    str_replace('%7E', '~', rawurlencode($input))
                    );
        } else {
            return '';
        }
    }
}
