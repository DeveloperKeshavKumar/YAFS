<?php

namespace YAFS\Http;

use YAFS\View\View;

/**
 * HTTP Response wrapper.
 * 
 * Provides methods for setting status codes, headers, and response body.
 * Supports JSON, HTML, text, and view responses.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $body = null;

    /**
     * Set HTTP status code.
     * 
     * @param int $code HTTP status code
     * @return self
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        http_response_code($code);
        return $this;
    }

    /**
     * Set response header.
     * 
     * @param string $name Header name
     * @param string $value Header value
     * @return self
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send JSON response.
     * 
     * @param mixed $data Data to encode as JSON
     * @return array
     */
    public function json($data): array
    {
        $this->header('Content-Type', 'application/json');
        return $data;
    }

    /**
     * Send HTML response.
     * 
     * @param string $html HTML content
     * @return string
     */
    public function html(string $html): string
    {
        $this->header('Content-Type', 'text/html');
        return $html;
    }

    /**
     * Send plain text response.
     * 
     * @param string $text Text content
     * @return string
     */
    public function text(string $text): string
    {
        $this->header('Content-Type', 'text/plain');
        return $text;
    }

    /**
     * Render a view template.
     * 
     * @param string $view View name (e.g., 'home' or 'users/profile')
     * @param array $data Data to pass to view
     * @return string Rendered HTML
     */
    public function view(string $view, array $data = []): string
    {
        $html = View::render($view, $data);
        return $this->html($html);
    }

    /**
     * Redirect to another URL.
     * 
     * @param string $url Destination URL
     * @param int $code HTTP status code (default: 302)
     * @return void
     */
    public function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: $url");
        exit;
    }

    /**
     * Get current status code.
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get all headers.
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
