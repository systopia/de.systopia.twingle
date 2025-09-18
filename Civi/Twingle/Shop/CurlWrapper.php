<?php

declare(strict_types = 1);

namespace Civi\Twingle\Shop;

/**
 * A simple wrapper for the cURL functions to allow for easier testing.
 */
class CurlWrapper {

  public function init($url) {
    return curl_init($url);
  }

  public function setopt($ch, $option, $value) {
    return curl_setopt($ch, $option, $value);
  }

  public function exec($ch) {
    return curl_exec($ch);
  }

  public function getinfo($ch, $option) {
    return curl_getinfo($ch, $option);
  }

  public function close($ch) {
    curl_close($ch);
  }

}
