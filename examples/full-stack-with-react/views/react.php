<?php use YAFS\View\AssetManager; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'YAFS React App') ?></title>
    <?= AssetManager::react('src/main.jsx') ?>
</head>
<body>
    <div id="root"></div>
    <?php if (isset($props)): ?>
        <script>
            window.__YAFS_PROPS__ = <?= json_encode($props, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        </script>
    <?php endif; ?>
</body>
</html>