<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: { DEFAULT: '#C8102E', hover: '#A00D24', light: '#FEE2E2' },
            sidebar: '#1A1D23',
          }
        }
      }
    }
  </script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Custom styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
<div id="toast-container"></div>
