<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS API Docs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html, body {
            margin: 0;
            height: 100%;
            background: #f7f8fa;
        }
        .topbar {
            font-family: Arial, sans-serif;
            padding: 10px 16px;
            background: #1f2937;
            color: #fff;
            font-size: 14px;
        }
        .topbar a {
            color: #93c5fd;
        }
        #swagger-ui {
            height: calc(100% - 42px);
            overflow: auto;
        }
    </style>
</head>
<body>
<div class="topbar">
    HRMS OpenAPI Spec:
    <a href="<?= base_url('docs/openapi/hrms.yaml') ?>" target="_blank" rel="noopener noreferrer">
        <?= base_url('docs/openapi/hrms.yaml') ?>
    </a>
</div>
<div id="swagger-ui"></div>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
<script>
window.onload = function () {
    SwaggerUIBundle({
        url: "<?= base_url('docs/openapi/hrms.yaml') ?>",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        layout: "BaseLayout"
    });
};
</script>
</body>
</html>
