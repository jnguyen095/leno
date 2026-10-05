<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Leno</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center text-center" style="min-height:100vh; padding:1.5rem;">
<div>
  <i class="bi bi-qr-code-scan text-warning" style="font-size:3rem;"></i>
  <h4 class="fw-bold mt-3"><?php echo htmlspecialchars($table['table_name']); ?></h4>
  <p class="text-muted"><?php echo htmlspecialchars($message); ?></p>
</div>
</body>
</html>
