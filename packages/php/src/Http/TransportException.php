<?php

namespace FlexaCms\Client\Http;

use RuntimeException;

/**
 * The request never got a response (DNS, timeout, refused connection...).
 */
class TransportException extends RuntimeException {}
