<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'YAFS App') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f9fafb;
            color: #1f2937;
        }
        code {
            font-family: "Courier New", monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?= $content ?? '' ?>
</body>
</html>