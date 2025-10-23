<?php

namespace YAFS\Http;

/**
 * The Response class helps handlers send HTTP responses.
 * 
 * It provides convenient methods for setting status codes,
 * headers, and sending JSON or HTML responses.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $body = null;

    /**
     * Set the HTTP status code.
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set a response header.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send a JSON response.
     */
    public function json($data): array
    {
        $this->header('Content-Type', 'application/json');
        return $data;
    }

    /**
     * Send an HTML response.
     */
    public function html(string $html): string
    {
        $this->header('Content-Type', 'text/html');
        return $html;
    }

    /**
     * Send plain text response.
     */
    public function text(string $text): string
    {
        $this->header('Content-Type', 'text/plain');
        return $text;
    }

    /**
     * Redirect to another URL.
     */
    public function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: $url");
        exit;
    }
}