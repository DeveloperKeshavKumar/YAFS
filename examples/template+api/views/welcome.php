<?php
$title = $title ?? 'Welcome to YAFS';
$message = $message ?? null;

$content = <<<HTML
<div style="max-width: 800px; margin: 0 auto; padding: 2rem; font-family: system-ui;">
    <h1 style="color: #2563eb;">{$title}</h1>
HTML;

if ($message) {
    $content .= <<<HTML
    
    <p style="background: #dbeafe; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
        {$message}
    </p>
HTML;
}

$content .= <<<HTML
    
    <div style="background: #f8fafc; padding: 2rem; border-radius: 1rem; margin-top: 2rem;">
        <h2>Getting Started</h2>
        <ul style="line-height: 1.8;">
            <li>Edit routes in <code style="background: #e5e7eb; padding: 0.25rem 0.5rem;">routes/web.php</code></li>
            <li>Create views in <code style="background: #e5e7eb; padding: 0.25rem 0.5rem;">views/</code></li>
            <li>Add React: <code style="background: #e5e7eb; padding: 0.25rem 0.5rem;">php yafs add react</code></li>
        </ul>
    </div>
    
    <div style="margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 1rem;">
        <h3 style="margin-top: 0;">🚀 Quick Start</h3>
        <p>Your YAFS application is running successfully!</p>
        <p style="font-size: 0.9rem; opacity: 0.9;">Check out <code>routes/web.php</code> to add more routes.</p>
    </div>
</div>
HTML;

require __DIR__ . '/layouts/app.php';