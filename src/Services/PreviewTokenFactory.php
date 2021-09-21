<?php


namespace SilverStripe\NextJS\Services;


use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use InvalidArgumentException;
use Firebase\JWT\JWT;

class PreviewTokenFactory
{
    use Injectable;
    use Configurable;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     * @config
     */
    private static $expires = '30';

    /**
     * @var string
     * @config
     */
    private static $algorithm = 'HS256';

    /**
     * Cache a map of links to tokens to ensure one per link per request
     * @var array
     */
    private static $tokens = [];

    /**
     * PreviewTokenFactory constructor.
     * @param string $secret
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param DataObject $obj
     */
    public function createToken(DataObject $obj)
    {
        if (!$obj->hasMethod('Link')) {
            throw new InvalidArgumentException(sprintf(
                'Cannot create token for %s object without a Link() method',
                get_class($obj)
            ));
        }

        $existing = self::$tokens[$obj->Link()] ?? null;
        if ($existing) {
            return $existing;
        }

        $link = $obj->Link();
        $payload = [
            'link' => $link,
            'expiry' => time() + ($this->config()->get('expires') * 60),
        ];

        $token = JWT::encode(
            $payload,
            $this->secret,
            $this->config()->get('algorithm')
        );

        self::$tokens[$link] = $token;

        return $token;
    }
}
