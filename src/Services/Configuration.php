<?php


namespace SilverStripe\NextJS\Services;

use SilverStripe\Core\Injector\Injectable;

class Configuration
{
    use Injectable;
    /**
     * @var string | null
     */
    private $baseURL;

    /**
     * @var string | null
     */
    private $previewKey;

    /**
     * @var string
     */
    private $previewEndpoint = 'api/preview';

    /**
     * @return string|null
     */
    public function getBaseURL(): ?string
    {
        return $this->baseURL;
    }

    /**
     * @param string|null $baseURL
     * @return Configuration
     */
    public function setBaseURL(?string $baseURL): Configuration
    {
        $this->baseURL = $baseURL;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreviewKey(): ?string
    {
        return $this->previewKey;
    }

    /**
     * @param string|null $previewKey
     * @return Configuration
     */
    public function setPreviewKey(?string $previewKey): Configuration
    {
        $this->previewKey = $previewKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getPreviewEndpoint(): string
    {
        return $this->previewEndpoint;
    }

    /**
     * @param string $previewEndpoint
     * @return Configuration
     */
    public function setPreviewEndpoint(string $previewEndpoint): Configuration
    {
        $this->previewEndpoint = $previewEndpoint;
        return $this;
    }

    /**
     * @param string $token
     * @param string $link
     * @return string
     */
    public function getPreviewLink(string $token, string $link): string
    {
        return sprintf(
            '%s/%s?token=%s&slug=%s',
            $this->getBaseURL(),
            $this->getPreviewEndpoint(),
            $token,
            $link
        );

    }

}
